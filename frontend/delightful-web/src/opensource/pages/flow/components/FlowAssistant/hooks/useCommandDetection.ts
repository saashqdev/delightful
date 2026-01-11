import { useCallback } from "react"

interface UseCommandDetectionResult {
	detectCommands: (content: string) => {
		updatedContent: string
		hasCommands: boolean
		commandCount: number
	}
	processCommandMarkers: (content: string, isCollectingCommand: boolean) => string
}

/**
 * Custom hook for detecting and handling command markers in message content
 */
export function useCommandDetection(): UseCommandDetectionResult {
	/**
	 * Detect command markers in message content
	 * @param content Message content
	 * @returns Processed content, whether commands exist, command count
	 */
	const detectCommands = useCallback((content: string) => {
		const updatedContent = content || ""
		let hasCommands = false

		// Find all command markers
		const commandStartRegex = /<!-- COMMAND_START -->/g
		const commandMatches = [...updatedContent.matchAll(commandStartRegex)]
		const commandCount = commandMatches.length

		hasCommands = commandCount > 0

		return {
			updatedContent,
			hasCommands,
			commandCount,
		}
	}, [])

	/**
	 * Process command markers and decide how to display content based on collection state
	 * @param content Original content
	 * @param isCollectingCommand Whether currently collecting commands
	 * @returns Processed content
	 */
	const processCommandMarkers = useCallback((content: string, isCollectingCommand: boolean) => {
		let processedContent = content || ""

		if (isCollectingCommand) {
			// During command collection phase, only show content before command markers
			const commandStartIndex = processedContent.indexOf("<!-- COMMAND_START -->")
			if (commandStartIndex >= 0) {
				processedContent = processedContent.substring(0, commandStartIndex)
			}
		} else if (processedContent.includes("<!-- COMMAND_START -->")) {
			// not in command collection phase, but if there is a command flag in the content, replace it with instruction data collecting
			while (processedContent.includes("<!-- COMMAND_START -->")) {
				const startIndex = processedContent.indexOf("<!-- COMMAND_START -->")
				let endIndex

				// find end flag or next status flag
				const endTagIndex = processedContent.indexOf("<!-- COMMAND_END -->", startIndex)
				const statusIndex = processedContent.indexOf("<!-- STATUS_START -->", startIndex)
				const nextCommandIndex = processedContent.indexOf(
					"<!-- COMMAND_START -->",
					startIndex + 1,
				)

				if (endTagIndex > -1) {
					// found end flag
					endIndex = endTagIndex + "<!-- COMMAND_END -->".length
				} else if (
					statusIndex > -1 &&
					(nextCommandIndex === -1 || statusIndex < nextCommandIndex)
				) {
					// found status flag
					endIndex = statusIndex
				} else if (nextCommandIndex > -1) {
					// found next command flag
					endIndex = nextCommandIndex
				} else {
					// none found, try to find JSON structure
					const jsonStartIndex = processedContent.indexOf("{", startIndex)
					if (jsonStartIndex > -1) {
						// find the last matching }
						let bracketCount = 1
						let i = jsonStartIndex + 1
						for (; i < processedContent.length; i += 1) {
							if (processedContent[i] === "{") bracketCount += 1
							else if (processedContent[i] === "}") {
								bracketCount -= 1
								if (bracketCount === 0) break
							}
						}
						endIndex = i + 1
					} else {
						// no JSON found, use all content after command flag
						endIndex = processedContent.length
					}
				}

				// replace command part with collecting command data
				if (endIndex > startIndex) {
					processedContent = `${processedContent.substring(
						0,
						startIndex,
					)}collecting command data${processedContent.substring(endIndex)}`
				}
			}
		}

		return processedContent
	}, [])

	return {
		detectCommands,
		processCommandMarkers,
	}
}

export default useCommandDetection





