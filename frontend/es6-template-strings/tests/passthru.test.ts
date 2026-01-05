import passthru from "../src/passthru"
import passthruArray from "../src/passthru-array"

describe("passthru function tests", () => {
	test("basic string template handling", () => {
		const result = passthru(["Hello, ", "!"], "World")
		expect(result).toBe("Hello, World!")
	})

	test("handles multiple substitutions", () => {
		const result = passthru(["", " loves ", "."], "Alice", "programming")
		expect(result).toBe("Alice loves programming.")
	})

	test("handles empty substitutions", () => {
		const result = passthru(["Hello, ", "!", " Welcome."], "", "")
		expect(result).toBe("Hello, ! Welcome.")
	})

	test("handles undefined substitutions", () => {
		const result = passthru(["Hello, ", "!"], "" as any)
		expect(result).toBe("Hello, !")
	})

	test("handles null substitutions", () => {
		const result = passthru(["Hello, ", "!"], null as any)
		expect(result).toBe("Hello, null!")
	})

	test("handles complex substitution values", () => {
		const fn = () => "World"
		const result = passthru(["Hello, ", "!"], fn as any)
		expect(result).toMatch(/Hello, function.*World.*!/)
	})
})

describe("passthru-array function tests", () => {
	test("basic array handling", () => {
		const result = passthruArray(["Hello, ", "!"], "World")
		expect(result).toEqual(["Hello, ", "World", "!"])
	})

	test("handles multiple substitutions", () => {
		const result = passthruArray(["", " loves ", "."], "Alice", "programming")
		expect(result).toEqual(["", "Alice", " loves ", "programming", "."])
	})

	test("handles empty substitutions", () => {
		const result = passthruArray(["Hello, ", "!", " Welcome."], "", "")
		expect(result).toEqual(["Hello, ", "", "!", "", " Welcome."])
	})

	test("handles undefined substitutions", () => {
		const result = passthruArray(["Hello, ", "!"], "" as any)
		expect(result).toEqual(["Hello, ", "", "!"])
	})

	test("handles empty literal array", () => {
		const result = passthruArray([], "World")
		expect(result).toEqual([])
	})

	test("handles edge cases", () => {
		const result = passthruArray(["Only literal"])
		expect(result).toEqual(["Only literal"])
	})
})
