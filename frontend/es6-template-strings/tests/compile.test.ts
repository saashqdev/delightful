/* eslint-disable no-template-curly-in-string */
import compile from "../src/compile"

describe("compile function tests", () => {
	test("basic template compilation", () => {
		const result = compile("Hello, ${name}!")
		expect(result).toHaveProperty("literals")
		expect(result).toHaveProperty("substitutions")
		expect(result.literals).toEqual(["Hello, ", "!"])
		expect(result.substitutions).toEqual(["name"])
	})

	test("template with no substitutions", () => {
		const result = compile("Hello, world!")
		expect(result.literals).toEqual(["Hello, world!"])
		expect(result.substitutions).toEqual([])
	})

	test("template with multiple substitutions", () => {
		const result = compile("${greeting}, ${name}! Your age is ${age}.")
		expect(result.literals).toEqual(["", ", ", "! Your age is ", "."])
		expect(result.substitutions).toEqual(["greeting", "name", "age"])
	})

	test("escape sequence handling", () => {
		const result = compile("Hello, \\${name}!")
		expect(result.literals).toEqual(["Hello, ${name}!"])
		expect(result.substitutions).toEqual([])
	})

	test("nested substitution handling", () => {
		const result = compile("Result: ${a + b * (c - d)}")
		expect(result.literals).toEqual(["Result: ", ""])
		expect(result.substitutions).toEqual(["a + b * (c - d)"])
	})

	test("double dollar handling", () => {
		const result = compile("Cost: $$${price}")
		expect(result.literals).toEqual(["Cost: $$", ""])
		expect(result.substitutions).toEqual(["price"])
	})

	test("custom notation options", () => {
		const result = compile("Hello, @{name}!", {
			notation: "@",
			notationStart: "{",
			notationEnd: "}",
		})
		expect(result.literals).toEqual(["Hello, ", "!"])
		expect(result.substitutions).toEqual(["name"])
	})

	test("unclosed placeholder handling", () => {
		const result = compile("Hello, ${name")
		expect(result.literals).toEqual(["Hello, ${name"])
		expect(result.substitutions).toEqual([])
	})

	test("expression containing whitespace", () => {
		const result = compile("${  a + b  }")
		expect(result.literals).toEqual(["", ""])
		expect(result.substitutions).toEqual(["  a + b  "])
	})
})
