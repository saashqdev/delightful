import { resolveToString, resolveToArray } from "../src"

describe("es6-template-strings export module tests", () => {
	test("resolveToString should be a function", () => {
		expect(typeof resolveToString).toBe("function")
	})

	test("resolveToArray should be a function", () => {
		expect(typeof resolveToArray).toBe("function")
	})

	test("default export should be resolveToString", () => {
		expect(resolveToString).toBe(resolveToString)
	})
})
