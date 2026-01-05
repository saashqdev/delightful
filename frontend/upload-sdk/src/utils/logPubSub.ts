import type { LogModule } from "../types/log"

/**
 * Log module for publishing and collecting logs
 */
class LogPubSub {
	private callbacks: LogModule.CallBack[] = []

	// Subscribe to log
	subscribe(cb: LogModule.CallBack) {
		this.callbacks.push(cb)
	}

	// Publish log
	report(createLogConfig: LogModule.CreateLogConfig) {
		const log = LogPubSub.createLog(createLogConfig)

		if (this.callbacks.length > 0) {
			this.callbacks.forEach((cb) => cb(log))
		}
	}

	/**
	 * Create log
	 * @param {LogModule.createLogConfig} createLogConfig
	 * @return {LogModule.LogData} LogData
	 */
	static createLog(createLogConfig: LogModule.CreateLogConfig): LogModule.LogData {
		const { type, eventName, eventParams, eventResponse, error, extra } = createLogConfig
		let output = {
			type,
			event_name: eventName,
			event_params: eventParams || "",
			event_response: eventResponse || "",
			exception_type: "",
			exception_message: "",
			exception_file: "",
			exception_line: "",
			exception_row: "",
			extra: extra || "",
			time: new Date(),
			version: "Upload-SDK.js VERSION",
		}
		if (type === "ERROR" && error) {
			const parseError = /at\s+(.*)\s+\((.*):(\d*):(\d*)\)/i.exec(error.stack || "")

			output = {
				...output,
				exception_type: error.name,
				exception_message: error.message,
				exception_file: parseError ? parseError[2] : "",
				exception_line: parseError ? parseError[3] : "",
				exception_row: parseError ? parseError[4] : "",
			}
		}

		return output
	}
}

export default new LogPubSub()
