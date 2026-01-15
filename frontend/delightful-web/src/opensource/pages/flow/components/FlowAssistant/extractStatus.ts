/**
 * Extract status information and replace status markers
 * @param content Original content
 * @returns Processed content
 */
export const extractStatus = (content: string): string => {
	let updatedContent = content
	// Add debug logs
	console.log("Processing status markers, original content length:", content.length)

	// Regular expression for status markers
	const statusRegex = /<!-- STATUS_START -->([\s\S]*?)<!-- STATUS_END -->/g

	// Check if content includes status markers
	const hasStartTag = content.includes("<!-- STATUS_START -->")
	const hasEndTag = content.includes("<!-- STATUS_END -->")
	console.log("Status marker check:", { hasStartTag, hasEndTag })

	// Handle complete status markers
	let statusMatch
	while ((statusMatch = statusRegex.exec(content))) {
		const statusBlock = statusMatch[0] // Full match including markers
		const statusText = statusMatch[1].trim() // Status text only
		console.log("Extracted status text:", statusText)

		// Replace status block within the content
		updatedContent = updatedContent.replace(statusBlock, statusText)
	}

	// Handle incomplete status markers
	if (
		updatedContent.includes("<!-- STATUS_START -->") &&
		!updatedContent.includes("<!-- STATUS_END -->")
	) {
		console.log("Found incomplete status marker")
		const startIndex = updatedContent.indexOf("<!-- STATUS_START -->")
		const endIndex = updatedContent.length

		// Extract status text part and remove the marker
		const statusPart = updatedContent.substring(startIndex, endIndex)
		const statusTextPart = statusPart.replace("<!-- STATUS_START -->", "").trim()

		// Replace incomplete status block
		updatedContent = updatedContent.substring(0, startIndex) + statusTextPart
	}

	return updatedContent
}

export default extractStatus
