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
			// 不在收集命令阶段，但内容中有命令标记，则替换为指令数据收集中
			while (processedContent.includes("<!-- COMMAND_START -->")) {
				const startIndex = processedContent.indexOf("<!-- COMMAND_START -->")
				let endIndex

				// 查找结束标记或下一个状态标记
				const endTagIndex = processedContent.indexOf("<!-- COMMAND_END -->", startIndex)
				const statusIndex = processedContent.indexOf("<!-- STATUS_START -->", startIndex)
				const nextCommandIndex = processedContent.indexOf(
					"<!-- COMMAND_START -->",
					startIndex + 1,
				)

				if (endTagIndex > -1) {
					// 找到结束标记
					endIndex = endTagIndex + "<!-- COMMAND_END -->".length
				} else if (
					statusIndex > -1 &&
					(nextCommandIndex === -1 || statusIndex < nextCommandIndex)
				) {
					// 找到状态标记
					endIndex = statusIndex
				} else if (nextCommandIndex > -1) {
					// 找到下一个命令标记
					endIndex = nextCommandIndex
				} else {
					// 都没找到，尝试查找JSON结构
					const jsonStartIndex = processedContent.indexOf("{", startIndex)
					if (jsonStartIndex > -1) {
						// 查找匹配的最后一个}
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
						// 没找到JSON，使用命令标记后的所有内容
						endIndex = processedContent.length
					}
				}

				// 替换命令部分为指令数据收集中
				if (endIndex > startIndex) {
					processedContent = `${processedContent.substring(
						0,
						startIndex,
					)}指令数据收集中${processedContent.substring(endIndex)}`
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
