import { describe, test, expect } from "vitest"
import { DownloadException, DownloadExceptionCode } from "../../src/Exception/DownloadException"
import { BaseException } from "../../src/Exception/BaseException"

describe("DownloadException", () => {
	test("should inherit from BaseException", () => {
		const exception = new DownloadException(
			DownloadExceptionCode.DOWNLOAD_REQUEST_ERROR,
			404
		)
		
		expect(exception).toBeInstanceOf(BaseException)
		expect(exception).toBeInstanceOf(DownloadException)
	})

	test("DOWNLOAD_REQUEST_ERROR should contain HTTP status code", () => {
		const httpStatus = 404
		const exception = new DownloadException(
			DownloadExceptionCode.DOWNLOAD_REQUEST_ERROR,
			httpStatus
		)
		
		expect(exception.message).toContain("[Uploader]")
		expect(exception.message).toContain(httpStatus.toString())
		expect(exception.status).toBe(3002)
	})

	test("should correctly handle different HTTP status codes", () => {
		const statuses = [400, 401, 403, 404, 500, 502, 503]
		
		statuses.forEach(httpStatus => {
			const exception = new DownloadException(
				DownloadExceptionCode.DOWNLOAD_REQUEST_ERROR,
				httpStatus
			)
			
			expect(exception.message).toContain(httpStatus.toString())
		})
	})

	test("exception code should be numeric", () => {
		const exception = new DownloadException(
			DownloadExceptionCode.DOWNLOAD_REQUEST_ERROR,
			404
		)
		
		expect(typeof exception.status).toBe("number")
	})

	test("should be able to be caught via catch", () => {
		expect(() => {
			throw new DownloadException(DownloadExceptionCode.DOWNLOAD_REQUEST_ERROR, 404)
		}).toThrow(DownloadException)
	})
})





