/**
 * Error type namespace
 * */
export namespace ErrorType {
	export interface BaseException {
		/** Error message */
		message: string
	}

	export interface BaseExceptionWithStatus extends BaseException {
		/** Error code */
		status: number
	}

	export interface UploadError extends Error {
		status?: number
	}

	export interface UploadPartException extends BaseExceptionWithStatus {
		name: string
		stack: string
		partNum: number
	}
}
