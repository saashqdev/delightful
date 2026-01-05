import { describe, test, expect } from "vitest"
import { UploadException, UploadExceptionCode } from "../../src/Exception/UploadException"
import { BaseException } from "../../src/Exception/BaseException"

describe("UploadException", () => {
	test("should inherit from BaseException", () => {
		const exception = new UploadException(UploadExceptionCode.UPLOAD_CANCEL)
		
		expect(exception).toBeInstanceOf(BaseException)
		expect(exception).toBeInstanceOf(UploadException)
	})

	test("UPLOAD_CANCEL should generate correct error message", () => {
		const exception = new UploadException(UploadExceptionCode.UPLOAD_CANCEL)
		
		expect(exception.message).toContain("[Uploader]")
		expect(exception.message).toContain("isCancel")
		expect(exception.status).toBe(1001)
	})

	test("UPLOAD_PAUSE should generate correct error message", () => {
		const exception = new UploadException(UploadExceptionCode.UPLOAD_PAUSE)
		
		expect(exception.message).toContain("[Uploader]")
		expect(exception.message).toContain("isPause")
		expect(exception.status).toBe(1002)
	})

	test("UPLOAD_UNKNOWN_ERROR should generate correct error message", () => {
		const exception = new UploadException(UploadExceptionCode.UPLOAD_UNKNOWN_ERROR)
		
		expect(exception.message).toContain("[Uploader]")
		expect(exception.status).toBe(1000)
	})

	test("exception code should be numeric", () => {
		const exception = new UploadException(UploadExceptionCode.UPLOAD_CANCEL)
		
		expect(typeof exception.status).toBe("number")
	})

	test("different exceptions should have different status values", () => {
		const cancelException = new UploadException(UploadExceptionCode.UPLOAD_CANCEL)
		const pauseException = new UploadException(UploadExceptionCode.UPLOAD_PAUSE)
		
		expect(cancelException.status).not.toBe(pauseException.status)
	})

	test("should be able to be caught via catch", () => {
		expect(() => {
			throw new UploadException(UploadExceptionCode.UPLOAD_CANCEL)
		}).toThrow(UploadException)
	})
})

