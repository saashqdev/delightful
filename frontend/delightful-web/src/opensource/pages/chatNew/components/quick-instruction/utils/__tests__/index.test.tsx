import { describe, it, expect } from "vitest"
import type { JSONContent } from "@tiptap/core"
import { replaceExistQuickInstruction, transformQuickInstruction } from "../index"
import { ExtensionName } from "../../extension/constants"

describe("replaceQuickInstruction", () => {
	it("should replace quick instruction node at top level", () => {
		const content = {
			type: ExtensionName,
			attrs: { old: "value" },
		} as JSONContent
		const newAttrs = { new: "value" }

		const result = transformQuickInstruction(content, (c) => {
			c.attrs = newAttrs
		}) as JSONContent
		expect(result?.attrs).toEqual({ new: "value" })
	})

	it("should replace quick instruction node in array", () => {
		const content = [
			{
				type: ExtensionName,
				attrs: { old: "value" },
			},
			{
				type: "paragraph",
				content: "text",
			},
		] as JSONContent
		const newAttrs = { new: "value" }

		const result = transformQuickInstruction(content, (c) => {
			c.attrs = newAttrs
		}) as JSONContent[]
		expect(result?.[0].attrs).toEqual({ new: "value" })
		expect(result?.[1]).toEqual({ type: "paragraph", content: "text" })
	})

	it("should replace quick instruction node in nested object", () => {
		const content = {
			type: "doc",
			content: [
				{
					type: ExtensionName,
					attrs: { old: "value" },
				},
			],
		} as JSONContent
		const newAttrs = { new: "value" }

		const result = transformQuickInstruction(content, (c) => {
			c.attrs = newAttrs
		}) as JSONContent
		expect(result?.content?.[0].attrs).toEqual({ new: "value" })
	})

	it("should handle complex nested structure", () => {
		const content = {
			type: "doc",
			content: [
				{
					type: ExtensionName,
					attrs: { old: "value1" },
				},
				{
					type: "paragraph",
					content: [
						{
							type: ExtensionName,
							attrs: { old: "value2" },
						},
					],
				},
			],
		} as JSONContent
		const newAttrs = { new: "value" }

		const result = transformQuickInstruction(content, (c) => {
			c.attrs = newAttrs
		}) as JSONContent
		expect(result?.content?.[0].attrs).toEqual({ new: "value" })
		expect(result?.content?.[1].content?.[0].attrs).toEqual({ new: "value" })
	})
})

describe("replaceExistQuickInstruction", () => {
	it("should replace matched quick instruction at top level and return true", () => {
		const content = {
			type: ExtensionName,
			attrs: {
				instructionName: "testInstruction",
				value: "oldValue",
			},
		} as JSONContent

		const result = replaceExistQuickInstruction(
			content,
			(attrs) => attrs?.instructionName === "testInstruction",
			"newValue",
		)

		expect(result).toBe(true)
		expect(content.attrs).toEqual({
			instructionName: "testInstruction",
			value: "newValue",
		})
	})

	it("should replace matched quick instruction in array and return true", () => {
		const content = [
			{
				type: ExtensionName,
				attrs: {
					instructionName: "testInstruction",
					value: "oldValue",
				},
			},
			{
				type: "paragraph",
				content: [],
			},
		] as JSONContent

		const result = replaceExistQuickInstruction(
			content,
			(attrs) => attrs?.instructionName === "testInstruction",
			"newValue",
		)

		expect(result).toBe(true)
		expect(content[0].attrs).toEqual({
			instructionName: "testInstruction",
			value: "newValue",
		})
		expect(content[1]).toEqual({
			type: "paragraph",
			content: [],
		})
	})

	it("should replace all matched quick instructions in nested structure and return true", () => {
		const content = {
			type: "doc",
			content: [
				{
					type: ExtensionName,
					attrs: {
						instructionName: "testInstruction",
						value: "oldValue1",
					},
				},
				{
					type: "paragraph",
					content: [
						{
							type: ExtensionName,
							attrs: {
								instructionName: "testInstruction",
								value: "oldValue2",
							},
						},
					],
				},
			],
		} as JSONContent

		const result = replaceExistQuickInstruction(
			content,
			(attrs) => attrs?.instructionName === "testInstruction",
			"newValue",
		)

		expect(result).toBe(true)
		expect(content.content?.[0].attrs).toEqual({
			instructionName: "testInstruction",
			value: "newValue",
		})
		expect(content.content?.[1].content?.[0].attrs).toEqual({
			instructionName: "testInstruction",
			value: "newValue",
		})
	})

	it("should return false when no matching instruction found", () => {
		const content = {
			type: ExtensionName,
			attrs: {
				instructionName: "differentInstruction",
				value: "oldValue",
			},
		} as JSONContent

		const result = replaceExistQuickInstruction(
			content,
			(attrs) => attrs?.instructionName === "testInstruction",
			"newValue",
		)

		expect(result).toBe(false)
		expect(content.attrs?.value).toBe("oldValue")
	})

	it("should only replace specified quick instruction by name", () => {
		const content = [
			{
				type: ExtensionName,
				attrs: {
					instructionName: "instruction1",
					value: "value1",
				},
			},
			{
				type: ExtensionName,
				attrs: {
					instructionName: "instruction2",
					value: "value2",
				},
			},
		] as JSONContent

		const result = replaceExistQuickInstruction(
			content,
			(attrs) => attrs?.instructionName === "instruction1",
			"newValue",
		)

		expect(result).toBe(true)
		expect(content[0].attrs.value).toBe("newValue")
		expect(content[1].attrs.value).toBe("value2")
	})
})
