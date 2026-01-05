/* eslint-disable no-template-curly-in-string */
import resolve from "../src/resolve"
import compile from "../src/compile"

describe("resolve function tests", () => {
	test("basic resolve functionality", () => {
		const compiled = compile("Hello, ${name}!")
		const result = resolve(compiled, { name: "World" })
		expect(result).toEqual([["Hello, ", "!"], "World"])
	})

	test("handles multiple variables", () => {
		const compiled = compile("${greeting}, ${name}! Your age is ${age}.")
		const result = resolve(compiled, { greeting: "Hello", name: "John", age: 30 })
		expect(result).toEqual([["", ", ", "! Your age is ", "."], "Hello", "John", 30])
	})

	test("handles complex expressions", () => {
		const compiled = compile("Result: ${a + b * (c - d)}")
		const result = resolve(compiled, { a: 5, b: 3, c: 8, d: 2 })
		expect(result).toEqual([["Result: ", ""], 23]) // 5 + 3 * (8 - 2) = 5 + 3 * 6 = 5 + 18 = 23
	})

	test("handles object properties", () => {
		const compiled = compile("User: ${user.name}, Age: ${user.age}")
		const result = resolve(compiled, { user: { name: "Alice", age: 25 } })
		expect(result).toEqual([["User: ", ", Age: ", ""], "Alice", 25])
	})

	test("handles array indexes", () => {
		const compiled = compile("First: ${items[0]}, Second: ${items[1]}")
		const result = resolve(compiled, { items: ["apple", "banana"] })
		expect(result).toEqual([["First: ", ", Second: ", ""], "apple", "banana"])
	})

	test("handles function calls", () => {
		const compiled = compile("Uppercase: ${name.toUpperCase()}")
		const result = resolve(compiled, { name: "john" })
		expect(result).toEqual([["Uppercase: ", ""], "JOHN"])
	})

	test("handles missing variables (non-partial)", () => {
		const compiled = compile("Name: ${name}, Age: ${age}")
		const result = resolve(compiled, { name: "John" })
		expect(result).toEqual([["Name: ", ", Age: ", ""], "John", "undefined"])
	})

	test("handles missing variables (partial)", () => {
		const compiled = compile("Name: ${name}, Age: ${age}")
		const result = resolve(compiled, { name: "John" }, { partial: true })
		expect(result).toEqual([["Name: ", ", Age: ", ""], "John", "${age}"])
	})

	test("handles empty context", () => {
		const compiled = compile("Hello, ${name}!")
		const result = resolve(compiled, {})
		expect(result).toEqual([["Hello, ", "!"], "undefined"])
	})

	test("error handling", () => {
		const compiled = compile("Result: ${(() => { throw new Error('Test error') })()}")
		const result = resolve(compiled, {})
		expect(result).toEqual([["Result: ", ""], "undefined"])
	})

	test("error handling (partial)", () => {
		const compiled = compile("Result: ${(() => { throw new Error('Test error') })()}")
		const result = resolve(compiled, {}, { partial: true })
		expect(result).toEqual([["Result: ", ""], "${(() => { throw new Error('Test error') })()}"])
	})
})
