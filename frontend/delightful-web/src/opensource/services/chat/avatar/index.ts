import chatDb from "@/opensource/database/chat"
import AvatarStore from "@/opensource/stores/chatNew/avatar"
import { textToBackgroundColor, textToDisplayName } from "./utils"

class AvatarService {
	constructor() {
		// Initialize text avatar cache from DB
		chatDb
			.getTextAvatarTable()
			?.toArray()
			.then((res) => {
				AvatarStore.init(res)
			})
	}

	/**
	 * Draw a text-based avatar.
	 * @param text Source text
	 * @returns Avatar image (base64)
	 */
	drawTextAvatar(
		text: string,
		bgColor: string | undefined,
		textColor: string | undefined,
	): string | null {
		// Check cache first
		const cached = AvatarStore.getTextAvatar(text)
		if (cached) {
			return cached
		}

		// Create canvas element
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

		// Configure text style
		ctx.fillStyle = textColor ?? "#FFFFFF" // White text color
		ctx.textAlign = "center"
		ctx.textBaseline = "middle"

		// Adjust font size based on text length
		const fontSize = displayText.length > 1 ? size * 0.4 : size * 0.5
		ctx.font = `bold ${fontSize}px Arial, sans-serif`

		// Add text shadow to improve readability
		ctx.shadowColor = "rgba(0, 0, 0, 0.3)"
		ctx.shadowBlur = 4
		ctx.shadowOffsetX = 0
		ctx.shadowOffsetY = 1

		// Render text
		ctx.fillText(displayText, size / 2, size / 2 + 5)

		// Convert to base64
		const base64 = canvas.toDataURL("image/png")

		// Cache the result
		AvatarStore.setTextAvatar(text, base64)

		// Persist to DB
		chatDb.getTextAvatarTable()?.put({ text, base64 })

		return base64
	}
}

export default new AvatarService()
