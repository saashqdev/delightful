import { describe, expect, it } from "vitest"
import { appendObject } from "../utils"

describe("StreamMessageApplyServiceV2 utils", () => {
	describe("appendObject", () => {
		// Basic case - append value to a regular object
		it("should append value to an existing property", () => {
			const obj = { a: { b: { c: "hello" } } }
			const result = appendObject(obj, ["a", "b", "c"], " world")
			expect(result).toEqual({ a: { b: { c: "hello world" } } })
		})

		// Empty case - when a key in the path does not exist
		it("should create missing properties in the path", () => {
			const obj = { a: {} }
			const result = appendObject(obj, ["a", "b", "c"], "value")
			expect(result).toEqual({ a: { b: { c: "value" } } })
		})

		it("should handle completely empty object", () => {
			const obj = {}
			const result = appendObject(obj, ["a", "b", "c"], "value")
			expect(result).toEqual({ a: { b: { c: "value" } } })
		})

		// Array case - handle array indices
		it("should create array if next key is numeric", () => {
			const obj = {}
			const result = appendObject(obj, ["data", "0", "name"], "John")
			expect(result).toEqual({ data: [{ name: "John" }] })
		})

		it("should append to an array if property is an array", () => {
			const obj = { users: [] }
			const result = appendObject(obj, ["users"], "John")
			expect(result).toEqual({ users: ["John"] })
		})

		it("should push to array if property exists and is an array", () => {
			const obj = { users: ["Alice"] }
			const result = appendObject(obj, ["users"], "Bob")
			expect(result).toEqual({ users: ["Alice", "Bob"] })
		})

		// Edge cases
		it("should return original object if keyPath is empty", () => {
			const obj = { a: 1 }
			const result = appendObject(obj, [], "value")
			expect(result).toBe(obj)
		})

		it("should return original object if object is null or undefined", () => {
			expect(appendObject(null, ["a", "b"], "value")).toBeNull()
			expect(appendObject(undefined, ["a", "b"], "value")).toBeUndefined()
		})

		it("should return original object if keyPath is null or undefined", () => {
			const obj = { a: 1 }
			expect(appendObject(obj, null as any, "value")).toBe(obj)
			expect(appendObject(obj, undefined as any, "value")).toBe(obj)
		})

		// Non-string appendValue: assign directly instead of concatenating
		it("should directly assign non-string appendValue instead of concatenating", () => {
			// Number type
			const objNumber = { value: 10 }
			const resultNumber = appendObject(objNumber, ["value"], 20)
			expect(resultNumber).toEqual({ value: 20 })

			// Object type
			const objObject = { config: { version: "1.0" } }
			const newConfig = { version: "2.0", features: ["a", "b"] }
			const resultObject = appendObject(objObject, ["config"], newConfig)
			expect(resultObject).toEqual({ config: newConfig })

			// Boolean type
			const objBoolean = { isActive: false }
			const resultBoolean = appendObject(objBoolean, ["isActive"], true)
			expect(resultBoolean).toEqual({ isActive: true })
		})

		// Complex case - deep nesting and mixed types
		it("should handle complex nested objects with arrays", () => {
			const obj = {
				users: [{ name: "Alice", scores: [10] }],
			}

			// First operation - push value into array
			const result1 = appendObject(obj, ["users", "0", "scores"], 20)
			expect(result1).toEqual({
				users: [{ name: "Alice", scores: [10, 20] }],
			})

			// Deeper nested path - use the original object (not previous result)
			const originalObj = {
				users: [{ name: "Alice", scores: [10] }],
			}
			const result2 = appendObject(
				originalObj,
				["users", "0", "details", "address", "city"],
				"New York",
			)
			expect(result2).toEqual({
				users: [
					{
						name: "Alice",
						scores: [10],
						details: {
							address: {
								city: "New York",
							},
						},
					},
				],
			})
		})
	})
})
