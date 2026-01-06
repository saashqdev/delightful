const map = new Map<string, string>()

export function textToBackgroundColor(name?: string) {
	if (!name) return "#ffffff"

	let hash = 0
	for (let i = 0; i < name.length; i += 1) {
		hash += name.charCodeAt(i)
	}
	const hue = (hash % 360) + 10 // Keep hue in 10–360 range
	const saturation = 60 + (hash % 20) // Keep saturation in 60–80 range
	const lightness = 40 + (hash % 20) // Keep lightness in 40–60 range

	// Ensure the generated color stays soft and comfortable
	return `hsl(${hue}, ${saturation}%, ${lightness}%)`
}

export function textToDisplayName(name?: string) {
	if (!name) return ""
	// Detect whether the name is Chinese or English
	const isChinese = name.match(/[\u4e00-\u9fa5]/)
	if (isChinese) {
		// Take the last two valid Chinese characters, excluding punctuation
		return name.replace(/[^\u4e00-\u9fa5]/g, "").slice(-2)
	}
	// Take the first uppercase letter of an English name
	return name[0]?.toUpperCase() ?? ""
}

export function textToTextColor(name?: string) {
	if (!name) return ""
	// Detect whether the name is Chinese or English
	const isChinese = name.match(/[\u4e00-\u9fa5]/)
	if (isChinese) {
		// Take the last two Chinese characters
		return name.slice(-2)
	}
	// Take the first uppercase letter of an English name
	return name[0]?.toUpperCase() ?? ""
}

/**
	 * Draw a text avatar
	 * @param text Text content
	 * @returns Avatar image
 */
export const drawTextAvatar = (
	text: string,
	bgColor: string | undefined,
	textColor: string | undefined,
): string | null => {
	// Check whether it exists in cache
	const cached = map.get(text)
	if (cached) {
		return cached
	}

	// Create a Canvas element
	const canvas = document.createElement("canvas")
	const size = 200 // High resolution to ensure quality
	canvas.width = size
	canvas.height = size
	const ctx = canvas.getContext("2d")

	if (!ctx) {
		return null
	}

	// Set background color
	const backgroundColor = bgColor ?? textToBackgroundColor(text)
	ctx.fillStyle = backgroundColor
	ctx.fillRect(0, 0, size, size)

	// Determine display text
	const displayText = textToDisplayName(text)

	// Set text style
	ctx.fillStyle = textColor ?? "#FFFFFF" // Use white text color
	ctx.textAlign = "center"
	ctx.textBaseline = "middle"

	// Adjust font size based on display text length
	const fontSize = displayText.length > 1 ? size * 0.4 : size * 0.5
	ctx.font = `bold ${fontSize}px Arial, sans-serif`

	// Add text shadow to improve readability
	ctx.shadowColor = "rgba(0, 0, 0, 0.3)"
	ctx.shadowBlur = 4
	ctx.shadowOffsetX = 0
	ctx.shadowOffsetY = 1

	// Draw the text
	ctx.fillText(displayText, size / 2, size / 2 + 5)

	// Convert to base64
	const base64 = canvas.toDataURL("image/png")

	// Cache the result
	map.set(text, base64)

	return base64
}
