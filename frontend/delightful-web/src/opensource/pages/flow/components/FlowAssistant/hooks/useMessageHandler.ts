// @ts-nocheck
import { useState, useRef } from "react"
import { useMemoizedFn } from "ahooks"
import { useTranslation } from "react-i18next"
import { message as antdMessage } from "antd"
import type { Message } from "../index"
import { useSendAgentMessage } from "./useSendAgentMessage"

interface UseMessageHandlerProps {
	conversationId: string
	setMessages: React.Dispatch<React.SetStateAction<Message[]>>
	hasSentFlowData: boolean
	setHasSentFlowData: React.Dispatch<React.SetStateAction<boolean>>
	setForceScroll: React.Dispatch<React.SetStateAction<boolean>>
	getFlowData?: () => string
}

interface UseMessageHandlerResult {
	isProcessing: boolean
	streamResponse: ReadableStream<Uint8Array> | null
	processingMessageId: string | null
	sendMessage: (content: string) => Promise<void>
	handleRetryMessage: (messageId: string) => Promise<void>
	handleStreamError: (errorMessage: string) => void
	handleStreamComplete: () => void
	setIsProcessing: React.Dispatch<React.SetStateAction<boolean>>
	setStreamResponse: React.Dispatch<React.SetStateAction<ReadableStream<Uint8Array> | null>>
	setProcessingMessageId: React.Dispatch<React.SetStateAction<string | null>>
}

export function useMessageHandler({
	conversationId,
	setMessages,
	hasSentFlowData,
	setHasSentFlowData,
	setForceScroll,
	getFlowData,
}: UseMessageHandlerProps): UseMessageHandlerResult {
	const { t } = useTranslation()
	const [isProcessing, setIsProcessing] = useState(false)
	const [streamResponse, setStreamResponse] = useState<ReadableStream<Uint8Array> | null>(null)
	const [processingMessageId, setProcessingMessageId] = useState<string | null>(null)
	const lastMessageCountRef = useRef(1) // Initially there is one welcome message

	// Use sendAgentMessage hook
	const { sendAgentMessage } = useSendAgentMessage()

	// Handle stream processing errors
	const handleStreamError = useMemoizedFn((errorMessage: string) => {
		if (!processingMessageId) return

		setMessages((prev) =>
			prev.map((msg) =>
				msg.id === processingMessageId
					? { ...msg, content: errorMessage, status: "error" }
					: msg,
			),
		)

		// Mark stream processing complete but keep error state
		setIsProcessing(false)
	})

	// Handle stream processing completion
	const handleStreamComplete = useMemoizedFn(() => {
		console.log(`handleStreamComplete called, processingMessageId=${processingMessageId}`)
		setIsProcessing(false)
		setStreamResponse(null)

		// Ensure the current processing message status is correctly set to done
		if (processingMessageId) {
			setMessages((prev) =>
				prev.map((msg) => {
					// Check if it's the message currently being processed
					if (msg.id === processingMessageId) {
						console.log(
							`Updating message ${msg.id} status to done, current status: ${msg.status}, content: ${msg.content}`,
						)

						// If the message status is error, keep error status
						if (msg.status === "error") {
							return msg
						}

						// Keep confirmOperation property, only update status
						return {
							...msg,
							status: "done",
							// If the message contains confirmOperation, ensure it is retained
							confirmOperation: msg.confirmOperation || undefined,
						}
					}
					return msg
				}),
			)
		}

		setProcessingMessageId(null)
	})

	// Send message
	const sendMessage = useMemoizedFn(async (content: string) => {
		if (!content.trim() || isProcessing) return

		const userMessage = content.trim()

		// Add user message and assistant message
		const newUserMessage: Message = {
			id: Date.now().toString(),
			role: "user",
			content: userMessage,
			status: "done", // Explicitly set user message status
		}

		const assistantMessageId = (Date.now() + 1).toString()
		const loadingAssistantMessage: Message = {
			id: assistantMessageId,
			role: "assistant",
			content: "",
			status: "loading", // Ensure assistant message initial status is loading
		}

		// Add messages and set force scroll flag
		setMessages((prev) => [...prev, newUserMessage, loadingAssistantMessage])
		setForceScroll(true) // Explicitly set force scroll
		lastMessageCountRef.current += 2 // Update message count

		setIsProcessing(true)
		setProcessingMessageId(assistantMessageId)

		try {
			// Use the wrapped send message function
			const result = await sendAgentMessage(userMessage, {
				conversationId: conversationId || "temp-conversation",
				includeFlowData: !hasSentFlowData, // Include flow data only on first send
				getFetchFlowData: getFlowData,
			})

			// If it's the first send, mark flow data as sent
			if (!hasSentFlowData && getFlowData) {
				setHasSentFlowData(true)
			}

			// Handle error case
			if (result.isError) {
				// Update assistant message to show error
				setMessages((prev) =>
					prev.map((msg) =>
						msg.id === assistantMessageId
							? {
									...msg,
								content: result.errorMessage || "Unknown error",
									status: "error",
							  }
							: msg,
					),
				)
				setIsProcessing(false)
				setProcessingMessageId(null)
				return
			}

			// Set stream processing parameters
			if (result.stream) {
				setStreamResponse(result.stream)
			} else if (result.contentStr) {
				// Handle non-stream response
				setMessages((prev) =>
					prev.map((msg) =>
						msg.id === assistantMessageId
							? {
									...msg,
								content: result.contentStr || "Server returned no content",
									status: "done",
							  }
							: msg,
					),
				)
				setIsProcessing(false)
				setProcessingMessageId(null)
			}
		} catch (error) {
			console.error("Error processing message:", error)
			const errorMessage = error instanceof Error ? error.message : String(error)

			// Update assistant message to error status
			setMessages((prev) =>
				prev.map((msg) =>
					msg.id === assistantMessageId
						? {
								...msg,
								content: `${t("flowAssistant.error", {
									ns: "flow",
								})}: ${errorMessage}`,
								status: "error",
						  }
						: msg,
				),
			)

			antdMessage.error(t("flowAssistant.processError", { ns: "flow" }))
			setIsProcessing(false)
			setProcessingMessageId(null)
		}
	})

	// Retry message
	const handleRetryMessage = useMemoizedFn(async (messageId: string) => {
		// Find the message to retry
		const messageIndex = setMessages((prevMessages) => {
			const index = prevMessages.findIndex((msg) => msg.id === messageId)
			if (index <= 0) return prevMessages // Message doesn't exist or is the first message

			// Find the nearest previous user message
			let userMessageContent = ""
			let userMessageIndex = -1

			for (let i = index - 1; i >= 0; i -= 1) {
				if (prevMessages[i].role === "user") {
					userMessageContent = prevMessages[i].content
					userMessageIndex = i
					break
				}
			}

			if (!userMessageContent) return prevMessages // No corresponding user message found

			// Remove the error message
			const newMessages = [...prevMessages]
			newMessages.splice(index, 1)

			// Create a new assistant message
			const newAssistantMessageId = Date.now().toString()
			const newAssistantMessage: Message = {
				id: newAssistantMessageId,
				role: "assistant",
				content: "",
				status: "loading",
			}

			// Insert the new assistant message after the user message
			newMessages.splice(userMessageIndex + 1, 0, newAssistantMessage)

			// Send request asynchronously
			setTimeout(async () => {
				setIsProcessing(true)
				setProcessingMessageId(newAssistantMessageId)

				try {
					// Resend request using the previous user message
					const result = await sendAgentMessage(userMessageContent, {
						conversationId: conversationId || "temp-conversation",
						includeFlowData: false, // No need to include flow data on retry
					})

					// Handle response
					if (result.isError) {
						setMessages((prev) =>
							prev.map((msg) =>
								msg.id === newAssistantMessageId
									? {
											...msg,
										content: result.errorMessage || "Unknown error",
											status: "error",
									  }
									: msg,
							),
						)
						setIsProcessing(false)
						setProcessingMessageId(null)
						return
					}

					// Set stream processing parameters
					if (result.stream) {
						setStreamResponse(result.stream)
					} else if (result.contentStr) {
						// Handle non-stream response
						setMessages((prev) =>
							prev.map((msg) =>
								msg.id === newAssistantMessageId
									? {
											...msg,
										content: result.contentStr || "Server returned no content",
											status: "done",
									  }
									: msg,
							),
						)
						setIsProcessing(false)
						setProcessingMessageId(null)
					}
				} catch (error) {
					console.error("Retry message failed:", error)
					const errorMessage = error instanceof Error ? error.message : String(error)

					// Update assistant message to error status
					setMessages((prev) =>
						prev.map((msg) =>
							msg.id === newAssistantMessageId
								? {
										...msg,
										content: `${t("flowAssistant.error", {
											ns: "flow",
										})}: ${errorMessage}`,
										status: "error",
								  }
								: msg,
						),
					)

					antdMessage.error(t("flowAssistant.processError", { ns: "flow" }))
					setIsProcessing(false)
					setProcessingMessageId(null)
				}
			}, 0)

			return newMessages
		})

		// Set force scroll to show the new message
		setForceScroll(true)
	})

	return {
		isProcessing,
		streamResponse,
		processingMessageId,
		sendMessage,
		handleRetryMessage,
		handleStreamError,
		handleStreamComplete,
		setIsProcessing,
		setStreamResponse,
		setProcessingMessageId,
	}
}





