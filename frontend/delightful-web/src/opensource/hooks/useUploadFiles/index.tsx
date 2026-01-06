import { userStore } from "@/opensource/models/user"
import { RequestUrl, RequestMethod } from "@/opensource/apis/constant"
import { env } from "@/utils/env"
import { genRequestUrl } from "@/utils/http"
import { Upload } from "@dtyq/upload-sdk"
import { useMemoizedFn } from "ahooks"
import { groupBy } from "lodash-es"
import { useRef, useState } from "react"
import { message } from "antd"
import type { ReportFileUploadsResponse } from "@/opensource/apis/modules/file"
import { FileApi } from "@/opensource/apis"
import type { UploadResponse, UseUploadFilesParams, UploadResult, DownloadResponse } from "./types"

export interface FileUploadData {
	name: string
	file: File
	status: "init" | "uploading" | "done" | "error"
	result?: UploadResponse
}

/**
 * Upload files (only upload to OSS; does not include reporting)
 * @param param0
 * @returns
 */
export const useUpload = <F extends FileUploadData>({
	onBeforeUpload,
	onProgress,
	onSuccess,
	onFail,
	onInit,
	storageType = "private",
}: UseUploadFilesParams<F> = {}) => {
	const uploader = useRef(new Upload())

	const [uploading, setUploading] = useState(false)

	const upload = useMemoizedFn<(fileList: F[]) => Promise<UploadResult>>(async (fileList) => {
		if (fileList.length === 0) {
			return { fullfilled: [], rejected: [] }
		}

		onBeforeUpload?.()
		setUploading(true)

		const promises: Promise<UploadResponse>[] = []

		for (let i = 0; i < fileList.length; i += 1) {
			const fileData = fileList[i]

			promises.push(
				new Promise<UploadResponse>((resolve, reject) => {
					const fileName = fileData.name

					if (fileData.status === "done" && fileData.result) {
						resolve(fileData.result)
						return
					}

					const { organizationCode } = userStore.user

					/**
					 * ⚠️ important:
					 * upload-sdk caches auth credentials by URL.
					 * To avoid cross-org leakage, isolate URLs with organization_code (currently by appending `?organization_code=xxx`).
					 * Ensures correct auth after switching organizations.
					 */
					const url = `${
						env("DELIGHTFUL_SERVICE_BASE_URL") +
						genRequestUrl(RequestUrl.getUploadCredentials)
					}?organization_code=${organizationCode}`

					const file = new File([fileData.file], "file", {
						type: fileData.file.type,
						lastModified: fileData.file.lastModified,
					})

					const { success, progress, fail, cancel, pause, resume } =
						uploader.current.upload({
							url,
							file,
							fileName,
							method: RequestMethod.POST,
							headers: {
								"Content-Type": "application/json",
								authorization: userStore.user.authorization ?? "",
								"Organization-Code": organizationCode ?? "",
							},
							option: {
								rewriteFileName: true,
								// reUploadedCount: shouldReFetchCredentials,
							},
							body: JSON.stringify({
								storage: storageType,
							}),
						})

					onInit?.(fileData, { cancel, pause, resume })

					success?.((res) => {
						if (res) {
							onSuccess?.(fileData, {
								key: res.data.path,
								name: fileName,
								size: fileData.file.size,
							})
							resolve({
								key: res.data.path,
								name: fileName,
								size: fileData.file.size,
							})
						} else reject(new Error("upload failed"))
					})

					fail?.((err) => {
						onFail?.(fileData, err)
						reject(err)
					})

					progress?.((percent) => {
						if (percent) onProgress?.(fileData, percent)
					})
				}),
			)
		}

		return Promise.allSettled(promises).then((data) => {
			const {
				fulfilled = [] as PromiseFulfilledResult<UploadResponse>[],
				rejected = [] as PromiseRejectedResult[],
			} = groupBy(data, (d) => d.status)

			const fullfilledData = fulfilled as PromiseFulfilledResult<UploadResponse>[]
			const rejectedData = rejected as PromiseRejectedResult[]

			setUploading(false)

			return { fullfilled: fullfilledData, rejected: rejectedData }
		})
	})

	const validateFileType = useMemoizedFn((file, maxSize = 2) => {
		// Validate file type
		const validTypes = ["image/png", "image/gif", "image/jpeg"]
		if (!validTypes.includes(file.type)) {
			message.error("Only png/jpg/gif images are supported")
			return false
		}

		if (file.size / 1024 / 1024 > maxSize) {
			message.error("Image must be no larger than 2MB")
			return false
		}
		return true
	})

	/**
	 * Get download URLs for files
	 * @param reportRes File report data
	 * @returns Object containing fulfilled and rejected results
	 */
	const getFileUrls = async (reportRes: ReportFileUploadsResponse[]) => {
		return Promise.allSettled(reportRes.map(async (r) => FileApi.getFileUrl(r.file_key))).then(
			(data) => {
				const {
					fulfilled = [] as PromiseFulfilledResult<DownloadResponse>[],
					rejected = [] as PromiseRejectedResult[],
				} = groupBy(data, (d) => d.status)

				const fullfilledData = fulfilled as PromiseFulfilledResult<DownloadResponse>[]
				const rejectedData = rejected as PromiseRejectedResult[]

				return { fullfilled: fullfilledData, rejected: rejectedData }
			},
		)
	}

	// Upload images and retrieve file URLs
	const uploadAndGetFileUrl = useMemoizedFn(
		async (filesList, validator?: (file: File) => boolean) => {
			const validatorFn = validator ?? validateFileType
			if (validatorFn(filesList[0].file)) {
				const { fullfilled } = await upload(filesList)
				if (fullfilled.length === filesList.length) {
					const reportRes = await FileApi.reportFileUploads(
						fullfilled.map((d) => ({
							file_extension: d.value.name.split(".").pop() ?? "",
							file_key: d.value.key,
							file_size: d.value.size,
							file_name: d.value.name,
						})),
					)
					return getFileUrls(reportRes)
				}
			}
			return { fullfilled: [] }
		},
	)

	return {
		upload,
		uploading,
		uploadAndGetFileUrl,
		reportFiles: FileApi.reportFileUploads,
	}
}
