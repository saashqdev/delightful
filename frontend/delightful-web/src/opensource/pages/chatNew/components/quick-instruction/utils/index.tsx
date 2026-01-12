import type { JSONContent } from "@tiptap/core"
import { isArray, isObject } from "lodash-es"
import { ExtensionName } from "../extension/constants"

/**
 * Replace quick instruction node
 * @param content - Content
 * @param quickInstructionNodeAttrs - Quick instruction node
 * @returns Replaced content
 */
export function transformQuickInstruction(
	content: JSONContent | JSONContent[] | undefined,
	updateQuickInstructionNode: (content: JSONContent) => void,
) {
	if (!content) return content
	if (isArray(content)) {
		content.forEach((node) => {
			transformQuickInstruction(node, updateQuickInstructionNode)
		})
	} else if (isObject(content)) {
		if (content.type === ExtensionName) {
			updateQuickInstructionNode(content)
		}
		transformQuickInstruction(content.content, updateQuickInstructionNode)
	}
	return content
}

/**
 * Replace existing quick instruction
 * @param content - Content
 * @param quickInstructionName - Quick instruction name
 * @param quickInstructionValue - Quick instruction value
 * @returns Replaced content
 */
export function replaceExistQuickInstruction(
	content: JSONContent | JSONContent[] | undefined,
	compareFn: (attrs?: Record<string, unknown>) => boolean,
	newValue: string,
): boolean {
	let found = false

	// Handle array type
	if (Array.isArray(content)) {
		content.forEach((item) => {
			if (replaceExistQuickInstruction(item, compareFn, newValue)) {
				found = true
			}
		})
		return found
	}

	// Handle object type
	if (content?.type === ExtensionName && content.attrs && compareFn(content.attrs)) {
		content.attrs.value = newValue
		found = true
	}

	// Recursively handle child nodes
	if (content?.content && Array.isArray(content.content)) {
		content.content.forEach?.((child: JSONContent) => {
			if (replaceExistQuickInstruction(child, compareFn, newValue)) {
				found = true
			}
		})
	}

	return found
}
