export function getStringSizeInBytes(str: string) {
	// Compute byte length using UTF-8 encoding
	const totalBytes = new Blob([str]).size

	// Convert bytes to KB
	const sizeInKB = totalBytes / 1024

	// Return the result
	return sizeInKB
}
