import type { OSS } from "./OSS"
import type { ErrorType } from "./error"
import type { Kodo } from "./Kodo"
import type { Local } from "./Local"
import type { Method } from "./request"
import type { TOS } from "./TOS"
import type { OBS } from "./OBS"
import type { MinIO } from "./MinIO"

/** Request type */
export type MethodType = Method

/**
 * @description Unified success response
 * @param code 1000 for successful request
 * @param message Request status information
 * @param data Return data payload
 */
export interface NormalSuccessResponse {
	code: number
	message: string
	headers: Record<string, string>
	data: {
		platform: PlatformType
		path: string
	}
}

/**
 * @param url Request URL
 * @param method Request method
 * @param headers Request headers
 * @param body Request body
 */
export interface Request {
	url: string
	method: Method
	headers?: Record<string, string>
	body?: any
}

/**
 * @description: Image processing configuration
 * @param type // Image processing type, e.g.: resize, watermark
 * @param params  Image processing parameters, refer to: https://help.aliyun.com/document_detail/144582.html
 */
export interface TransformImageConfig {
	type:
		| "resize"
		| "watermark"
		| "crop"
		| "quality"
		| "format"
		| "auto-orient"
		| "circle"
		| "indexcrop"
		| "rounded-corners"
		| "blur"
		| "rotate"
		| "interlace"
		| "bright"
		| "sharpen"
		| "contrast"
	params: Record<string, any>
}

/**
 * @description: Download configuration
 * @param url Request URL
 * @param method Request method
 * @param headers Request headers
 * @param body Request body
 */
export interface DownloadConfig extends Request {
	option?: {
		image?: TransformImageConfig[]
	}
}

type UploadConfigOption = UploadCommonOption &
	PlatformSimpleUploadOption &
	PlatformMultipartUploadOption

/**
 * @description: Common upload configuration
 * @param rewriteFileName Whether to rename the file
 */
export interface UploadCommonOption {
	rewriteFileName?: boolean
}

/**
 * @description: Custom upload credential configuration
 * @param platform Platform type
 * @param credentials Credential information
 * @param expire Expiration timestamp (optional)
 */
export interface CustomCredentials {
	platform: PlatformType
	temporary_credential: PlatformParams
	expire?: number
}

/**
 * @description: Upload configuration, requires developers to configure upload credential request, SDK handles upload request internally
 * @param url  Request upload credential - URL (optional when using custom credentials)
 * @param method Request upload credential - request method (optional when using custom credentials)
 * @param headers Request upload credential - request headers (optional when using custom credentials)
 * @param body Request upload credential - request body (optional when using custom credentials)
 * @param {File | Blob} file File to be uploaded
 * @param fileName File name
 * @param option Optional upload configuration
 * @param customCredentials Custom upload credentials (optional, will skip credential request when provided)
 */
export interface UploadConfig {
	url?: string
	method?: Method
	headers?: Record<string, string>
	body?: any
	file: File | Blob
	fileName: string
	option?: UploadConfigOption
	customCredentials?: CustomCredentials
}

/** Upload callback */
export interface UploadCallBack extends TaskCallBack {}

export type DonePart = { number: number; etag: string }

export interface Checkpoint {
	/** The file object selected by the user, if the browser is restarted, it needs the user to manually trigger the settings */
	file: any
	/** object key */
	name: string
	fileSize: number
	partSize: number
	uploadId: string
	doneParts: DonePart[]
}

/** percent: percentage progress loaded: bytes uploaded total: total bytes checkpoint: upload checkpoint information */
export type Progress = (
	percent: number,
	loaded: number,
	total: number,
	checkpoint: Checkpoint | null,
) => void

/** PlatformType cloud storage provider enum type */
export enum PlatformType {
	OSS = "aliyun",
	Kodo = "qiniu",
	TOS = "tos",
	OBS = "obs",
	Local = "local",
	Minio = "minio",
}

/** Aggregated platform parameters */
export type PlatformParams =
	| OSS.AuthParams
	| OSS.STSAuthParams
	| Kodo.AuthParams
	| Local.AuthParams
	| TOS.AuthParams
	| TOS.STSAuthParams
	| OBS.AuthParams
	| OBS.STSAuthParams
	| MinIO.AuthParams
	| MinIO.STSAuthParams

/** Request temporary credentials, return data template */
export interface UploadSource<T extends PlatformParams> {
	platform: PlatformType
	temporary_credential: T
	expire: number
}

/** Global variables */
export type GlobalCache<T extends PlatformParams> = Record<string, UploadSource<T>>

/** Object storage platform base Option */
interface PlatformOption {
	headers?: Record<string, string>
	taskId?: TaskId
	progress?: Progress
	reUploadedCount?: number
}

/** Multipart upload Option optional fields */
export interface PlatformMultipartUploadOption extends PlatformOption {
	parallel?: number
	partSize?: number
	checkpoint?: OSS.Checkpoint
	mime?: string | null
}

/** Simple upload Option optional fields */
export interface PlatformSimpleUploadOption extends PlatformOption {}

/** Upload file implementation interface for different object storage platforms */
export type PlatformRequest<P, O> = (
	file: File | Blob,
	key: string,
	params: P,
	option: O,
) => Promise<NormalSuccessResponse>

/** Object storage platform module */
export interface PlatformModule {
	upload: PlatformRequest<
		PlatformParams,
		PlatformSimpleUploadOption | PlatformMultipartUploadOption
	>
}

/** 上传文件开始会生成一个 TaskId */
export type TaskId = string

/** 上传进度回调参数 */
export interface ProgressCallbackProps {
	percent?: number
	loaded?: number
	total?: number
	checkpoint?: OSS.Checkpoint | null
}

/** 成功钩子毁掉 */
export type SuccessCallback = (response?: NormalSuccessResponse) => void

/** 失败钩子毁掉 */
export type FailCallback = (err?: ErrorType.UploadError) => void

/** 进度钩子毁掉 */
export type ProgressCallback = (
	percent?: ProgressCallbackProps["percent"],
	loaded?: ProgressCallbackProps["loaded"],
	total?: ProgressCallbackProps["total"],
	checkpoint?: ProgressCallbackProps["checkpoint"],
) => void

/** 上传任务回调 */
export interface TaskCallBack {
	// 上传成功
	success?: (callback: SuccessCallback) => void
	// 上传出错
	fail?: (callback: FailCallback) => void
	// 进度
	progress?: (callback: ProgressCallback) => void
	// 取消
	cancel?: () => void
	// 暂停
	pause?: () => void
	// 恢复
	resume?: () => void
}

/** 任务对象 */
export interface Task {
	// 上传成功
	success?: SuccessCallback
	// 上传出错
	fail?: FailCallback
	// 进度
	progress?: ProgressCallback
	// 取消
	cancel?: () => void
	// 暂停
	pause?: () => void
	// 恢复
	resume?: () => void
	/** 暂停行为中的文件进度信息 */
	pauseInfo?: {
		isPause: boolean
		checkpoint: OSS.Checkpoint
	}
}
