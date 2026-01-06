import ObsClient from "esdk-obs-browserjs"
import mime from "mime"
import { InitException, InitExceptionCode } from "../../Exception/InitException"
import { UploadException, UploadExceptionCode } from "../../Exception/UploadException"
import type {
	Checkpoint,
	DonePart,
	NormalSuccessResponse,
	PlatformMultipartUploadOption,
	PlatformRequest,
} from "../../types"
import { PlatformType } from "../../types"
import type { ErrorType } from "../../types/error"
import type { OBS } from "../../types/OBS"
import { isBlob, isFile } from "../../utils/checkDataFormat"
import {
	createBuffer,
	divideParts,
	getPartSize,
	initCheckpoint,
	parallelSend,
} from "../../utils/multipart"
import { OBS_MIN_PART_SIZE } from "./utils"
import { parseExtname } from "../../utils/regExpUtil"
import { normalizeSuccessResponse } from "../../utils/response"
import { STSUpload } from "./STSUpload"
import type { DataWrapperWithHeaders } from "../../types/request"
import { request } from "../../utils/request"

/**
 * Multipart upload initialization to obtain UploadId from OBS
 * @param key File name (with path)
 * @param obsClient OBS upload client
 * @param param2 Upload credential info
 * @param param3 Upload configuration
 * @returns
 */
async function initMultipartUpload(
	key: string,
	obsClient: ObsClient,
	{ bucket, expires }: OBS.STSAuthParams,
	options: OBS.InitMultipartUploadOption,
) {
	const { SignedUrl, ActualSignedRequestHeaders } = obsClient.createSignedUrlSync({
		Method: "POST",
		Bucket: bucket,
		Key: key,
		SpecialParam: "uploads",
		Expires: expires,
	})

	const {
		data: { InitiateMultipartUploadResult },
	} = await request<DataWrapperWithHeaders<OBS.InitMultipartUploadResponse>>({
		url: SignedUrl,
		headers: ActualSignedRequestHeaders,
		method: "POST",
		xmlResponse: true,
		...options,
	})

	return {
		res: InitiateMultipartUploadResult,
		bucket: InitiateMultipartUploadResult.Bucket,
		name: InitiateMultipartUploadResult.Key,
		UploadId: InitiateMultipartUploadResult.UploadId,
	}
}

/**
 * After multipart upload is done, call this to complete it
 * @param name File name
 * @param UploadId Upload ID
 * @param parts Uploaded parts
 * @param obsClient OBS upload client
 * @param param4 Upload credential
 * @returns
 */
async function completeMultipartUpload(
	name: string,
	UploadId: string,
	parts: Array<OBS.UploadPart>,
	obsClient: ObsClient,
	{ bucket }: OBS.STSAuthParams,
	{ progress, partSize }: PlatformMultipartUploadOption,
) {
	const completeParts = parts
		.concat()
		.sort((a, b) => a.number - b.number)
		.filter((item, index, arr) => !index || item.number !== arr[index - 1].number)
		.map((item) => ({
			PartNumber: item.number,
			ETag: item.etag,
		}))

	return new Promise<NormalSuccessResponse>((resolve, reject) => {
		obsClient.completeMultipartUpload(
			{
				Bucket: bucket,
				Key: name,
				UploadId: UploadId,
				Parts: completeParts,
			},
			(err: unknown, result: OBS.CompleteMultipartUploadResponse) => {
				if (err) {
					reject(err)
				} else {
					if (progress && partSize) {
						progress(100, parts.length * partSize, parts.length * partSize, null)
					}

					resolve(
						normalizeSuccessResponse(
							result.InterfaceResult.Key,
							PlatformType.OBS,
							result.InterfaceResult,
						),
					)
				}
			},
		)
	})
}

/**
 * Used to upload a specific part
 * @param key File name (with path)
 * @param UploadId Upload ID
 * @param partNo Part number
 * @param data Part content
 * @param obsClient OBS upload client
 * @param param5 Upload credential
 * @returns
 */
async function uploadPart(
	key: string,
	UploadId: string,
	partNo: number,
	data: OBS.PartInfo,
	obsClient: ObsClient,
	{ bucket, expires }: OBS.STSAuthParams,
	options: PlatformMultipartUploadOption,
) {
	const { SignedUrl, ActualSignedRequestHeaders } = obsClient.createSignedUrlSync({
		Method: "PUT",
		Bucket: bucket,
		Key: key,
		QueryParams: {
			partNumber: `${partNo}`,
			UploadId,
		},
		Expires: expires,
	})

	const result = await request<OBS.UploadPartResponse>({
		url: SignedUrl,
		method: "PUT",
		data: data.content,
		headers: ActualSignedRequestHeaders,
		taskId: `${partNo}`,
		...options,
	})

	if (!result.headers.etag) {
		throw new InitException(InitExceptionCode.UPLOAD_HEAD_NO_EXPOSE_ETAG)
	}

	return {
		name: key,
		etag: result.headers.etag,
		res: result,
	}
}

/**
 * Used for multipart upload or ResumeCheckpoint resume
 * @param checkpoint Checkpoint
 * @param obsClient  OBS upload client
 * @param params Upload credential
 * @param options Upload configuration
 * @returns
 */
async function resumeMultipart(
	checkpoint: Checkpoint,
	obsClient: ObsClient,
	params: OBS.STSAuthParams,
	options: PlatformMultipartUploadOption,
) {
	const { file, fileSize, partSize, UploadId, doneParts, name } = checkpoint
	const internalDoneParts = doneParts.length > 0 ? [...doneParts] : []
	const partOffs = divideParts(fileSize, partSize)
	const numParts = partOffs.length
	let multipartFinish = false
	const opt = { ...options, partSize }
	const uploadPartJob = (partNo: number): Promise<void | DonePart> =>
		// eslint-disable-next-line no-async-promise-executor
		new Promise(async (resolve, reject) => {
			try {
				const pi = partOffs[partNo - 1]
				const content = await createBuffer(file, pi.start, pi.end)
				const data = {
					content,
					size: pi.end - pi.start,
				}

				const result = await uploadPart(name, UploadId, partNo, data, obsClient, params, {
					...opt,
				})

				if (!multipartFinish) {
					checkpoint.doneParts.push({
						number: partNo,
						etag: result.etag,
					})

					if (typeof options.progress === "function") {
						console.log(11)
						options.progress(
							(doneParts.length / (numParts + 1)) * 100,
							doneParts.length * partSize,
							fileSize,
							checkpoint,
						)
					}

					resolve({
						number: partNo,
						etag: result.etag,
					})
				} else {
					resolve()
				}
			} catch (err: any) {
				const tempErr = new Error() as unknown as ErrorType.UploadPartException
				tempErr.name = err.name
				tempErr.message = err.message
				tempErr.stack = err.stack
				tempErr.partNum = partNo
				tempErr.status = err.status

				reject(tempErr)
			}
		})

	const all = Array.from(new Array(numParts), (_, i) => i + 1)
	const done = internalDoneParts.map((p) => p.number)
	const todo = all.filter((p) => done.indexOf(p) < 0)
	const defaultParallel = 5
	const parallel = opt.parallel || defaultParallel
	// upload in parallel
	const jobErr: ErrorType.UploadPartException[] = await parallelSend(
		todo,
		parallel,
		(value) =>
			new Promise((resolve, reject) => {
				uploadPartJob(value)
					.then((result: DonePart | void) => {
						if (result) {
							internalDoneParts.push(result)
						}
						resolve()
					})
					.catch((err) => {
						reject(err)
					})
			}),
	)

	multipartFinish = true

	if (jobErr && jobErr.length > 0) {
		const error = jobErr[0]
		// 5001 Cancel upload, 5002 Pause upload
		if (error.status === 5001 || error.status === 5002) {
			throw error as Error
		}
		throw new UploadException(
			UploadExceptionCode.UPLOAD_MULTIPART_ERROR,
			error.message.replace("[Uploader] ", ""),
			error.partNum,
		)
	}

	return completeMultipartUpload(name, UploadId, internalDoneParts, obsClient, params, opt)
}

/**
 * Multipart upload interface, e.g., for part upload or Checkpoint resume
 * @param file File
 * @param key File name
 * @param params Credential parameters
 * @param option Upload parameters
 * @returns
 */
export const MultipartUpload: PlatformRequest<
	OBS.STSAuthParams,
	PlatformMultipartUploadOption
> = async (
	file: File | Blob,
	key: string,
	params: OBS.STSAuthParams,
	option: PlatformMultipartUploadOption,
) => {
	const options = { ...option }
	const {
		endpoint,
		region,
		bucket,
		dir,
		credentials: { access, secret, security_token },
	} = params

	if (!region || !bucket || !dir || !endpoint || !access || !secret || !security_token) {
		throw new InitException(
			InitExceptionCode.MISSING_CREDENTIALS_PARAMS_FOR_UPLOAD,
			"volcEngine",
			"region",
			"bucket",
			"dir",
			"endpoint",
			"access_key_id",
			"secret_access_key",
			"security_token",
		)
	}

	const name = `${dir}${key}`

	const obsClient = new ObsClient({
		access_key_id: access,
		secret_access_key: secret,
		security_token,
		server: endpoint,
	})

	// Generate File type
	if (!options.mime) {
		if (isFile(file)) {
			options.mime = file.type
		} else if (isBlob(file)) {
			options.mime = file.type
		} else {
			options.mime = mime.getType(parseExtname(name))
		}
	}

	if (options.checkpoint && options.checkpoint.UploadId) {
		if (file && isFile(file)) options.checkpoint.file = file
		if (file) options.checkpoint.file = file

		return resumeMultipart(options.checkpoint, obsClient, params, {
			...options,
		})
	}

	options.headers = options.headers || {}

	const fileSize = file.size
	if (fileSize < OBS_MIN_PART_SIZE) {
		return STSUpload(file, key, params, { ...options })
	}
	if (options.partSize && !(parseInt(String(options.partSize), 10) === options.partSize)) {
		throw new InitException(InitExceptionCode.UPLOAD_API_OPTION_PARTSIZE_MUST_INT)
	}

	if (options.partSize && options.partSize < OBS_MIN_PART_SIZE) {
		throw new InitException(
			InitExceptionCode.UPLOAD_API_OPTION_PARTSIZE_IS_SMALL,
			OBS_MIN_PART_SIZE,
		)
	}

	// Initialize multipart upload
	const { UploadId } = await initMultipartUpload(name, obsClient, params, {
		headers: { ...options.headers },
		mime: options.mime,
	})

	// Get part size
	const partSize = getPartSize(fileSize, <number>options.partSize, OBS_MIN_PART_SIZE)

	const checkpoint = initCheckpoint(file, name, fileSize, partSize, UploadId)

	if (options && options.progress) {
		options.progress(0, 0, fileSize, checkpoint)
	}

	return resumeMultipart(checkpoint, obsClient, params, { ...options })
}




