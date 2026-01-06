import { describe, test, expect } from "vitest"
import {
	isBlob,
	isBuffer,
	isFile,
	isFunction,
	isIP,
	isJson,
	isObject,
} from "../../src/utils/checkDataFormat"

describe("checkDataFormat utility function tests", () => {
	describe("isBlob function", () => {
		test("should correctly identify Blob objects", () => {
			const blob = new Blob(["test"])
			expect(isBlob(blob)).toBe(true)
		})

		test("should correctly reject non-Blob objects", () => {
			const nonBlob = { size: 4, type: "text/plain" }
			expect(isBlob(nonBlob)).toBe(false)
		})
	})

	describe("isBuffer function", () => {
		test("should correctly identify Buffer objects", () => {
			const buffer = Buffer.from("test")
			expect(isBuffer(buffer)).toBe(true)
		})

		test("should correctly reject non-Buffer objects", () => {
			const nonBuffer = Uint8Array.from([116, 101, 115, 116])
			expect(isBuffer(nonBuffer)).toBe(false)
		})
	})

	describe("isFile function", () => {
		test("should correctly identify File objects", () => {
			const file = new File(["test"], "test.txt")
			expect(isFile(file)).toBe(true)
		})

		test("should correctly reject non-File objects", () => {
			const nonFile = { name: "test.txt", size: 4 }
			expect(isFile(nonFile)).toBe(false)
		})
	})

	describe("isFunction function", () => {
		test("should correctly identify functions", () => {
			const fn = () => {}
			expect(isFunction(fn)).toBe(true)
		})

		test("should correctly reject non-functions", () => {
			const nonFn = {
				method: () => {},
			}
			expect(isFunction(nonFn)).toBe(false)
		})
	})

	describe("isIP function", () => {
		test("should correctly identify IPv4 addresses", () => {
			expect(isIP("192.168.1.1")).toBe(true)
			expect(isIP("255.255.255.255")).toBe(true)
			expect(isIP("0.0.0.0")).toBe(true)
		})

		test("should correctly reject invalid IPv4 addresses", () => {
			expect(isIP("256.0.0.1")).toBe(false)
			expect(isIP("192.168.1")).toBe(false)
			expect(isIP("192.168.1.1.1")).toBe(false)
		})

		test("should correctly identify IPv6 addresses", () => {
			expect(isIP("2001:0db8:85a3:0000:0000:8a2e:0370:7334")).toBe(true)
			expect(isIP("::1")).toBe(true)
		})
	})

	describe("isJson function", () => {
		test("should correctly identify JSON strings", () => {
			expect(isJson('{"name":"test"}')).toBe(true)
			expect(isJson("[]")).toBe(true)
		})

		test("should correctly reject non-JSON strings", () => {
			expect(isJson("not a json")).toBe(false)
			expect(isJson("{name:test}")).toBe(false)
		})

		test("should correctly reject non-string inputs", () => {
			expect(isJson(123)).toBe(false)
			expect(isJson(null)).toBe(false)
			expect(isJson(undefined)).toBe(false)
			expect(isJson({ name: "test" })).toBe(false)
		})
	})

	describe("isObject function", () => {
		test("should correctly identify Object objects", () => {
			expect(isObject({})).toBe(true)
			expect(isObject({ name: "test" })).toBe(true)
		})

		test("should correctly reject non-Object objects", () => {
			expect(isObject([])).toBe(false)
			expect(isObject(null)).toBe(false)
			expect(isObject(undefined)).toBe(false)
			expect(isObject("string")).toBe(false)
			expect(isObject(123)).toBe(false)
		})
	})
})




