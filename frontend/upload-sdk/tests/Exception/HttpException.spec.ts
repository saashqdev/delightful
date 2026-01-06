import { describe, test, expect } from "vitest"
import { HttpException, HttpExceptionCode } from "../../src/Exception/HttpException"
import { BaseException } from "../../src/Exception/BaseException"

describe("HttpException", () => {
	test("should inherit from BaseException", () => {
		const exception = new HttpException(
			HttpExceptionCode.REQUEST_FAILED_WITH_STATUS_CODE,
			404
		)
		
		expect(exception).toBeInstanceOf(BaseException)
		expect(exception).toBeInstanceOf(HttpException)
	})

	test("REQUEST_FAILED_WITH_STATUS_CODE should contain HTTP status code", () => {
		const httpStatus = 500
		const exception = new HttpException(
			HttpExceptionCode.REQUEST_FAILED_WITH_STATUS_CODE,
			httpStatus
		)
		
		expect(exception.message).toContain("[Uploader]")
		expect(exception.message).toContain(httpStatus.toString())
		expect(exception.status).toBe(httpStatus)
	})

	test("REQUEST_IS_CANCEL should generate correct error message", () => {
		const exception = new HttpException(HttpExceptionCode.REQUEST_IS_CANCEL)
		
		expect(exception.message).toContain("[Uploader]")
		expect(exception.message).toContain("canceled")
		expect(exception.status).toBe(5001)
	})

	test("REQUEST_IS_PAUSE should generate correct error message", () => {
		const exception = new HttpException(HttpExceptionCode.REQUEST_IS_PAUSE)
		
		expect(exception.message).toContain("[Uploader]")
		expect(exception.message).toContain("paused")
		expect(exception.status).toBe(5002)
	})

	test("should correctly handle different HTTP status codes", () => {
		const statuses = [400, 401, 403, 404, 500, 502, 503]
		
		statuses.forEach(httpStatus => {
			const exception = new HttpException(
				HttpExceptionCode.REQUEST_FAILED_WITH_STATUS_CODE,
				httpStatus
			)
			
			expect(exception.message).toContain(httpStatus.toString())
			expect(exception.status).toBe(httpStatus)
		})
	})

	test("exception code should be numeric", () => {
		const exception = new HttpException(
			HttpExceptionCode.REQUEST_FAILED_WITH_STATUS_CODE,
			404
		)
		
		expect(typeof exception.status).toBe("number")
	})

	test("different exceptions should have different status values", () => {
		const cancelException = new HttpException(HttpExceptionCode.REQUEST_IS_CANCEL)
		const pauseException = new HttpException(HttpExceptionCode.REQUEST_IS_PAUSE)
		
		expect(cancelException.status).not.toBe(pauseException.status)
	})

	test("should be able to be caught via catch", () => {
		expect(() => {
			throw new HttpException(HttpExceptionCode.REQUEST_FAILED_WITH_STATUS_CODE, 404)
		}).toThrow(HttpException)
	})
})





