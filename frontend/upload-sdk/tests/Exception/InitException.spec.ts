import { describe, test, expect } from "vitest"
import { InitException, InitExceptionCode } from "../../src/Exception/InitException"
import { BaseException } from "../../src/Exception/BaseException"

describe("InitException", () => {
	test("should inherit from BaseException", () => {
		const exception = new InitException(InitExceptionCode.MISSING_PARAMS_FOR_UPLOAD, "param1")
		
		expect(exception).toBeInstanceOf(BaseException)
		expect(exception).toBeInstanceOf(InitException)
	})

	test("MISSING_PARAMS_FOR_UPLOAD should generate correct error message", () => {
		const exception = new InitException(
			InitExceptionCode.MISSING_PARAMS_FOR_UPLOAD,
			"url",
			"method"
		)
		
		expect(exception.message).toContain("[Uploader]")
		expect(exception.message).toContain("url")
		expect(exception.message).toContain("method")
		expect(exception.message).toContain("must be provided")
	})

	test("UPLOAD_IS_NO_SUPPORT_THIS_PLATFORM should generate correct error message", () => {
		const platform = "unknown-platform"
		const exception = new InitException(
			InitExceptionCode.UPLOAD_IS_NO_SUPPORT_THIS_PLATFORM,
			platform
		)
		
		expect(exception.message).toContain(platform)
		expect(exception.message).toContain("not supported")
	})

	test("UPLOAD_IS_NO_SUPPORT_THIS_FILE_FORMAT should generate correct error message", () => {
		const exception = new InitException(
			InitExceptionCode.UPLOAD_IS_NO_SUPPORT_THIS_FILE_FORMAT
		)
		
		expect(exception.message).toContain("[Uploader]")
		expect(exception.message).toContain("Blob/File")
	})

	test("UPLOAD_FILENAME_EXIST_SPECIAL_CHAR should contain filename", () => {
		const filename = "file%name.txt"
		const exception = new InitException(
			InitExceptionCode.UPLOAD_FILENAME_EXIST_SPECIAL_CHAR,
			filename
		)
		
		expect(exception.message).toContain(filename)
		expect(exception.message).toContain("special characters")
	})

	test("REUPLOAD_IS_FAILED should generate correct error message", () => {
		const exception = new InitException(InitExceptionCode.REUPLOAD_IS_FAILED)
		
		expect(exception.message).toContain("[Uploader]")
		expect(exception.message).toContain("expired")
	})

	test("exception should have correct name", () => {
		const exception = new InitException(InitExceptionCode.MISSING_PARAMS_FOR_UPLOAD, "test")
		
		expect(exception.name).toBe("InitException")
	})

	test("should be able to be caught via catch", () => {
		expect(() => {
			throw new InitException(InitExceptionCode.MISSING_PARAMS_FOR_UPLOAD, "url")
		}).toThrow(InitException)
	})
})

