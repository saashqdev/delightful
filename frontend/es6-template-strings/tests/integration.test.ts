/* eslint-disable no-template-curly-in-string */
import { resolveToString, resolveToArray } from "../src/index"

describe("es6-template-strings integration tests", () => {
	test("resolveToString basic functionality", () => {
		const result = resolveToString("Hello, ${name}!", { name: "World" })
		expect(result).toBe("Hello, World!")
	})

	test("resolveToString handles complex expressions", () => {
		const result = resolveToString("Total: ${price * quantity}", { price: 10, quantity: 3 })
		expect(result).toBe("Total: 30")
	})

	test("resolveToString handles object properties", () => {
		const result = resolveToString("User: ${user.name}, Age: ${user.age}", {
			user: { name: "Alice", age: 25 },
		})
		expect(result).toBe("User: Alice, Age: 25")
	})

	test("resolveToString missing variables (non-partial)", () => {
		const result = resolveToString("Name: ${name}, Age: ${age}", { name: "John" })
		expect(result).toBe("Name: John, Age: undefined")
	})

	test("resolveToString missing variables (partial)", () => {
		const result = resolveToString(
			"Name: ${name}, Age: ${age}",
			{ name: "John" },
			{ partial: true },
		)
		expect(result).toBe("Name: John, Age: ${age}")
	})

	test("resolveToString with custom notation", () => {
		const result = resolveToString(
			"Hello, @{name}!",
			{ name: "World" },
			{
				notation: "@",
				notationStart: "{",
				notationEnd: "}",
			},
		)
		expect(result).toBe("Hello, World!")
	})

	test("resolveToArray basic functionality", () => {
		const result = resolveToArray("Hello, ${name}!", { name: "World" })
		expect(result).toEqual(["Hello, ", "World", "!"])
	})

	test("resolveToArray handles complex expressions", () => {
		const result = resolveToArray("Total: ${price * quantity}", { price: 10, quantity: 3 })
		expect(result).toEqual(["Total: ", 30, ""])
	})

	test("resolveToArray handles object properties", () => {
		const result = resolveToArray("User: ${user.name}, Age: ${user.age}", {
			user: { name: "Alice", age: 25 },
		})
		expect(result).toEqual(["User: ", "Alice", ", Age: ", 25, ""])
	})

	test("resolveToArray missing variables (non-partial)", () => {
		const result = resolveToArray("Name: ${name}, Age: ${age}", { name: "John" })
		expect(result).toEqual(["Name: ", "John", ", Age: ", "undefined", ""])
	})

	test("resolveToArray missing variables (partial)", () => {
		const result = resolveToArray(
			"Name: ${name}, Age: ${age}",
			{ name: "John" },
			{ partial: true },
		)
		expect(result).toEqual(["Name: ", "John", ", Age: ", "${age}", ""])
	})

	test("resolveToArray with custom notation", () => {
		const result = resolveToArray(
			"Hello, @{name}!",
			{ name: "World" },
			{
				notation: "@",
				notationStart: "{",
				notationEnd: "}",
			},
		)
		expect(result).toEqual(["Hello, ", "World", "!"])
	})
})
