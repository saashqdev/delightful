import type { StreamResponse } from "@/types/request"

/**
 * Slice a streaming message into smaller units for per-character output.
 * @param message Streaming message.
 * @returns Array of sliced streaming messages.
 */
export function sliceMessage(message: StreamResponse) {
	const { content, reasoning_content, llm_response } = message

	// Split a string into smaller units to enable character-by-character output
	const sliceString = (str: string) => {
		// For very short strings, return early
		if (!str || str.length === 0) {
			return []
		}

		if (str.length <= 1) {
			return [str]
		}

		// Split the string into single characters
		const characters: string[] = []

		// Iterate per character to ensure individual handling
		for (let i = 0; i < str.length; i++) {
			const char = str.charAt(i)
			characters.push(char)
		}

		return characters
	}

	switch (true) {
		case Boolean(content):
			return sliceString(content).map((item) => ({
				...message,
				content: item,
			}))
		case Boolean(llm_response):
			return sliceString(llm_response).map((item) => ({
				...message,
				llm_response: item,
			}))
		case Boolean(reasoning_content):
			return sliceString(reasoning_content).map((item) => ({
				...message,
				reasoning_content: item,
			}))
		default:
			return []
	}
}
