import type { JSONContent } from "@tiptap/core"

/**
 * Add a doc wrapper
 * @param content
 * @returns
 */
export const addDocWrapper = (content: JSONContent[] = []) => {
	return {
		type: "doc",
		content,
	}
}
