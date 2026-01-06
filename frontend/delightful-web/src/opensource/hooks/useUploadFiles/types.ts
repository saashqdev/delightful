import type { UploadCallBack } from "@dtyq/upload-sdk"

export interface UploadResponse {
	key: string
	name: string
	size: number
}
export interface UseUploadFilesParams<F> {
	/** File storage type */
	storageType?: "private" | "public"
	/** Callback before upload */
	onBeforeUpload?: () => void
	/** Upload progress callback */
	onProgress?: (file: F, progress: number) => void
	/** Upload success callback */
	onSuccess?: (file: F, response: UploadResponse) => void
	/** Upload failure callback */
	onFail?: (file: F, error?: Error) => void
	/** Upload initialization callback */
	onInit?: (file: F, tools: Pick<UploadCallBack, "cancel" | "pause" | "resume">) => void
}
export interface UploadResult {
	fullfilled: PromiseFulfilledResult<UploadResponse>[]
	rejected: PromiseRejectedResult[]
}

export interface DownloadResponse {
	download_name: string
	url: string
	path: string
	expires: number
}
