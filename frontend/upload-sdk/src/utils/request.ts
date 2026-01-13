/**
 * @file Global request
 */

import { HttpException, HttpExceptionCode } from "../Exception/HttpException"
import type { TaskId } from "../types"
import type { RequestTask, UploadRequestConfig } from "../types/request"
import { isObject } from "./checkDataFormat"
import { formatHeaders } from "../modules/TOS/utils"

// Maintain a request queue
let requestTasks: RequestTask[] = []

// Maintain AbortController queue (for AWS SDK and other Fetch-based requests)
let abortControllers: Map<TaskId, AbortController[]> = new Map()

// Maintain task cancel/pause state (to distinguish between cancel and pause)
let taskAbortStates: Map<TaskId, "cancel" | "pause"> = new Map()

/**
 * Register an AbortController for a task (used for AWS SDK requests)
 * @param {TaskId} taskId Task ID
 * @param {AbortController} controller AbortController instance
 */
export function registerAbortController(taskId: TaskId, controller: AbortController): void {
	if (!abortControllers.has(taskId)) {
		abortControllers.set(taskId, [])
	}
	abortControllers.get(taskId)?.push(controller)
}

/**
 * Get the AbortSignal for a task
 * @param {TaskId} taskId Task ID
 * @returns {AbortSignal | undefined} AbortSignal if exists
 */
export function getAbortSignal(taskId: TaskId): AbortSignal | undefined {
	const controllers = abortControllers.get(taskId)
	if (controllers && controllers.length > 0) {
		// Create a new controller for this specific request
		const controller = new AbortController()
		controllers.push(controller)
		return controller.signal
	}
	return undefined
}

/**
 * Create a new AbortController for a task and return its signal
 * @param {TaskId} taskId Task ID
 * @returns {AbortSignal} AbortSignal
 */
export function createAbortSignal(taskId: TaskId): AbortSignal {
	const controller = new AbortController()
	registerAbortController(taskId, controller)
	return controller.signal
}

/**
 * Get the abort state of a task (cancel or pause)
 * @param {TaskId} taskId Task ID
 * @returns {"cancel" | "pause" | undefined} The abort state
 */
export function getTaskAbortState(taskId: TaskId): "cancel" | "pause" | undefined {
	return taskAbortStates.get(taskId)
}

/**
 * XMLHttpRequest object compatibility handling
 */
function createXHR() {
	if (typeof XMLHttpRequest !== "undefined") {
		// Firefox, Opera, Safari, Chrome
		return new XMLHttpRequest()
	}
	throw new HttpException(HttpExceptionCode.REQUEST_NO_XHR_OBJ_AVAILABLE)
}
/**
 * @description Terminate file upload
 * @param {TaskId} taskId Task ID
 */
export function cancelRequest(taskId: TaskId): void {
	// Set abort state to cancel
	taskAbortStates.set(taskId, "cancel")
	
	// Cancel XHR requests
	requestTasks.forEach((task) => {
		if (task.taskId === taskId) {
			task.makeCancel()
		}
	})
	
	// Cancel Fetch-based requests (AWS SDK)
	const controllers = abortControllers.get(taskId)
	if (controllers) {
		controllers.forEach((controller) => {
			controller.abort()
		})
	}
}

/**
 * @description Pause file upload
 * @param {TaskId} taskId Task ID
 */
export function pauseRequest(taskId: TaskId): void {
	// Set abort state to pause
	taskAbortStates.set(taskId, "pause")
	
	// Pause XHR requests
	requestTasks.forEach((task) => {
		if (task.taskId === taskId) {
			task.makePause()
		}
	})
	
	// Pause Fetch-based requests (AWS SDK)
	const controllers = abortControllers.get(taskId)
	if (controllers) {
		controllers.forEach((controller) => {
			controller.abort()
		})
	}
}

/**
 * @description XML =>>> Object
 * @param {XMLDocument} xml
 * @return {Object} convertedObj Converted Object
 */
function parseXML(xml: XMLDocument | null): Record<string, string | object> | {} {
	// output
	let convertedObj: Record<string, object | string> = {}
	if (xml?.children && xml.children.length !== 0) {
		Object.values(xml.children).forEach((childNode: any) => {
			convertedObj = {
				...convertedObj,
				[childNode.nodeName]: parseXML(childNode),
			}
		})
	} else {
		return xml?.textContent ? xml.textContent : {}
	}
	return convertedObj
}

/**
 * Parse header string to object
 * @param str
 */
function parseHeaderString(str: string) {
	return str.split("\r\n").reduce((obj, header) => {
		let tempObj = { ...obj }
		if (header !== "" && header.split(":").length > 0) {
			const [key, value] = header.split(":")
			tempObj = {
				...tempObj,
				[key]: value.replace(" ", ""),
			}
		}
		return tempObj
	}, {})
}

/**
 * @description Request completion (whether success or failure)
 * @param {TaskId} taskId Task ID
 */
export function completeRequest(taskId: TaskId | undefined): void {
	if (taskId === undefined) return

	requestTasks = requestTasks.filter((task) => task.taskId !== taskId)
	
	// Clean up AbortControllers
	abortControllers.delete(taskId)
	
	// Clean up abort state
	taskAbortStates.delete(taskId)
}

/**
 * Determine if response is JSON
 * @param req
 */
function isJSONResponse(req: XMLHttpRequest) {
	return req.responseType === "json" && typeof req.response === "string"
}

/**
 * Add headers object to response object
 * @param result
 * @param responseHeaders
 */
function addHeadersToResponse<T extends Record<string, any>, H extends Record<string, string>>(
	result: T,
	responseHeaders: H,
): T & { headers: H } {
	return {
		...result,
		headers: responseHeaders,
	}
}

/**
 * Encapsulate XHR request method
 * @param {UploadRequestConfig} uploadRequestConfig
 * @return {Promise<Result>}
 */
export function request<T>(uploadRequestConfig: UploadRequestConfig): Promise<T> {
	const {
		method,
		url,
		data,
		query,
		headers,
		success,
		fail,
		taskId,
		onProgress,
		xmlResponse,
		withoutWrapper,
	} = uploadRequestConfig

	return new Promise((resolve, reject) => {
		// Create a request
		const req = createXHR()
		// Cancel state
		let isCancel: boolean = false

		// Pause state
		let isPause: boolean = false

		req.responseType = xmlResponse ? "document" : "json"

		// Monitor upload file start
		req.upload.onloadstart = () => {
			if (taskId) {
				requestTasks.push({
					taskId,
					makeCancel: () => {
						isCancel = true
						req.abort()
					},
					makePause: () => {
						isPause = true
						req.abort()
					},
				})
			}
		}

		// Monitor upload file progress event
		req.upload.onprogress = (evt: any) => {
			if (onProgress) onProgress((evt.loaded / evt.total) * 100, evt.loaded, evt.total, null)
		}

		// Handle query parameters
		let handledUrl = url
		if (query) {
			const queryString = Object.entries(query)
				.map(([key, value]) => {
					if (value) {
						return `${key}=${value}`
					}
					return key
				})
				.join("&")

			if (queryString) {
				handledUrl += `?${queryString}`
			}
		}

		// Set request method, URL, async
		req.open(method.toUpperCase(), handledUrl, true)

		// Set request headers
		req.setRequestHeader("language", "en_US")

		if (headers && Object.keys(headers).length > 0) {
			const handledHeaders = formatHeaders(headers)
			Object.keys(handledHeaders).forEach((key) => {
				req.setRequestHeader(key, headers[key])
			})
		}

		// Send request
		req.send(data as XMLHttpRequestBodyInit | null)

		// Receive response
		req.onreadystatechange = () => {
			if (req.readyState !== 4) {
				return
			}
			// Judge response status
			if (/^2\d{2}/.test(String(req.status))) {
				// Request success
				let result = isJSONResponse(req) ? JSON.parse(req.response) : req.response
				const responseHeaders: Record<string, string> = {
					...parseHeaderString(req.getAllResponseHeaders()),
				}

				// aliyun initMultipleUpload
				if (xmlResponse) {
					result = {
						data: parseXML(req.responseXML),
					}
				}
				// tos post
				if (withoutWrapper || !isObject(result)) {
					result = {
						data: result,
					}
				}
				result = addHeadersToResponse(result, responseHeaders)

				resolve(result)
				if (success && typeof success === "function") {
					success(result)
				}
			} else {
				// Request failure
				if (fail && typeof fail === "function") {
					fail(req.status, reject)
				}
				if (isCancel) {
					reject(new HttpException(HttpExceptionCode.REQUEST_IS_CANCEL))
				} else if (isPause) {
					reject(new HttpException(HttpExceptionCode.REQUEST_IS_PAUSE))
				}
				reject(
					new HttpException(
						HttpExceptionCode.REQUEST_FAILED_WITH_STATUS_CODE,
						req.status,
					),
				)
			}
		}
	})
}




