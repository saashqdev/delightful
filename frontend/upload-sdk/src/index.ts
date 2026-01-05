import { version } from "../package.json"
import { DownloadException, DownloadExceptionCode } from "./Exception/DownloadException"
import { InitException, InitExceptionCode } from "./Exception/InitException"
import PlatformModules from "./modules"
import type { DownloadConfig, UploadCallBack, UploadConfig } from "./types"
import { PlatformType } from "./types"
import type { LogModule } from "./types/log"
import { isJson, isObject } from "./utils/checkDataFormat"
import logPubSub from "./utils/logPubSub"
import { request } from "./utils/request"
import { UploadManger } from "./utils/UploadManger"
import { checkSpecialCharacters, getFileExtension } from "./utils"
import { nanoid } from "./utils/nanoid"

export class Upload {
	/** Package version */
	static version: string = version

	uploadManger: UploadManger

	public constructor() {
		this.uploadManger = new UploadManger()
	}

	/**
	 * @description: Generate temporary URL for file download/preview
	 * @param {DownloadConfig} downloadConfig
	 * @return Promise<any>
	 */
	public static download(downloadConfig: DownloadConfig): Promise<any> {
		const { url, method, headers, body, option } = downloadConfig
		let tempBody = body

		try {
			// If body is FormData format
			if (tempBody && tempBody instanceof FormData && option) {
				tempBody.append("options", JSON.stringify(option))
				// eslint-disable-next-line no-console
				console.warn("Since body is FormData type, option field (image processing parameters) may become ineffective")
			} else if (tempBody && isJson(tempBody)) {
				// If body is JSON format
				tempBody = JSON.stringify({
					...JSON.parse(tempBody),
					options: option,
				})
			} else if (tempBody && isObject(tempBody)) {
				// If body is Object format
				tempBody = JSON.stringify({
					...tempBody,
					options: option,
				})
			}
		} catch (e) {
			tempBody = body
		}

		return request({
			url,
			method: method || "post",
			headers,
			data: tempBody,
			success: (response) => {
				logPubSub.report({
					type: "SUCCESS",
					eventName: "download",
					eventParams: {
						url,
						method: method || "post",
						headers,
						body: tempBody,
					},
					eventResponse: response,
				})
			},
			fail: (status, reject) => {
				const error = new DownloadException(
					DownloadExceptionCode.DOWNLOAD_REQUEST_ERROR,
					status,
				)
				reject(error)
				logPubSub.report({
					type: "ERROR",
					eventName: "download",
					eventParams: {
						url,
						method: method || "post",
						headers,
						body: tempBody,
					},
					error,
				})
			},
		})
	}

	/**
	 * @description: File upload interface, including simple upload/multipart upload/resumable upload, will select the corresponding platform and upload method based on upload credential information
	 * @param {UploadConfig} uploadConfig Upload configuration
	 * @return {UploadCallBack} uploadCallBack Upload callback
	 */
	public upload(uploadConfig: UploadConfig): UploadCallBack {
		const { url, method, file, option, customCredentials } = uploadConfig

		// Validate parameters: if no custom credentials provided, url and method must be provided
		if (!customCredentials && (!url || !method)) {
			throw new InitException(InitExceptionCode.MISSING_PARAMS_FOR_UPLOAD, "url", "method")
		}

		// If custom credentials are provided, validate credential parameters
		if (customCredentials) {
			const { platform, temporary_credential } = customCredentials
			if (!platform || !temporary_credential) {
				throw new InitException(
					InitExceptionCode.MISSING_PARAMS_FOR_UPLOAD,
					"platform",
					"credentials",
				)
			}
		}

		// Handle filename
		const { rewriteFileName } = option || {}
		if (rewriteFileName) {
			const suffix = getFileExtension(uploadConfig.fileName)
			uploadConfig.fileName = `${nanoid()}.${suffix}`
		}

		// Check if filename contains special characters
		const hasError = checkSpecialCharacters(uploadConfig.fileName)
		if (hasError) {
			throw new InitException(
				InitExceptionCode.UPLOAD_FILENAME_EXIST_SPECIAL_CHAR,
				uploadConfig.fileName,
			)
		}
		return this.uploadManger.createTask(file, uploadConfig.fileName, uploadConfig, option || {})
	}

	/**
	 * @description: Pause all file uploads (multipart upload)
	 */
	public pause() {
		this.uploadManger.pauseAllTask()
	}

	/**
	 * @description: Resume all file uploads (multipart upload)
	 */
	public resume() {
		this.uploadManger.resumeAllTask()
	}

	/**
	 * @description: Cancel all file uploads (multipart upload)
	 */
	public cancel() {
		this.uploadManger.cancelAllTask()
	}

	/**
	 * @description: Pass in callback function to subscribe to log content
	 */
	static subscribeLogs(callback: LogModule.CallBack) {
		logPubSub.subscribe(callback)
	}
}

// Export types and functions
export type { DownloadConfig, UploadCallBack, UploadConfig }
export { PlatformType, PlatformModules }

export { default as TOS } from "./modules/TOS"
export { default as OSS } from "./modules/OSS"
export { default as OBS } from "./modules/OBS"
export { default as Kodo } from "./modules/Kodo"
export { default as Local } from "./modules/Local"
export { default as MinIO } from "./modules/MinIO"
