import { describe, test, expect } from "vitest"
import { BaseException } from "../../src/Exception/BaseException"

describe("BaseException", () => {
	test("should create base exception instance", () => {
		const exception = new BaseException("Test error message")
		
		expect(exception).toBeInstanceOf(Error)
		expect(exception).toBeInstanceOf(BaseException)
	})

	test("exception message should contain [Uploader] prefix", () => {
		const message = "Test error message"
		const exception = new BaseException(message)
		
		expect(exception.message).toBe(`[Uploader] ${message}`)
	})

	test("exception message should correctly handle empty string", () => {
		const exception = new BaseException("")
		
		expect(exception.message).toBe("[Uploader] ")
	})

	test("exception should have correct name", () => {
		const exception = new BaseException("Test error")
		
		expect(exception.name).toBe("Error")
	})

	test("exception should be catchable", () => {
		expect(() => {
			throw new BaseException("Test error")
		}).toThrow(BaseException)
	})

	test("exception should preserve error stack", () => {
		const exception = new BaseException("Test error")
		
		expect(exception.stack).toBeDefined()
		expect(exception.stack).toContain("BaseException")
	})
})

