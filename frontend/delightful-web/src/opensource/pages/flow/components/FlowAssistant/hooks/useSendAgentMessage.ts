import { useMemoizedFn } from "ahooks"
import { env } from "@/utils/env"
import { message as antdMessage } from "antd"
import { useGlobalLanguage } from "@/opensource/models/config/hooks"
import { userStore } from "@/opensource/models/user"

interface SendMessageOptions {
	includeFlowData?: boolean
	conversationId: string
	getFetchFlowData?: () => string
}

interface SendMessageResult {
	stream: ReadableStream<Uint8Array> | null
	isError: boolean
	errorMessage?: string
	contentStr?: string
}

/**
 * Encapsulate core logic for sending messages to Agent
 */
export const useSendAgentMessage = () => {
	const language = useGlobalLanguage(false)
	/**
	 * Send message to Agent and get response
	 */
	const sendAgentMessage = useMemoizedFn(
		async (content: string, options: SendMessageOptions): Promise<SendMessageResult> => {
			// Default result
			const defaultResult: SendMessageResult = {
				stream: null,
				isError: false,
			}

			try {
				// Build message to send to assistant
				let message = `Instruction: ${content}`

			// Include flow data only when needed
			if (options.includeFlowData && options.getFetchFlowData) {
				const flowYaml = options.getFetchFlowData()
				message += `\nFlow Data:\n${flowYaml}`
				}

// Use fetch API directly to send request
				const apiUrl = `${env("DELIGHTFUL_SERVICE_BASE_URL")}/api/v2/delightful/flows/built-chat`

// For delightful API requests, the organization Code needs to be replaced with the organization Code in the delightful ecosystem, not the teamshare organization Code
				const delightfulOrganizationCode = userStore.user.organizationCode

				const headers = {
					"Content-Type": "application/json",
					authorization: userStore.user.authorization ?? "",
					"organization-code": delightfulOrganizationCode ?? "",
					language,
				}

				console.log("Send Agent request headers:", headers)

				// Send request
				const response = await fetch(apiUrl, {
					method: "POST",
					headers,
					body: JSON.stringify({
						message,
						conversation_id: options.conversationId || "temp-conversation",
						stream: true,
						flow_code: "flow_assistant",
					}),
				})

				// Log response information
				console.log("Agent response object:", response)
				console.log("Agent response status:", response.status, response.statusText)

// Check response status
			if (!response.ok) {
				console.error(`Request error: ${response.status} ${response.statusText}`)

// Try to extract error message from response
				let errorMessage = `Server returned error: ${response.status} ${response.statusText}`

					if (response.body) {
						try {
							// Try to read error message
							const reader = response.body.getReader()
							const decoder = new TextDecoder("utf-8")
							const { value } = await reader.read()

							if (value) {
// Decode data
								const errorContent = decoder.decode(value)

									// If there is error content, try to parse it
								if (errorContent.trim()) {
									try {
										const errorData = JSON.parse(errorContent)
										if (errorData.message) {
											errorMessage = errorData.message
										}
									} catch (e) {
											// If not JSON format, use raw error content
										errorMessage = errorContent
									}
								}
							}
						} catch (e) {
							console.error("Failed to read error response body:", e)
						}
					}

					antdMessage.error(errorMessage)
					return { ...defaultResult, isError: true, errorMessage }
				}

				// Check if it's a streaming response
				if (
					response.body &&
					response.headers.get("content-type")?.includes("text/event-stream")
				) {
					// Return streaming response
					return {
						...defaultResult,
						stream: response.body,
					}
				} else {
					// Handle non-streaming response, this is less common
					console.log("Received non-streaming response:", response)
					try {
						const responseData = await response.json()
						console.log("Response data:", responseData)

						// Extract content from response
						if (responseData.messages && responseData.messages.length > 0) {
							const contentStr = responseData.messages[0].message.content
							return { ...defaultResult, contentStr }
						} else {
							const errorMessage = "Did not receive valid response"
							return { ...defaultResult, isError: true, errorMessage }
						}
					} catch (e) {
						// If unable to parse response as JSON
						console.error("Failed to parse response:", e)
						const errorMessage = "Unable to parse server response"
						return { ...defaultResult, isError: true, errorMessage }
					}
				}
			} catch (error) {
			console.error("Failed to send message:", error)
			const errorMessage = error instanceof Error ? error.message : String(error)
			antdMessage.error(`Failed to send message: ${errorMessage}`)
				return { ...defaultResult, isError: true, errorMessage }
			}
		},
	)

	return {
		sendAgentMessage,
	}
}

export default useSendAgentMessage





