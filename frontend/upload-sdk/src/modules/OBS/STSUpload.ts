import ObsClient from "esdk-obs-browserjs"
import { InitException, InitExceptionCode } from "../../Exception/InitException"
import { UploadException, UploadExceptionCode } from "../../Exception/UploadException"
import type {
	NormalSuccessResponse,
	PlatformMultipartUploadOption,
	PlatformSimpleUploadOption,
} from "../../types"
import { PlatformType } from "../../types"
import type { OBS } from "../../types/OBS"
import { normalizeSuccessResponse } from "../../utils/response"

/**
 * Huawei Cloud PUT upload
 * @param file File
 * @param key File name
 * @param params Upload credential parameters
 * @param option Upload configuration
 * @returns
 */
export function STSUpload(
	file: File | Blob,
	key: string,
	params: OBS.STSAuthParams,
	// @ts-ignore
	option: PlatformSimpleUploadOption | PlatformMultipartUploadOption,
): Promise<NormalSuccessResponse> {
	const {
		bucket,
		credentials: { access, secret, security_token },
		endpoint,
		// callback,
		dir,
		region,
	} = params

	if (!bucket || !access || !secret || !security_token || !dir || !endpoint || !region) {
		throw new InitException(
			InitExceptionCode.MISSING_CREDENTIALS_PARAMS_FOR_UPLOAD,
			"obs",
			"bucket",
			"access_key_id",
			"secret_access_key",
			"security_token",
			"dir",
			"server",
			"region",
		)
	}

	// Huawei Cloud PutObject upload limit 5GB
	if (file?.size > 5 * 1024 * 1024 * 1024) {
		throw new InitException(InitExceptionCode.UPLOAD_FILE_TO_BIG, key)
	}

	const combinedKey = `${dir}${key}`

	const obsClient = new ObsClient({
		access_key_id: access,
		secret_access_key: secret,
		security_token,
		server: endpoint,
	})

	return new Promise((resolve, reject) => {
		obsClient.putObject(
			{
				Bucket: bucket,
				Key: combinedKey,
				SourceFile: file,
			},
			(err: any, data: OBS.PutResponseData) => {
				if (err) {
					reject(new UploadException(UploadExceptionCode.UPLOAD_UNKNOWN_ERROR))
				} else if (data.CommonMsg.Status < 300) {
					resolve(
						normalizeSuccessResponse(
							combinedKey,
							PlatformType.OBS,
							data.InterfaceResult,
						),
					)
				} else if (data.CommonMsg.Status === 403) {
					reject(new UploadException(UploadExceptionCode.UPLOAD_CREDENTIALS_IS_EXPIRED))
				} else {
					reject(new UploadException(UploadExceptionCode.UPLOAD_UNKNOWN_ERROR))
				}
			},
		)
	})
}




