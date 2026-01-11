import type { JSONContent } from "@tiptap/core"

/**
 * Combine content into a doc node
 * @param content - The content
 * @returns The combined content
 */
export const combindContent = (content: string | undefined) => {
	return {
		type: "doc",
		content: [
			{
				type: "paragraph",
				content: content ? JSON.parse(content) : [],
			},
		],
	}
}

/** Extract required child nodes */
export const pickContent = (content: JSONContent) => {
	return content.content?.[0]?.content
}





