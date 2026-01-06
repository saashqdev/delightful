import type { ErrorType } from "./error"

/**
 * Log module namespace
 * */
export namespace LogModule {
	export type LogType = "SUCCESS" | "ERROR" | "WARN" | "DEBUG"

	export type CallBack = (log: any) => void

	export type LogEventType = "upload" | "download"

	export type CreateLogConfig = {
		type: LogType
		/** Event name */
		eventName: LogEventType
		/** Event parameters */
		eventParams?: any
		/** Event response */
		eventResponse?: any
		error?: ErrorType.UploadError
		/** Additional content */
		extra?: string
	}

	/**
	 * Log data structure
	 */
	export interface LogData {
		/** Status type */
		type: LogType
		/** Event name */
		event_name: LogEventType
		/** Event parameters */
		event_params: any
		/** Event response */
		event_response: any
		/** Exception type */
		exception_type: string
		/** Exception message */
		exception_message: string
		/** File where exception was triggered */
		exception_file: string
		/** Line number where exception was triggered */
		exception_line: string
		/** Column number where exception was triggered */
		exception_row: string
		/** Additional content */
		extra?: string
		/** Record time */
		time: Date
		/** SDK name and version */
		version: string
	}
}




