/**
 * Stream processing related utility functions
 */

/**
 * Extract content from SSE data line
 * Format: data:{"id":"xyz","event":"message","message":{"role":"assistant","content":"content"}}
 */
export const extractContent = (
	line: string,
): { content: string; isError: boolean; errorInfo: string } => {
	if (!line.startsWith("data:") || line === "data:[DONE]")
		return { content: "", isError: false, errorInfo: "" }

	try {
		const jsonStr = line.substring(5).trim()
		const data = JSON.parse(jsonStr)

		// check if is error message
		if (data.event === "error" && data.error_information) {
			return {
				content: "",
				isError: true,
				errorInfo: data.error_information,
			}
		}

		// try to extract content from various possible locations
		let content = ""

		if (data.message?.content) {
			content = data.message.content
		} else if (data.type === "message" && data.content) {
			content = data.content
		} else if (typeof data.content === "string") {
			content = data.content
		}

		// key change: do not process content in any way, keep as is
		// especially do not process quotes and special characters to avoid breaking JSON structure
		// because content may be chunked JSON fragments

		return { content, isError: false, errorInfo: "" }
	} catch (error) {
		console.error("failed to parse SSE data line:", error, "raw line:", line)
		return { content: "", isError: false, errorInfo: "" }
	}
}

/**
 * normalize JSON string, process multiline formatted JSON
 * @param json raw JSON string
 * @returns normalized JSON string
 */
export const normalizeJson = (json: string): string => {
	// if input is single line, return directly
	if (!json.includes("\n")) return json

	try {
		// try parsing and reserializing to automatically process format issues
		const parsed = JSON.parse(json)
		return JSON.stringify(parsed)
	} catch (e) {
		console.log("JSON parsing failed, trying manual normalization:", e)

		// manually clean up format
		const normalized = json
			// remove comments
			.replace(/\/\/.*$/gm, "")
			// remove leading and trailing whitespace
			.replace(/^\s+|\s+$/gm, "")
			// replace multiple spaces with single space
			.replace(/\s+/g, " ")

		return normalized
	}
}

/**
 * fix JSON property names to ensure double quotes are used
 * @param json JSON string
 * @returns fixed JSON string
 */
export const fixJsonPropertyNames = (json: string): string => {
	// fix common JSON property name cases without quotes or using single quotes
	// match: property name preceded by { or , followed by colon
	return (
		json
			// change unquoted property names to double-quoted
			.replace(/([{,]\s*)([a-zA-Z0-9_$]+)(\s*:)/g, '$1"$2"$3')
			// change single-quoted property names to double-quoted
			.replace(/([{,]\s*)'([^']+)'(\s*:)/g, '$1"$2"$3')
	)
}

/**
 * verify if braces in JSON string are balanced
 * @param json JSON string
 * @returns 0 means balanced, positive means extra right braces, negative means missing right braces
 */
export const validateJsonBrackets = (json: string): number => {
	let balance = 0
	let inString = false
	let escapeNext = false

	for (let i = 0; i < json.length; i += 1) {
		const char = json[i]

		// handle quotes and escapes in string
		if (char === '"' && !escapeNext) {
			inString = !inString
		} else if (char === "\\" && !escapeNext) {
			escapeNext = true

			continue
		}

		escapeNext = false

		// only count braces outside of strings
		if (!inString) {
			if (char === "{") balance += 1
			if (char === "}") balance -= 1
		}
	}

	return balance * -1 // return 0 for balanced, negative for missing right braces, positive for extra right braces
}

/**
 * remove extra right braces
 */
export const removeExtraBrackets = (json: string, count: number): string => {
	let result = json
	// remove specified number of right braces from back
	for (let i = 0; i < count; i += 1) {
		const lastBracketIndex = result.lastIndexOf("}")
		if (lastBracketIndex !== -1) {
			result = result.substring(0, lastBracketIndex) + result.substring(lastBracketIndex + 1)
		}
	}
	return result
}

/**
 * add missing right braces
 */
export const addMissingBrackets = (json: string, count: number): string => {
	let result = json
	// add specified number of right braces at end
	for (let i = 0; i < count; i += 1) {
		result += "}"
	}
	return result
}

/**
 * try to fix and parse JSON
 * @param jsonStr JSON string
 * @returns parsed JSON object
 */
export const tryParseAndFixJSON = (jsonStr: string): any => {
	// first try direct parsing
	try {
		return JSON.parse(jsonStr)
	} catch (initialError) {
		console.log("initial JSON parsing failed, trying to fix:", initialError)
	}

	// try to normalize JSON
	let attemptStr = normalizeJson(jsonStr)

	try {
		return JSON.parse(attemptStr)
	} catch (normalizeError) {
		console.log("JSON parsing failed after normalization, continuing to fix:", normalizeError)
	}

	// check and fix bracket balance
	const balance = validateJsonBrackets(attemptStr)
	if (balance !== 0) {
		console.log(`detected JSON brackets not balanced, difference: ${balance}`)
		if (balance > 0) {
			attemptStr = removeExtraBrackets(attemptStr, balance)
		} else if (balance < 0) {
			attemptStr = addMissingBrackets(attemptStr, Math.abs(balance))
		}
	}

	// fix common formatting issues
	attemptStr = attemptStr.replace(/,\s*}/g, "}").replace(/,\s*]/, "]")
	attemptStr = fixJsonPropertyNames(attemptStr)

	// try parsing again
	try {
		return JSON.parse(attemptStr)
	} catch (fixedError) {
		console.log("JSON parsing still failed after fix:", fixedError)

		// last attempt: fix common errors in nested structures
		attemptStr = attemptStr
			// fix properties missing values like "key": ,
			.replace(/:\s*,/g, ": null,")
			// fix properties with missing values at end like "key":
			.replace(/:\s*}/g, ": null}")
			// fix extra commas like [1,2,]
			.replace(/,\s*]/g, "]")

		try {
			return JSON.parse(attemptStr)
		} catch (error) {
			// all attempts failed, throw original error
			console.error("all JSON fix attempts failed:", error)
			throw new Error(
				`cannot parse JSON: ${error instanceof Error ? error.message : String(error)}`,
			)
		}
	}
}

/**
 * extract commands from HTML comment tags
 */
export const extractCommands = (content: string): { updatedContent: string; commands: any[] } => {
	let updatedContent = content
	const commands: any[] = []

	// add debug log to view raw content
	console.log("raw content:", content)

	// handle complete command tag cases - update regex to support multiline content
	const commandRegex = /<!-- COMMAND_START -->([\s\S]*?)<!-- COMMAND_END -->/g
	let commandMatch

	// check if command tags exist
	const hasCommandStart = content.includes("<!-- COMMAND_START -->")
	const hasCommandEnd = content.includes("<!-- COMMAND_END -->")

	console.log("command tag check:", { hasCommandStart, hasCommandEnd })

	while ((commandMatch = commandRegex.exec(content))) {
		try {
			const fullMatch = commandMatch[0] // complete match, including tags
			const commandJson = commandMatch[1].trim()
			console.log("extracted command JSON:", commandJson)

			// use enhanced JSON parsing and fix method
			let command
			try {
				command = tryParseAndFixJSON(commandJson)
				console.log("successfully parsed command:", command)
			} catch (parseError) {
				console.error("all parsing attempts failed:", parseError)
				// skip this command and process next one

				continue
			}

			// check if it is a confirmation operation command
			if (command.type === "confirmOperation") {
				console.log("found confirmation operation command:", command)
				// replace text with confirmation prompt instead of"collecting command data"
				const confirmMessage =
					command.message || "please confirm if you want to execute this operation?"
				updatedContent = updatedContent.replace(fullMatch, `${confirmMessage}`)

				// specially mark confirmation operation command
				command.isConfirmationCommand = true
			} else {
				// other command types use normal replacement
				updatedContent = updatedContent.replace(fullMatch, "collecting command data...")
			}

			commands.push(command)
			console.log("content after replacement:", updatedContent)
		} catch (error) {
			console.error("failed to process command:", error, "raw command:", commandMatch[1])
		}
	}

	// check if command tags still exist after replacement
	const stillHasCommandStart = updatedContent.includes("<!-- COMMAND_START -->")
	console.log("command start tag still exists after replacement:", stillHasCommandStart)

	//  info try to process incomplete command
	if (stillHasCommandStart) {
		console.log("try to process incomplete command")
		// find command start position
		const startIndex = updatedContent.indexOf("<!-- COMMAND_START -->")
		// find next possible boundary (STATUS_START or text end)
		const statusIndex = updatedContent.indexOf("<!-- STATUS_START -->", startIndex)
		const endIndex = statusIndex > -1 ? statusIndex : updatedContent.length

		// extract command part and replace
		const commandPart = updatedContent.substring(startIndex, endIndex)
		updatedContent = updatedContent.replace(commandPart, "collecting command data")
		console.log("content after processing incomplete command:", updatedContent)
	}

	return { updatedContent, commands }
}

/**
 * extract status information
 */
export const extractStatusInline = (content: string): string => {
	let updatedContent = content
	const statusRegex = /<!-- STATUS_START -->([\s\S]*?)<!-- STATUS_END -->/g
	let statusMatch

	while ((statusMatch = statusRegex.exec(content))) {
		// remove status part from displayed content
		updatedContent = updatedContent.replace(statusMatch[0], statusMatch[1].trim())

		// record status update
		const statusText = statusMatch[1].trim()
		console.log("status update:", statusText)
	}

	return updatedContent
}
