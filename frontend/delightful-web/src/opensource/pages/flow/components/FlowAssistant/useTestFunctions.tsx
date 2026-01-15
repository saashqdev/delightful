// @ts-nocheck
import { useRef, useEffect } from "react"
import { useMemoizedFn } from "ahooks"
import type { MessageProps } from "./MessageItem"
import StreamProcessor from "./StreamProcessor"

interface Message extends MessageProps {}

interface UseTestFunctionsProps {
	setMessages: React.Dispatch<React.SetStateAction<Message[]>>
	setProcessingMessageId: React.Dispatch<React.SetStateAction<string | null>>
	setCommandQueue: React.Dispatch<React.SetStateAction<any[]>>
	setIsCommandProcessing: React.Dispatch<React.SetStateAction<boolean>>
	commandQueue: any[]
	setStreamResponse: React.Dispatch<React.SetStateAction<ReadableStream<Uint8Array> | null>>
	setIsProcessing: React.Dispatch<React.SetStateAction<boolean>>
}

/**
 * testing hook
 * provides testing for streaming responses and command processing
 */
export default function useTestFunctions({
	setMessages,
	setProcessingMessageId,
	setCommandQueue,
	setIsCommandProcessing,
	commandQueue,
	setStreamResponse,
	setIsProcessing,
}: UseTestFunctionsProps) {
	// track current test session ID to avoid overlapping tests
	const currentTestSessionRef = useRef<string | null>(null)
	// track whether commands are being processed
	const processingRef = useRef<boolean>(false)

	/**
	 * force cleanup of all test states
	 */
	const forceCleanupState = useMemoizedFn(() => {
		// reset all states immediately
		setProcessingMessageId(null)
		setIsCommandProcessing(false)
		setCommandQueue([])
		currentTestSessionRef.current = null
		processingRef.current = false
		console.log("test state reset")
	})

	/**
	 * create a mock real SSE ReadableStream with more reliable data transfer
	 * @param sseLines SSE event line array
	 * @param delayBetweenLines delay between lines (ms)
	 * @returns mock SSE stream
	 */
	const createMockSSEStream = useMemoizedFn(
		(sseLines: string[], delayBetweenLines: number): ReadableStream<Uint8Array> => {
			// ensure minimum delay to avoid late processing
			const safeDelay = Math.max(150, delayBetweenLines)
			console.log(`Using safe delay: ${safeDelay}ms`)

			let lineIndex = 0
			const encoder = new TextEncoder()

			// process data with a more reliable queue
			return new ReadableStream({
				start(controller) {
					// add an end-detection counter
					let processingTimeoutId: NodeJS.Timeout | null = null

					// define a function to send the next line
					const sendNextLine = () => {
						// clear previous timeout
						if (processingTimeoutId) {
							clearTimeout(processingTimeoutId)
							processingTimeoutId = null
						}

						if (lineIndex >= sseLines.length) {
							// all lines sent, close stream
							console.log(`All SSE stream lines sent (${sseLines.length} lines)`)

							// send end marker and close stream
							setTimeout(() => {
								controller.enqueue(encoder.encode("data:[DONE]\n"))
								controller.close()
								console.log("SSE stream closed")
							}, safeDelay)
							return
						}

						// get current line and prepare the next
						const line = sseLines[lineIndex]
						lineIndex += 1

						try {
						// Process the current line
						const lineWithNewline = line.endsWith("\n") ? line : `${line}\n`
						const encodedLine = encoder.encode(lineWithNewline)

						// enqueue and log
						controller.enqueue(encodedLine)
						console.log(
							`sent ${lineIndex}/${sseLines.length} lines: ${line.slice(0, 50)}${line.length > 50 ? "..." : ""}`,
							)

							// schedule next line and set timeout protection
							processingTimeoutId = setTimeout(() => {
								sendNextLine()
							}, safeDelay)
						} catch (error) {
							console.error(`error sending SSE line:`, error)
							// on error still try sending next line
							processingTimeoutId = setTimeout(() => {
								sendNextLine()
							}, safeDelay * 2) // use longer delay on error
						}
					}

					// start sending first line
					console.log(`Start sending SSE stream, total${sseLines.length} lines, delay: ${safeDelay}ms`)
					sendNextLine()
				},

				cancel() {
					console.log("mock SSE stream cancelled")
					// prevent further sending
					lineIndex = sseLines.length
				},
			})
		},
	)

	/**
	 * test using real SSE event stream format
	 * @param sseLines SSE event line array，each line formatted as data:{...}
	 * @param delayBetweenLines delay between lines (ms)
	 */
	const testWithStreamEvents = useMemoizedFn(
		(sseLines: string[], delayBetweenLines: number = 200) => {
			// if a test is already running, force cleanup first
			if (processingRef.current || currentTestSessionRef.current) {
				console.warn("detected unfinished test, performing force cleanup")
				forceCleanupState()
			}

			// generate a new test session ID
			const testSessionId = `test-${Date.now()}`
			currentTestSessionRef.current = testSessionId
			processingRef.current = true

			// create a new AI message ID
			const assistantMessageId = `msg-${Date.now()}`

			// add an empty AI message with status set to loading
			const newAssistantMessage: Message = {
				id: assistantMessageId,
				role: "assistant",
				content: "",
				status: "loading", // initial status is loading
			}

			console.log(`start new SSE stream test session: ${testSessionId}, message ID: ${assistantMessageId}`)

			// reset state and add message
			setCommandQueue([]) // clear the command queue
			setProcessingMessageId(assistantMessageId)
			setMessages((prev) => [...prev, newAssistantMessage])
			setIsProcessing(true)

			// create mock SSE stream
			const mockStream = createMockSSEStream(sseLines, delayBetweenLines)

			// manually monitor stream processing progress to ensure message status updates
			const checkStreamProgress = () => {
				// check stream processing status every second
				const checkInterval = setInterval(() => {
					// if session ID mismatches or not processing, cancel the check
					if (currentTestSessionRef.current !== testSessionId || !processingRef.current) {
						clearInterval(checkInterval)
						return
					}

					// get latest message state
					let messageHasContent = false

					setMessages((prevMessages) => {
						// find the current message
						const currentMessage = prevMessages.find(
							(msg) => msg.id === assistantMessageId,
						)

						// check whether the message has content but is still loading
						if (
							currentMessage &&
							currentMessage.status === "loading" &&
							currentMessage.content
						) {
							messageHasContent = true
							// update status to done
							return prevMessages.map((msg) =>
								msg.id === assistantMessageId ? { ...msg, status: "done" } : msg,
							)
						}
						return prevMessages
					})

					// if message has content but is still loading, ensure status correct when processing completes
					if (messageHasContent) {
						console.log(`detected content but status not updated, fixing status: ${assistantMessageId}`)
					}
				}, 1000)

				// force cleanup after 60 seconds to avoid infinite checker
				setTimeout(() => {
					clearInterval(checkInterval)

					// if still processing, force update message status and end processing
					if (currentTestSessionRef.current === testSessionId && processingRef.current) {
						console.warn(`stream processing timeout, forcing completion: ${testSessionId}`)

						// update message status to done
						setMessages((prevMessages) =>
							prevMessages.map((msg) =>
								msg.id === assistantMessageId ? { ...msg, status: "done" } : msg,
							),
						)

						// clean processing state
						setProcessingMessageId(null)
						setIsProcessing(false)
						processingRef.current = false
						currentTestSessionRef.current = null
						setStreamResponse(null)
					}
				}, 60000)
			}

			// start progress check
			checkStreamProgress()
										forceCleanupState, // expose force cleanup for manual reset
										testWithStreamEvents, // expose SSE stream test (array version)

			// set response stream for StreamProcessor to process
			setStreamResponse(mockStream)
		},
	)

	/**
	 * extract content from SSE data in raw form, preserving special characters
	 * @param sseContent complete SSE-formatted content
	 * @returns extracted raw content, or null on failure
	 */
	const extractContentFromSSE = (sseContent: string): string | null => {
		try {
			// split SSE content by line
			const lines = sseContent.split("\n").filter((line) => line.trim().length > 0)
			if (lines.length === 0) return null

			// collect character content for each line
			const contentFragments: string[] = []

			// use forEach instead of for loop to avoid linter errors
			lines.forEach((line) => {
				// ensure it starts with data:
				if (!line.startsWith("data:")) return // use return instead of continue

				try {
					// extract JSON string after data:
					const jsonStr = line.substring(5)
					// try parsing JSON
					const data = JSON.parse(jsonStr)

					// try multiple paths to extract content
					if (data.message?.content !== undefined) {
						// common structure: {"message":{"content":"..."}}
						contentFragments.push(data.message.content)
					} else if (data.content !== undefined) {
						// simple structure: {"content":"..."}
						contentFragments.push(data.content)
					} else if (typeof data === "string") {
						// pure string structure
						contentFragments.push(data)
					}
				} catch (e) {
					// JSON parsing failed; log and continue other lines
					console.warn(`unable to parse SSE line JSON data: ${line.substring(0, 50)}...`)

					// try using regex to extract content directly
					const contentMatch = /"content":"([^"]*)"/g.exec(line)
					if (contentMatch && contentMatch[1]) {
						// need to handle escaped quotes and special characters
						try {
							// decode escaped characters using JSON.parse
							const decodedContent = JSON.parse(`"${contentMatch[1]}"`)
							contentFragments.push(decodedContent)
						} catch (decodeError) {
							// decoding failed; use raw matched content
							contentFragments.push(contentMatch[1])
						}
					}
				}
			})

			// if content extracted, join and return
			if (contentFragments.length > 0) {
				const result = contentFragments.join("")
				// check whether special characters exist
				const hasSpecialChars = /[:"\\\n\r\t]/.test(result)
				if (hasSpecialChars) {
					console.log("extracted content contains special characters; ensure correct handling")
				}
				return result
			}

			// no content found
			return null
		} catch (e) {
			console.error("failed to extract content from SSE:", e)
			return null
		}
	}

	/**
	 * extract plain text directly from raw SSE event array without JSON parsing
	 * this method handles the extreme case of one character per event
	 * @param sseEvents SSE event array; each event formatted as data:{"message":{"content":"char"}}
	 * @returns extracted plain text content
	 */
	const extractTextFromSSEEvents = (sseEvents: string[]): string => {
		const fragments: string[] = []

		// log special character matches
		let specialCharCount = 0

		// use forEach to avoid linter errors
		sseEvents.forEach((event) => {
			if (!event.startsWith("data:")) return // use return instead of continue

			// use direct string matching to avoid JSON parse errors
			const contentRegex = /"content":"((?:\\"|[^"])*)"/
			const match = contentRegex.exec(event)

			if (match && match[1] !== undefined) {
				try {
					// get the content inside quotes and handle escapes
					const rawContent = match[1]
					// use JSON.parse to handle escaped characters
					const content = JSON.parse(`"${rawContent}"`)

					// check special characters
					if (/[:"\\\n\r\t]/.test(content)) {
						specialCharCount += 1 // avoid using ++
					}

					fragments.push(content)
				} catch (e) {
					// on parse failure, use raw matched content
					fragments.push(match[1])
				}
			}
		})

		// log special character situation
		if (specialCharCount > 0) {
			console.log(`Extracted ${specialCharCount} fragments with special characters from SSE events`)
		}

		return fragments.join("")
	}

	/**
	 * Test using raw string content (non-SSE format, content itself)
	 * Handle large text blocks by splitting into chunks, ensuring special characters (newline, colon, etc.) are preserved
	 * @param fullContent Full response content (not SSE format; raw content)
	 * @param delayBetweenChunks Delay between chunks (milliseconds)
	 * @param chunkSize Size of each chunk (character count)
	 */
	const testWithRawContent = useMemoizedFn(
		(fullContent: string, delayBetweenChunks: number = 200, chunkSize: number = 10) => {
			// validate parameters
			if (!fullContent) {
				console.warn("Content is empty, cannot test")
				return
			}

			// if a test is already running, force cleanup first
			if (processingRef.current || currentTestSessionRef.current) {
				console.warn("detected unfinished test, performing force cleanup")
				forceCleanupState()
			}

			// generate a new test session ID
			const testSessionId = `test-${Date.now()}`
			currentTestSessionRef.current = testSessionId
			processingRef.current = true

			// create a new AI message ID
			const assistantMessageId = `msg-${Date.now()}`

			// add an empty AI message with status set to loading
			const newAssistantMessage: Message = {
				id: assistantMessageId,
				role: "assistant",
				content: "",
				status: "loading", // initial status is loading
			}

			console.log(`start new raw content test session: ${testSessionId}, message ID: ${assistantMessageId}`)
			console.log(`raw content length: ${fullContent.length} characters`)

			// reset state and add message
			setCommandQueue([]) // clear the command queue
			setProcessingMessageId(assistantMessageId)
			setMessages((prev) => [...prev, newAssistantMessage])
			setIsProcessing(true)

			// check whether content contains special characters
			const hasSpecialChars = /[\r\n\t":{}[\]\\]/.test(fullContent)

			// log special characters
			if (hasSpecialChars) {
				console.log("detected special characters; use smaller chunk size with precise encoding")
				// simple log that special characters exist
				console.log("found special characters; using stricter processing")

				// print a short sample containing special characters
				let sampleWithSpecialChars = ""
				for (
					let i = 0;
					i < fullContent.length && sampleWithSpecialChars.length < 100;
					i += 1
				) {
					if (/[\r\n\t":{}[\]\\]/.test(fullContent[i])) {
						const start = Math.max(0, i - 10)
						const end = Math.min(fullContent.length, i + 10)
						sampleWithSpecialChars = fullContent.substring(start, end)
						console.log(
							`special character sample at position ${i}, context: "${sampleWithSpecialChars.replace(
								/\n/g,
								"\\n",
							)}"`,
						)
						break
					}
				}
			}

			// split the full content into chunks; each chunk becomes an SSE event
			const chunks: string[] = []

			// use smaller chunk size for special characters to ensure correct encoding
			const safeChunkSize = hasSpecialChars ? Math.min(chunkSize, 3) : chunkSize
			console.log(
				`using chunk size: ${safeChunkSize} (${
					hasSpecialChars ? "special characters detected" : "no special characters"
				})`,
			)

			// log raw content and JSON-encoded content for debugging
			console.log(
				`raw content sample: "${fullContent.substring(0, 50)}${
					fullContent.length > 50 ? "..." : ""
				}"`,
			)
			const jsonEncoded = JSON.stringify(fullContent.substring(0, 50))
			console.log(`after JSON encoding: ${jsonEncoded}`)

			// verify JSON decode is correct
			const testDecoded = JSON.parse(jsonEncoded)
			console.log(`decode test: "${testDecoded}"`)
			if (testDecoded !== fullContent.substring(0, 50)) {
				console.warn("warning: JSON encode/decode test mismatch!")
			}

			// chunk content for processing
			for (let i = 0; i < fullContent.length; i += safeChunkSize) {
				const chunk = fullContent.substring(
					i,
					Math.min(i + safeChunkSize, fullContent.length),
				)

				// log special characters
				const hasSpecialInChunk = /[\r\n\t":{}[\]\\]/.test(chunk)
				if (hasSpecialInChunk) {
					// convert to char codes for debugging
					const charCodes = Array.from(chunk)
						.map((c) => `${c}(${c.charCodeAt(0)})`)
						.join(" ")
					console.log(
						`chunk ${Math.floor(i / safeChunkSize)} contains special characters: ${charCodes}`,
					)
				}

				// use strict JSON string encoding
				const escapedContent = JSON.stringify(chunk)

				// ensure JSON format is correct
				try {
					// verify escaped content can be parsed back
					const testParse = JSON.parse(escapedContent)
					if (testParse !== chunk) {
						console.warn(`warning: JSON encode/decode mismatch!`)
						console.log(`expected: "${chunk}"`)
						console.log(`actual: "${testParse}"`)

						// log detailed character codes for diagnosis
						const originalChars = Array.from(chunk).map(
							(c) => `${c}(${c.charCodeAt(0)})`,
						)
						const parsedChars = Array.from(testParse).map(
							// @ts-ignore
							(c) => `${c}(${c.charCodeAt(0)})`,
						)
						console.log(`original char codes: ${originalChars.join(" ")}`)
						console.log(`parsed char codes: ${parsedChars.join(" ")}`)
					}
				} catch (e) {
					console.error(`JSON validation failed: ${e}`)
				}

				// build SSE events directly from escaped content to preserve original text
				const sseEvent = `data:{"id":"${testSessionId}","event":"message","conversation_id":"test","message":{"role":"assistant","content":${escapedContent}}}`
				chunks.push(sseEvent)

				// log SSE event sample
				if (i === 0) {
					console.log(`SSE event sample: ${sseEvent}`)
				}
			}

			console.log(
				`split content of ${fullContent.length} characters into ${chunks.length} event chunks`,
			)

			// use SSE stream testing to process these events
			testWithStreamEvents(chunks, delayBetweenChunks)
		},
	)

	/**
	 * test StreamProcessor with complete response text
	 * @param completeResponse complete response text
	 */
	const testWithCompleteResponse = useMemoizedFn((completeResponse: string) => {
		// if a test is already running, force cleanup first
		if (processingRef.current || currentTestSessionRef.current) {
			console.warn("detected unfinished test, performing force cleanup")
			forceCleanupState()
		}

		// generate a new test session ID
		const testSessionId = `test-${Date.now()}`
		currentTestSessionRef.current = testSessionId
		processingRef.current = true

		// create a new AI message ID
		const assistantMessageId = `msg-${Date.now()}`

		// add an empty AI message with status set to loading
		const newAssistantMessage: Message = {
			id: assistantMessageId,
			role: "assistant",
			content: "",
			status: "loading", // initial status is loading
		}

		console.log(`start new test session: ${testSessionId}, message ID: ${assistantMessageId}`)

		// reset state and add message
		setCommandQueue([]) // clear the command queue
		setProcessingMessageId(assistantMessageId)
		setMessages((prev) => [...prev, newAssistantMessage])

		// use StreamProcessor static method to process full response
		StreamProcessor.testWithCompleteResponse(
			completeResponse,
			// text update callback
			(text) => {
				// check whether it is the current test session
				if (currentTestSessionRef.current !== testSessionId) {
					console.warn("ignore outdated test session callback")
					return
				}

				// update message content and set status to done
				setMessages((prev) =>
					prev.map((msg) =>
						msg.id === assistantMessageId
							? { ...msg, content: text, status: "done" }
							: msg,
					),
				)
			},
			// command receive callback
			(commands) => {
				// check whether it is the current test session
				if (currentTestSessionRef.current !== testSessionId) {
					console.warn("ignore outdated test session command")
					return
				}

				if (commands.length > 0) {
					console.log(`commands received: ${commands.length}, session: ${testSessionId}`)

					// set command queue with functional update to avoid stale closures
					setCommandQueue(commands)

					// mark command processing start
					setIsCommandProcessing(true)

					// if commands exist, update message content indicating processing commands
					setMessages((prev) =>
						prev.map((msg) =>
							msg.id === assistantMessageId
								? {
										...msg,
										content: msg.content || "Processing commands...",
										status: "done",
								  }
								: msg,
						),
					)
				} else {
					console.log(`no commands need processing, session: ${testSessionId}`)

					// no commands; ensure message status is done
					setMessages((prev) =>
						prev.map((msg) =>
							msg.id === assistantMessageId ? { ...msg, status: "done" } : msg,
						),
					)

					// complete processing
					setProcessingMessageId(null)
					processingRef.current = false
					currentTestSessionRef.current = null
				}
			},
			// completion callback
			() => {
				console.log(`test session content processing complete: ${testSessionId}`)

				// get latest command queue state for verification
				const currentCommands = commandQueue
				const hasCommands = currentCommands && currentCommands.length > 0

				// if no commands, clean processing state
				if (!hasCommands) {
					setProcessingMessageId(null)
					setIsCommandProcessing(false)
					processingRef.current = false
					currentTestSessionRef.current = null
					console.log(`test session completed: ${testSessionId}`)
				}
				// note: if commands exist, do not clear processingMessageId here
				// clear after CommandProcessor completes
			},
		)
	})

	/**
	 * create a mock SSE stream
	 * @param content complete response text
	 * @returns mock ReadableStream
	 */
	const createMockStream = useMemoizedFn((content: string): ReadableStream<Uint8Array> => {
		return StreamProcessor.createMockStream(content)
	})

	/**
	 * test using full SSE event string
	 * accept a single string containing multiple lines of SSE data; automatically split and process
	 * @param sseContent complete SSE event string containing multiple data: lines
	 * @param delayBetweenLines delay between lines (ms)
	 */
	const testWithFullSSEContent = useMemoizedFn(
		(sseContent: string, delayBetweenLines: number = 200) => {
			// if content is empty, return directly
			if (!sseContent) {
				console.warn("SSE content is empty; cannot test")
				return
			}

			console.log("Start processing SSE content, length:", sseContent.length)

			// check whether it is SSE format
			const isSSEFormat = sseContent.trim().startsWith("data:")

			if (isSSEFormat) {
				// 1. Try extracting raw content
				console.log("Detected SSE formatted data, trying two extraction methods")

				// Method 1: Process entire text line-by-line
				const contentFromText = extractContentFromSSE(sseContent)

				// Method 2: Split into event array (suitable for per-character sending)
				const events = sseContent.split("\n").filter((line) => line.trim().length > 0)
				const contentFromEvents = extractTextFromSSEEvents(events)

				// compare both methods and choose the one preserving more special characters
				let finalContent: string

				if (contentFromText && contentFromEvents) {
					// check which result preserves more special characters
					const specialCharsInText = (contentFromText.match(/[:"\\\n\r\t]/g) || []).length
					const specialCharsInEvents = (contentFromEvents.match(/[:"\\\n\r\t]/g) || [])
						.length

					if (specialCharsInEvents > specialCharsInText) {
						console.log("Using event-array extraction; it preserves more special characters")
						finalContent = contentFromEvents
					} else {
						console.log("Using text extraction method; it preserves more special characters")
						finalContent = contentFromText
					}
				} else {
					// use whichever result is non-empty
					finalContent = contentFromText || contentFromEvents || sseContent
				}

				console.log(`extracted raw content, length: ${finalContent.length} characters`)

				// use content extracted by testWithRawContentProcess to preserve special characters
				testWithRawContent(finalContent, delayBetweenLines, 3) // use small chunk size to ensure special characters handled correctly
			} else {
				// not SSE format; process as raw content
				console.log("content is not SSE format; process as raw content")
				testWithRawContent(sseContent, delayBetweenLines)
			}
		},
	)

	/**
	 * expose test methods on window for console usage
	 */
	const exposeTestFunctions = useMemoizedFn(() => {
		// @ts-ignore
		window.testFlowAssistant = {
			testWithCompleteResponse,
			createMockStream,
			forceCleanupState, // expose force cleanup for manual reset
			testWithStreamEvents, // expose SSE stream test (array version)
			testWithFullSSEContent, // expose SSE stream test (string version)
			testWithRawContent, // test raw text content while preserving special characters
		}
	})

	/**
	 * clean up exposed test methods
	 */
	const cleanupTestFunctions = useMemoizedFn(() => {
		// @ts-ignore
		delete window.testFlowAssistant
	})

	// expose test methods on mount; clean up on unmount
	useEffect(() => {
		exposeTestFunctions()
		return () => {
			cleanupTestFunctions()
			forceCleanupState() // ensure cleanup when component unmounts
		}
	}, [exposeTestFunctions, cleanupTestFunctions, forceCleanupState])

	return {
		testWithCompleteResponse,
		createMockStream,
		forceCleanupState,
		testWithStreamEvents,
		testWithFullSSEContent,
		testWithRawContent,
	}
}






