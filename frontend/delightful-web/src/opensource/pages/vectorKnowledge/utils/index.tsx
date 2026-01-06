import { nanoid } from "nanoid"
import type { FileData } from "../types"

export function genFileData(file: File): FileData {
	return {
		id: nanoid(),
		name: file.name,
		file,
		status: "init",
		progress: 0,
	}
}

/**
 * Process escape characters in strings to preserve original user input
 * For example, when using "\n" as a separator, after JSON it becomes "\\n"; this function restores it to "\n"
 * @param {string} str - The string to process
 * @returns {string} The processed string
 */
export function processEscapeChars(str: string): string {
	if (!str) return str

	// Handle common escape characters
	return str.replace(/\\\\n/g, "\\n").replace(/\\\\t/g, "\\t").replace(/\\\\r/g, "\\r")
}

/**
 * Process escape characters for all delimiter fields in a configuration object
 * @param {object} config - Configuration object with normal and parent_child subobjects
 * @returns {object} The processed configuration object
 */
export function processConfigSeparators(config: any): any {
	const { normal, parent_child } = config

	// Copy object to avoid modifying the original
	const processedNormal = { ...normal }
	const processedParentChild = { ...parent_child }

	// Handle separators in normal
	if (processedNormal?.segment_rule?.separator) {
		processedNormal.segment_rule.separator = processEscapeChars(
			processedNormal.segment_rule.separator,
		)
	}

	// Handle separators in parent_child
	if (processedParentChild?.parent_segment_rule?.separator) {
		processedParentChild.parent_segment_rule.separator = processEscapeChars(
			processedParentChild.parent_segment_rule.separator,
		)
	}

	if (processedParentChild?.child_segment_rule?.separator) {
		processedParentChild.child_segment_rule.separator = processEscapeChars(
			processedParentChild.child_segment_rule.separator,
		)
	}

	return {
		...config,
		normal: processedNormal,
		parent_child: processedParentChild,
	}
}

/**
 * Get file name extension
 * Safely extract extension from filename, handling various edge cases
 * @param {string} fileName - File name
 * @returns {string} File extension (lowercase); returns empty string if no extension or extension doesn't match rules
 */
export function getFileExtension(fileName: string): string {
	if (!fileName || typeof fileName !== "string") {
		return ""
	}

	// Handle path separators to get actual filename
	const baseName = fileName.split(/[/\\]/).pop() || ""

	// Hidden files (starting with . but no other . ) don't have extensions
	if (/^\.[\w-]+$/.test(baseName)) {
		return ""
	}

	// Get part after the last dot
	const parts = baseName.split(".")

	// No extension if no . or only one . at the beginning (hidden file)
	if (parts.length <= 1 || (parts.length === 2 && parts[0] === "")) {
		return ""
	}

	const extension = parts.pop()?.toLowerCase() || ""

	// Only allow alphanumeric extensions
	if (!/^[a-z0-9]+$/.test(extension)) {
		return ""
	}

	return extension
}
