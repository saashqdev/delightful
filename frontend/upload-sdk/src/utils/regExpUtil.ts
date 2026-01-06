/**
 * @description: Extract the extension from a file path
 * @param {string} path File path
 * @return {string} File extension (with dot, e.g., ".mp4") or empty string if none
 */
export function parseExtname(path: string) {
	// First check if empty
	if (!path) return ""

	// Check whether the path ends with a slash or backslash
	if (path.endsWith("/") || path.endsWith("\\")) return ""

	// Get the part after the last / or \\
	const fileName = path.split(/[/\\]/).pop() || ""

	// If the filename starts with a dot and has no other dots, return the whole name
	if (fileName.startsWith(".") && fileName.indexOf(".", 1) === -1) {
		return fileName
	}

	// Find the part after the last dot
	const lastDotIndex = fileName.lastIndexOf(".")
	if (lastDotIndex === -1) return "" // No extension

	return fileName.substring(lastDotIndex)
}




