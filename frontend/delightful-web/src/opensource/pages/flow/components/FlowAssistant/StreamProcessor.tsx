// @ts-nocheck
import { useUpdateEffect } from "ahooks"
import type React from "react"
import { useEffect, useRef, useState, useCallback } from "react"
import { useTranslation } from "react-i18next"
import { extractStatus } from "./extractStatus"
import { extractContent, extractCommands } from "./utils/streamUtils"

interface StreamProcessorProps {
	responseBody: ReadableStream<Uint8Array> | null
	messageId: string
	onTextUpdate: (text: string) => void
	onCommandsReceived: (commands: any[]) => void
	onError: (error: string) => void
	onComplete: () => void
	userScrolling: boolean
	onCommandProcessingStatusChange?: (isProcessing: boolean) => void // New callback to notify parent component of command processing status
}

// Use function component declaration instead of React.FC generic
function StreamProcessor(props: StreamProcessorProps): React.ReactElement | null {
	const {
		responseBody,
		messageId,
		onTextUpdate,
		onCommandsReceived,
		onError,
		onComplete,
		userScrolling,
		onCommandProcessingStatusChange,
	} = props

	const { t } = useTranslation()
	const [isProcessing, setIsProcessing] = useState(false)
	const completeContentRef = useRef<string>("") // Store complete content
	const displayContentRef = useRef<string>("") // Store current display content
	const newContentBufferRef = useRef<string>("") // Store newly received but not yet displayed content
	const typingTimerRef = useRef<NodeJS.Timeout | null>(null)
	const processingCommandsRef = useRef<boolean>(false)
	const streamProcessingRef = useRef<boolean>(false) // New: Mark whether stream is being processed
	const currentStreamRef = useRef<ReadableStream<Uint8Array> | null>(null) // New: Record the stream currently being processed
	const readerRef = useRef<ReadableStreamDefaultReader<Uint8Array> | null>(null) // New: Save reader reference
	const responseBodyIdRef = useRef<string>("") // Used to uniquely identify each stream to avoid duplicate processing
	const commandBufferRef = useRef<string>("") // New: Store command accumulation buffer
	const isCollectingCommandRef = useRef<boolean>(false) // Mark whether command data is being collected
	const partialCommandStartRef = useRef<string>("") // New: Used to accumulate potentially split command start marker
	const partialCommandEndRef = useRef<string>("") // New: Used to accumulate potentially split command end marker
	const errorDetectedRef = useRef<boolean>(false) // New: Mark whether error has been detected
	const processedCommandsRef = useRef<Set<string>>(new Set()) // New: Record processed commands

	// Typewriter effect parameters
	const typingSpeedRef = useRef<number>(30) // Millisecond delay between characters
	const typingBatchSizeRef = useRef<number>(2) // Number of characters per update

	// Detect potentially split command markers
	const detectPartialMarker = useCallback(
		(text: string, marker: string, accumulatorRef: React.MutableRefObject<string>): boolean => {
			// Currently accumulated partial marker
			const accumulated = accumulatorRef.current + text

			// If current accumulated text contains complete marker
			if (accumulated.includes(marker)) {
				// Found marker and reset accumulator
				accumulatorRef.current = ""
				return true
			}

			// Check if there is a partial marker match
			for (let i = 1; i < marker.length; i += 1) {
				// Try substrings of different lengths to see if they match the beginning of the marker
				const potentialPartial = marker.substring(0, i)
				if (accumulated.endsWith(potentialPartial)) {
					// Found potential partial match, update accumulator
					accumulatorRef.current = potentialPartial
					return false
				}
			}

			// Check if text might be a middle part of the marker
			for (let i = 1; i < marker.length - 1; i += 1) {
				for (let j = i + 1; j <= marker.length; j += 1) {
					const middlePart = marker.substring(i, j)
					if (text === middlePart || accumulated.endsWith(middlePart)) {
						// Save current accumulated portion
						accumulatorRef.current = accumulated.substring(
							Math.max(0, accumulated.length - marker.length),
						)
						return false
					}
				}
			}

			// No match found, reset accumulator
			accumulatorRef.current = ""
			return false
		},
		[],
	)

	// New: Detect and process command collection start and end markers
	const processCommandMarkers = useCallback(
		(text: string): string => {
			const COMMAND_START = "<!-- COMMAND_START -->"
			const COMMAND_END = "<!-- COMMAND_END -->"

			// Detect complete markers or partial markers spanning multiple data lines
			const hasStartMarker =
				text.includes(COMMAND_START) ||
				detectPartialMarker(text, COMMAND_START, partialCommandStartRef)

			const hasEndMarker =
				text.includes(COMMAND_END) ||
				detectPartialMarker(text, COMMAND_END, partialCommandEndRef)

			// Process command start marker
			if (hasStartMarker && !isCollectingCommandRef.current) {
				isCollectingCommandRef.current = true
				// Notify parent component to start collecting commands
				if (onCommandProcessingStatusChange) {
					onCommandProcessingStatusChange(true)
				}
			console.log("Detected command start marker")
					onCommandProcessingStatusChange(false)
				}
			console.log("Detected command end marker")
				// During command collection phase, find the position of the last start marker
				const startPos = text.lastIndexOf(COMMAND_START)
				if (startPos >= 0) {
					// Only return content before the start marker
					return text.substring(0, startPos)
				}
			}

			return text
		},
		[detectPartialMarker, onCommandProcessingStatusChange],
	)

	// Check and process commands in buffer
	const processCommandBuffer = useCallback(() => {
		// Loop through and process all possible commands
		const buffer = commandBufferRef.current
		let commandsProcessed = false

		// Use regex to find all complete commands
		const commandRegex = /<!-- COMMAND_START -->\s*([\s\S]*?)\s*<!-- COMMAND_END -->/g
		let match = null
		let lastIndex = 0
		const commands: any[] = []

		// Find all complete commands
		// eslint-disable-next-line no-cond-assign
		while ((match = commandRegex.exec(buffer)) !== null) {
			try {
				const commandJson = match[1].trim()
				console.log("Attempting to parse command:", commandJson)
				const command = JSON.parse(commandJson)
				commands.push(command)
				lastIndex = match.index + match[0].length
				commandsProcessed = true

				// If previously collecting commands, and now found complete command, notify end of collection
				if (isCollectingCommandRef.current) {
					isCollectingCommandRef.current = false
					if (onCommandProcessingStatusChange) {
						onCommandProcessingStatusChange(false)
					}
				}
			} catch (error) {
				console.error("Failed to parse command JSON:", error, "Command content:", match[1])
			processingCommandsRef.current = true
			try {
				// Save commands to the record of processed commands
				commands.forEach((cmd) => {
					processedCommandsRef.current.add(JSON.stringify(cmd))
				})
				onCommandsReceived(commands)
			} catch (error) {
				console.error("Error processing commands:", error)
		return commandsProcessed
	}, [onCommandsReceived, onCommandProcessingStatusChange])

	// Process complete content and extract commands
	const processCompleteContent = useCallback(() => {
		// Print current complete content
		console.log("Starting to process complete content, length:", completeContentRef.current.length)
		console.log("completeContentRef.current excerpt:", completeContentRef.current.substring(0, 100))

		// Check if it contains command markers
		const hasCommandStart = completeContentRef.current.includes("<!-- COMMAND_START -->")
		const hasCommandEnd = completeContentRef.current.includes("<!-- COMMAND_END -->")
		console.log("Complete content command marker check:", { hasCommandStart, hasCommandEnd })

		// Extract commands and status, and clean content
		const { updatedContent: contentWithoutCommands, commands } = extractCommands(
			completeContentRef.current,
		)

		console.log("Content after processing commands:", contentWithoutCommands.substring(0, 100))

		// Filter out commands that have already been processed
		const newCommands = commands.filter((cmd) => {
			const cmdStr = JSON.stringify(cmd)
			return !processedCommandsRef.current.has(cmdStr)
		})

		console.log(`Found ${commands.length} commands, ${newCommands.length} of which are new`)

		// Check if there are new commands
		if (newCommands.length > 0 && !processingCommandsRef.current) {
			processingCommandsRef.current = true
			try {
				// Add new commands to the record of processed commands
				newCommands.forEach((cmd) => {
					processedCommandsRef.current.add(JSON.stringify(cmd))
				})
				onCommandsReceived(newCommands)
			} catch (error) {
				console.error("Error processing commands:", error)
			} finally {
				// Reset flag immediately after command processing is complete
				processingCommandsRef.current = false
			}
		}

		// Remove status information
		const cleanContent = extractStatus(contentWithoutCommands)
		console.log("Content after processing status:", cleanContent.substring(0, 100))
		completeContentRef.current = cleanContent

		return cleanContent
	}, [onCommandsReceived])

	// Typewriter effect - display new content character by character
	const startTypingEffect = useCallback(() => {
		// Cancel previous timer
		if (typingTimerRef.current) {
			clearTimeout(typingTimerRef.current)
			typingTimerRef.current = null
		}

		// If there's no new content to display, return directly
		if (newContentBufferRef.current.length === 0) return

		// Typewriter effect function
		const typeNextBatch = () => {
			if (newContentBufferRef.current.length > 0) {
				// Determine the number of characters to display this time
				const charsToDisplay = Math.min(
					typingBatchSizeRef.current,
					newContentBufferRef.current.length,
				)

				// Extract characters to display from buffer
				const textToAdd = newContentBufferRef.current.substring(0, charsToDisplay)
				newContentBufferRef.current = newContentBufferRef.current.substring(charsToDisplay)

				// Add to display content
				displayContentRef.current += textToAdd

				// Update UI
				onTextUpdate(displayContentRef.current)

				// Schedule next batch of characters to display
				typingTimerRef.current = setTimeout(typeNextBatch, typingSpeedRef.current)
			} else {
				// All text has been displayed
				typingTimerRef.current = null

				// Check if there are any commands to process in the complete content
				processCompleteContent()
			}
		}

		// Start typewriter effect
		typeNextBatch()
	}, [onTextUpdate, processCompleteContent])

	// Add new content to buffer and start typing effect
	const addNewContent = useCallback(
		(newText: string) => {
			if (!newText) return

			// Add new content to complete content
			completeContentRef.current += newText

			// Accumulate command buffer
			commandBufferRef.current += newText

			// Try to process complete commands in the command buffer
			processCommandBuffer()

			// Process command markers
			const processedText = processCommandMarkers(newText)

			// Only add to display buffer if not in command data collection phase or if there's still content after processing
			if (processedText.length > 0) {
				// Add processed new content to the display buffer
				newContentBufferRef.current += processedText

				// If typewriter effect is not currently running, start new typing effect
				if (!typingTimerRef.current) {
					startTypingEffect()
				}
			}
		},
		[startTypingEffect, processCommandBuffer, processCommandMarkers],
	)

	// Clean up stream resources
	const cleanupStreamResources = useCallback(() => {
		// Clear typewriter effect timer
		if (typingTimerRef.current) {
			clearTimeout(typingTimerRef.current)
			typingTimerRef.current = null
		}

		// If reader exists, release it
		if (readerRef.current) {
			try {
				// Call cancel to tell the stream we no longer need more data
				readerRef.current.cancel().catch((err) => {
					console.error("Failed to cancel reader:", err)
				})
			} catch (error) {
				console.error("Error when canceling reader:", error)
			} finally {
				readerRef.current = null
			}
		}

		// Reset stream processing state
		streamProcessingRef.current = false
		currentStreamRef.current = null
	}, [])

	// Helper function to determine if it's the same stream
	const isSameStream = useCallback(
		(
			stream1: ReadableStream<Uint8Array> | null,
			stream2: ReadableStream<Uint8Array> | null,
		): boolean => {
			if (!stream1 || !stream2) return false
			if (stream1 === stream2) return true

			// Additional comparison logic can be added here, such as comparing certain properties of the two streams
			return false
		},
		[],
	)

	// Generate stream ID for unique stream identification
	const generateStreamId = useCallback((stream: ReadableStream<Uint8Array> | null): string => {
		if (!stream) return ""
		// Use void operator to avoid linter errors
		return `${Date.now()}-${Math.random().toString(36).substring(2, 9)}`
	}, [])

	useUpdateEffect(() => {
		// Clear all message content when messageId updates to prevent message accumulation
		completeContentRef.current = ""
		displayContentRef.current = ""
		newContentBufferRef.current = ""
		commandBufferRef.current = "" // Reset command buffer
		isCollectingCommandRef.current = false // Reset command collection status
		partialCommandStartRef.current = "" // Reset partial command start marker
		partialCommandEndRef.current = "" // Reset partial command end marker
		errorDetectedRef.current = false // Reset error detection flag
		processedCommandsRef.current = new Set() // Reset processed commands record

		// Reset command processing status notification
		if (onCommandProcessingStatusChange) {
			onCommandProcessingStatusChange(false)
		}

		// Cancel current typewriter effect
		if (typingTimerRef.current) {
			clearTimeout(typingTimerRef.current)
			typingTimerRef.current = null
		}

		// Clear text displayed on UI
		onTextUpdate("")
	}, [messageId, onCommandProcessingStatusChange])

	// Process SSE stream
	useEffect(() => {
		// Return directly if there's no ResponseBody
		if (!responseBody || !messageId) return

		// Generate new response body ID for more reliable differentiation between different streams
		const currentResponseBodyId = generateStreamId(responseBody)

		// 1. First determine if it's the same stream to avoid duplicate processing
		// Object reference check + ID comparison double protection to prevent duplicate processing of the same stream during React re-renders
		if (
			isSameStream(currentStreamRef.current, responseBody) &&
			responseBodyIdRef.current === currentResponseBodyId
		) {
			console.log("Already processing the same stream, skipping")
			return
		}

		// 2. Safely clean up previous resources to avoid multiple readers working simultaneously
		cleanupStreamResources()

		// 3. Reset related states and update references
		setIsProcessing(true)
		streamProcessingRef.current = true
		currentStreamRef.current = responseBody
		responseBodyIdRef.current = currentResponseBodyId
		errorDetectedRef.current = false // Reset error detection flag

		// 4. Record the stream start processing time for logging
		const streamStartTime = Date.now()
		console.log(
			`Start processing new stream: ${currentResponseBodyId}, time: ${new Date(
				streamStartTime,
			).toLocaleTimeString()}`,
		)

		const decoder = new TextDecoder("utf-8")
		let isAborted = false

		const processStream = async () => {
			try {
				// 5. Enhanced stream lock detection, check if stream is already locked in advance
				// Even if not initially locked, avoid errors when creating reader below
				try {
					if (!responseBody || responseBody.locked) {
						console.warn("Stream is already locked or doesn't exist, unable to process")
						setIsProcessing(false)
						streamProcessingRef.current = false
						return
					}
				} catch (lockCheckError) {
					console.error("Error when checking stream lock status:", lockCheckError)
					onError(`${t("flowAssistant.error", { ns: "flow" })}: Unable to check stream status`)
					setIsProcessing(false)
					streamProcessingRef.current = false
					return
				}

				// 6. Try to create reader, wrapped in try/catch to ensure exceptions can be caught
				try {
					readerRef.current = responseBody.getReader()
					console.log(`Successfully created reader: ${currentResponseBodyId}`)
				} catch (readerError) {
					// 7. Log detailed error information when creating reader
					const errorMessage =
						readerError instanceof Error ? readerError.message : String(readerError)
					console.error(`Failed to create reader (stream ID: ${currentResponseBodyId}):`, errorMessage)

					// 8. If it's a stream lock error, provide more specific error information
					if (errorMessage.includes("locked to a reader")) {
						onError(
							`${t("flowAssistant.error", { ns: "flow" })}: Stream is already locked, unable to read response`,
						)
					} else {
						onError(`${t("flowAssistant.error", { ns: "flow" })}: ${errorMessage}`)
					}

					setIsProcessing(false)
					streamProcessingRef.current = false
					return
				}

				// 9. Process data chunk function
				const processNextChunk = async (): Promise<void> => {
					if (isAborted || !readerRef.current) return

					try {
						const result = await readerRef.current.read()

						if (result.done) {
						// Ensure all content is displayed
							if (newContentBufferRef.current.length > 0) {
							// Display the last content immediately in full, not character by character
								displayContentRef.current += newContentBufferRef.current
								newContentBufferRef.current = ""
								onTextUpdate(displayContentRef.current)

							// Final command buffer processing
							// Added: Process possible commands in complete content
								processCompleteContent()
							}

						// 10. Log stream processing completion time and duration
							const streamEndTime = Date.now()
							console.log(
							`Stream processing complete: ${currentResponseBodyId}, ` +
								`duration: ${(streamEndTime - streamStartTime) / 1000} seconds`,
								console.error("Model error:", extractedData.errorInfo)
							// Pass error information to parent component
								onError(
									`${extractedData.errorInfo} Please click the retry button or refresh the page to try again.`,
								)
								// Mark stream processing as complete
								setIsProcessing(false)
								streamProcessingRef.current = false
							// Set error flag to true
								errorDetectedRef.current = true
							} else if (extractedData.content) {
								addNewContent(extractedData.content)
							}
						})

					// Recursively process next chunk
						if (!isAborted && !errorDetectedRef.current) {
						// Use setTimeout to avoid blocking main thread
							setTimeout(() => {
								processNextChunk()
							}, 0)
						}
					} catch (error) {
						if (!isAborted) {
							const errorMessage =
								error instanceof Error ? error.message : String(error)
							console.error(
							`Failed to process data chunk (stream ID: ${currentResponseBodyId}):`,
								errorMessage,
							)
							setIsProcessing(false)
							streamProcessingRef.current = false
							errorDetectedRef.current = true
							isCollectingCommandRef.current = false
							cleanupStreamResources()
							onError(`${t("flowAssistant.error", { ns: "flow" })}: ${errorMessage}`)
						}
					}
				}

				// Start processing first chunk
				await processNextChunk()
			} catch (error) {
				if (!isAborted) {
					const errorMessage = error instanceof Error ? error.message : String(error)
					console.error(`Failed to process stream data (stream ID: ${currentResponseBodyId}):`, errorMessage)
					onError(`${t("flowAssistant.error", { ns: "flow" })}: ${errorMessage}`)
					setIsProcessing(false)
					streamProcessingRef.current = false
					errorDetectedRef.current = true
				}
			}
		}

		// Start stream processing
		processStream().catch((error) => {
			console.error(`Failed to start stream processing (stream ID: ${currentResponseBodyId}):`, error)
		})

		// Cleanup function
		// eslint-disable-next-line consistent-return
		return () => {
			console.log(`useEffect cleanup function executed (stream ID: ${currentResponseBodyId})`)
			isAborted = true
			cleanupStreamResources()

			// Reset command collection status
			if (isCollectingCommandRef.current && onCommandProcessingStatusChange) {
				isCollectingCommandRef.current = false
				onCommandProcessingStatusChange(false)
			}
		}
	}, [
		responseBody,
		messageId,
		onTextUpdate,
		onCommandsReceived,
		onError,
		onComplete,
		t,
		addNewContent,
		processCompleteContent,
		processCommandBuffer,
		cleanupStreamResources,
		isSameStream,
		generateStreamId,
		onCommandProcessingStatusChange,
	])

	// Add a separate effect to respond to userScrolling changes and adjust typewriter effect parameters
	useEffect(() => {
		// Dynamically adjust typewriter effect parameters based on user scrolling status
		if (userScrolling) {
			// User is scrolling, reduce update frequency
			typingSpeedRef.current = 100
			typingBatchSizeRef.current = 10
		} else {
			// User is not scrolling, use default values
			typingSpeedRef.current = 30
			typingBatchSizeRef.current = 2
		}
	}, [userScrolling])

	return null // This is a logical component that doesn't render UI
}

/**
 * Test StreamProcessor component functionality with complete text response
 * Mainly used for testing command processing functionality in messages
 * @param completeResponse Complete response text
 * @param onTextUpdate Text update callback
 * @param onCommandsReceived Commands received callback
 * @param onComplete Completion callback
 * @param onCommandProcessingStatusChange Command processing status change callback (optional)
 */
StreamProcessor.testWithCompleteResponse = (
	completeResponse: string,
	onTextUpdate: (text: string) => void,
	onCommandsReceived: (commands: any[]) => void,
	onComplete?: () => void,
	onCommandProcessingStatusChange?: (isProcessing: boolean) => void,
): void => {
	// Check if command start marker is present
	if (completeResponse.includes("<!-- COMMAND_START -->") && onCommandProcessingStatusChange) {
		onCommandProcessingStatusChange(true)
	}

	// Extract commands
	const { updatedContent, commands } = extractCommands(completeResponse)

	// Clean status information
	const cleanContent = extractStatus(updatedContent)

	// Update display text
	onTextUpdate(cleanContent)

	// Process commands
	if (commands.length > 0) {
		onCommandsReceived(commands)
	}

	// Command processing complete
	if (onCommandProcessingStatusChange) {
		onCommandProcessingStatusChange(false)
	}

	// Completion callback
	if (onComplete) {
		onComplete()
	}
}

/**
 * Create a ReadableStream that simulates SSE stream
 * Used for testing StreamProcessor component
 * @param completeResponse Complete response text
 * @returns Simulated SSE stream
 */
StreamProcessor.createMockStream = (completeResponse: string): ReadableStream<Uint8Array> => {
	// Create encoder
	const encoder = new TextEncoder()

	// Create and return ReadableStream
	return new ReadableStream({
		start(controller) {
			// Format as SSE format data line
			const sseData = `data:{"message":{"content":${JSON.stringify(completeResponse)}}}`
			controller.enqueue(encoder.encode(sseData))
			// Complete the stream
			controller.close()
		},
	})
}

export default StreamProcessor
