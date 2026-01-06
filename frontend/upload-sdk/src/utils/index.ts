/**
 * Check if fileName contains special characters
 * @param fileName
 */
export function checkSpecialCharacters(fileName: string) {
	// Exists in actual implementation
	return fileName.includes("%")
}

/**
 * Get file extension
 * @param fileName
 */
export function getFileExtension(fileName: string): string {
	const lastDotIndex = fileName.lastIndexOf(".")
	if (lastDotIndex === -1) {
		return "" // No extension in filename
	}
	const extension = fileName.substring(lastDotIndex + 1)
	return extension.toLowerCase() // Return lowercase extension
}




