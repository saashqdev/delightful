/* eslint-disable prettier/prettier */
/* eslint-disable no-underscore-dangle */
/* eslint-disable no-console */
/* eslint-disable no-restricted-syntax */
import type React from "react"
import { useState, useRef, useEffect } from "react"
import { Input, Button, Card, List, message as antdMessage } from "antd"
import { useTranslation } from "react-i18next"
import { useMemoizedFn } from "ahooks"
import { IconSend, IconX, IconBan } from "@tabler/icons-react"
import { Resizable } from "re-resizable"
import { FlowConverter } from "../../utils/flowConverter"
import { useStyles } from "./styles"
import StreamProcessor from "./StreamProcessor"
import type { MessageProps } from "./MessageItem"
import MessageItem from "./MessageItem"
import CommandProcessor from "./CommandProcessor"
import useFlowOperations from "./useFlowOperations"
import useTestFunctions from "./useTestFunctions"
import { useConfirmOperations } from "./hooks/useConfirmOperations"
import { useSendAgentMessage } from "./hooks/useSendAgentMessage"
import { DelightfulFlow } from "@bedelightful/delightful-flow/dist/DelightfulFlow/types/flow"
import { FlowApi } from "@/apis"

function generateMD5(input: string): string {
	let hash = 0
	for (let i = 0; i < input.length; i += 1) {
		// eslint-disable-next-line no-bitwise
		hash = (hash << 5) - hash + input.charCodeAt(i)
		// eslint-disable-next-line no-bitwise
		hash |= 0
	}

	const hashHex = Math.abs(hash).toString(16).padStart(12, "0")
	return hashHex.substring(0, 12)
}

function generateConversationId(flowId: string): string {
	const randomStr = Math.random().toString(36).substring(2, 10)
	const baseString = `${flowId || "temp-flow"}-${randomStr}`
	return generateMD5(baseString)
}

const { TextArea } = Input

export interface Message extends MessageProps {}

interface FlowAssistantProps {
	flowInteractionRef: React.MutableRefObject<any>
	flow?: DelightfulFlow.Flow
	onClose: () => void
	isAgent: boolean
	saveDraft: () => Promise<void>
	isEditRight: boolean
}

interface AgentCallParams {
	message: string
	conversation_id: string
	stream?: boolean
}

const parseSSELine = (line: string): { text: string; commands: any[] } => {
	if (line === "data:[DONE]") {
		return { text: "", commands: [] }
	}

	if (line.startsWith("data:") && line.length > 5) {
		try {
			const jsonStr = line.substring(5).trim()
			const data = JSON.parse(jsonStr)

			let extractedText = ""

			if (data.message && typeof data.message.content === "string") {
				extractedText = data.message.content
			} else if (data.type === "message" && typeof data.content === "string") {
				extractedText = data.content
			} else if (typeof data.content === "string") {
				extractedText = data.content
			}

			const commands = []
			if (data.type === "flow_commands" && Array.isArray(data.commands)) {
				commands.push(...data.commands)
			}

			return { text: extractedText, commands }
		} catch (error) {
			console.error("Failed to parse SSE line:", error)
		}
	}

	return { text: "", commands: [] }
}

const DEFAULT_WIDTH = 420
const DEFAULT_HEIGHT = 600
const MIN_WIDTH = 420
const MIN_HEIGHT = 350
const MAX_WIDTH = 800
const MAX_HEIGHT = 900

const DEFAULT_POSITION = { x: undefined as number | undefined, y: "20%" as string | number }

const STORAGE_KEY = {
	POSITION: "flow_assistant_position",
	SIZE: "flow_assistant_size",
}

const getSavedPosition = (): typeof DEFAULT_POSITION => {
	try {
		const saved = localStorage.getItem(STORAGE_KEY.POSITION)
		if (saved) {
			return JSON.parse(saved)
		}
	} catch (e) {
		console.error("Error:", e)
	}
	return DEFAULT_POSITION
}

const getSavedSize = (): { width: number; height: number } => {
	try {
		const saved = localStorage.getItem(STORAGE_KEY.SIZE)
		if (saved) {
			return JSON.parse(saved)
		}
	} catch (e) {
		console.error("Error:", e)
	}
	return { width: DEFAULT_WIDTH, height: DEFAULT_HEIGHT }
}

export default function FlowAssistant({
	flowInteractionRef,
	flow,
	onClose,
	saveDraft,
}: FlowAssistantProps) {
	const { t } = useTranslation()
	const { styles } = useStyles()
	const [messages, setMessages] = useState<Message[]>([
		{
			id: "0",
			role: "assistant",
			content: t("flowAssistant.welcome", { ns: "flow" }),
			status: "done",
		},
	])
	const [inputValue, setInputValue] = useState("")
	const [isProcessing, setIsProcessing] = useState(false)
	const [conversationId, setConversationId] = useState<string>("")
	const messagesEndRef = useRef<HTMLDivElement>(null)
	const [streamResponse, setStreamResponse] = useState<ReadableStream<Uint8Array> | null>(null)
	const [processingMessageId, setProcessingMessageId] = useState<string | null>(null)
	const [commandQueue, setCommandQueue] = useState<any[]>([])
	const [isCommandProcessing, setIsCommandProcessing] = useState(false)
	const [userScrolling, setUserScrolling] = useState(false)
	const scrollTimeoutRef = useRef<NodeJS.Timeout | null>(null)
	const [forceScroll, setForceScroll] = useState(false)
	const lastMessageCountRef = useRef(1)
	const [size, setSize] = useState(getSavedSize())
	const [position, setPosition] = useState(getSavedPosition())
	const [isDragging, setIsDragging] = useState(false)
	const dragStartRef = useRef({ x: 0, y: 0 })
	const positionRef = useRef(getSavedPosition())
	const [isCollectingCommand, setIsCollectingCommand] = useState(false)

	const { sendAgentMessage } = useSendAgentMessage()

	const flowOperations = useFlowOperations({
		flowInteractionRef,
		saveDraft,
		flowService: FlowApi,
	})

	const getFlowData = useMemoizedFn(() => {
		const currentFlow = flowInteractionRef?.current?.getFlow()
		return currentFlow ? FlowConverter.jsonToYamlString(currentFlow) : ""
	})

	const { handleConfirmOperationCommand, handleConfirmOperation, handleCancelOperation } =
		useConfirmOperations({
			flowId: flow?.id || "",
			executeOperations: flowOperations.executeOperations,
			setMessages,
			setForceScroll,
			sendMessage: async (content: string) => {
				if (isProcessing) return

				setIsProcessing(true)

				try {
					const assistantMessageId = Date.now().toString()
					const loadingAssistantMessage: Message = {
						id: assistantMessageId,
						role: "assistant",
						content: "",
						status: "loading",
					}

					setMessages((prev) => [...prev, loadingAssistantMessage])
					setProcessingMessageId(assistantMessageId)

					const result = await sendAgentMessage(content, {
						conversationId: conversationId || "temp-conversation",
						includeFlowData: false,
					})

					if (result.isError) {
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
						return
					}

					if (result.stream) {
						setStreamResponse(result.stream)
					} else {
						setMessages((prev) =>
							prev.map((msg) =>
								msg.id === assistantMessageId
									? {
											...msg,
											content: result.contentStr || "No content",
											status: "done",
									  }
									: msg,
							),
						)
						setIsProcessing(false)
					}
				} catch (error) {
					console.error("Error processing instruction:", error)
					const errorMessage = error instanceof Error ? error.message : String(error)

					setMessages((prev) =>
						prev.map((msg) =>
							msg.id === processingMessageId
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
				}
			},
		})

	useTestFunctions({
		setMessages,
		setProcessingMessageId,
		setCommandQueue,
		setIsCommandProcessing,
		commandQueue,
		setStreamResponse,
		setIsProcessing,
	})

	useEffect(() => {
		if (!conversationId) {
			setConversationId(generateConversationId(flow?.id || ""))
		}
	}, [flow?.id, conversationId])

	const scrollToBottom = useMemoizedFn(() => {
		if (forceScroll || !userScrolling) {
			messagesEndRef.current?.scrollIntoView({ behavior: "smooth" })
			if (forceScroll) {
				setForceScroll(false)
			}
		}
	})

	useEffect(() => {
		if (messages.length > lastMessageCountRef.current) {
			setForceScroll(true)
			lastMessageCountRef.current = messages.length
		}
		scrollToBottom()
	}, [messages, scrollToBottom])

	const handleMessagesScroll = useMemoizedFn((e: React.UIEvent<HTMLDivElement>) => {
		const element = e.currentTarget

		if (scrollTimeoutRef.current) {
			clearTimeout(scrollTimeoutRef.current)
		}

		const isAtBottom = element.scrollHeight - element.scrollTop <= element.clientHeight + 100

		if (!isAtBottom) {
			setUserScrolling(true)

			scrollTimeoutRef.current = setTimeout(() => {
				const currentElement = messagesEndRef.current?.parentElement
				if (currentElement) {
					const currentIsAtBottom =
						currentElement.scrollHeight - currentElement.scrollTop <=
						currentElement.clientHeight + 100

					if (currentIsAtBottom) {
						setUserScrolling(false)
					}
				}
			}, 5000)
		} else if (userScrolling && isAtBottom) {
			setUserScrolling(false)
		}
	})

	useEffect(() => {
		return () => {
			if (scrollTimeoutRef.current) {
				clearTimeout(scrollTimeoutRef.current)
			}
		}
	}, [])

	const savePosition = useMemoizedFn((pos: typeof position) => {
		try {
			localStorage.setItem(STORAGE_KEY.POSITION, JSON.stringify(pos))
		} catch (e) {
			console.error("Error:", e)
		}
	})

	const saveSize = useMemoizedFn((newSize: typeof size) => {
		try {
			localStorage.setItem(STORAGE_KEY.SIZE, JSON.stringify(newSize))
		} catch (e) {
			console.error("Error:", e)
		}
	})

	const handleCommandUpdate = useMemoizedFn((messageId: string, commandStatus: any[]) => {
		setMessages((prev) =>
			prev.map((msg) =>
				msg.id === messageId
					? {
							...msg,
							commandStatus: [
								...(msg.commandStatus || []).filter(
									(cmd) =>
										!commandStatus.some((newCmd) => newCmd.type === cmd.type),
								),
								...commandStatus,
							],
					  }
					: msg,
			),
		)
	})

	const handleCommandComplete = useMemoizedFn(() => {
		setIsCommandProcessing(false)
		setCommandQueue([])
	})

	const handleTextUpdate = useMemoizedFn((text: string) => {
		if (!processingMessageId) return

		console.log(
			`handleTextUpdate: processingMessageId=${processingMessageId}, text length=${text.length}`,
		)

		setMessages((prev) =>
			prev.map((msg) =>
				msg.id === processingMessageId ? { ...msg, content: text, status: "loading" } : msg,
			),
		)
	})

	const handleResizeStop = useMemoizedFn((e, direction, ref, d) => {
		const newSize = {
			width: size.width + d.width,
			height: size.height + d.height,
		}
		setSize(newSize)
		saveSize(newSize)
	})

	const handleDragStart = useMemoizedFn((e: React.MouseEvent) => {
		e.preventDefault()
		setIsDragging(true)
		dragStartRef.current = { x: e.clientX, y: e.clientY }
	})

	const handleDragMove = useMemoizedFn((e: MouseEvent) => {
		if (!isDragging) return

		const deltaX = e.clientX - dragStartRef.current.x
		const deltaY = e.clientY - dragStartRef.current.y

		const currentX =
			typeof positionRef.current.x === "number"
				? positionRef.current.x
				: window.innerWidth - DEFAULT_WIDTH - 34
		const currentY =
			typeof positionRef.current.y === "number"
				? positionRef.current.y
				: window.innerHeight * 0.2

		const newX = currentX + deltaX
		const newY = currentY + deltaY

		const boundedX = Math.max(0, Math.min(window.innerWidth - size.width, newX))
		const boundedY = Math.max(0, Math.min(window.innerHeight - 100, newY))

		const newPosition = { x: boundedX, y: boundedY }
		positionRef.current = newPosition
		setPosition(newPosition)

		savePosition(newPosition)

		dragStartRef.current = { x: e.clientX, y: e.clientY }
	})

	const handleDragEnd = useMemoizedFn(() => {
		setIsDragging(false)
	})

	useEffect(() => {
		if (isDragging) {
			document.addEventListener("mousemove", handleDragMove)
			document.addEventListener("mouseup", handleDragEnd)
		}

		return () => {
			document.removeEventListener("mousemove", handleDragMove)
			document.removeEventListener("mouseup", handleDragEnd)
		}
	}, [isDragging, handleDragMove, handleDragEnd])

	const handleCommandProcessingStatusChange = useMemoizedFn((processStatus: boolean) => {
		setIsCollectingCommand(processStatus)

		if (processingMessageId) {
			setMessages((prev) =>
				prev.map((msg) =>
					msg.id === processingMessageId
						? {
								...msg,
								isCollectingCommand: processStatus,
								content: processStatus
								? `${msg.content}\n\n[processing commands...]`
									: msg.content,
						  }
						: msg,
				),
			)
		}
	})

	const handleCommandsReceived = useMemoizedFn((commands: any[]) => {
		if (!processingMessageId || commands.length === 0) return

		console.log("commands:", commands)

		for (const command of commands) {
			if (command.type === "confirmOperation") {
				console.log("found confirmation operation command:", command)
				handleConfirmOperationCommand(command, processingMessageId)
				return
			}
		}

		setCommandQueue((prev) => {
			// Filter out duplicate commands (based on type and other key properties)
		const newCommands = commands.filter(
			(newCmd) =>
				!prev.some(
					(existing) =>
						existing.type === newCmd.type &&
						JSON.stringify(existing) === JSON.stringify(newCmd),
				),
		)

		if (newCommands.length === 0) return prev
		return [...prev, ...newCommands]
	})
})

const handleStreamError = useMemoizedFn((errorMessage: string) => {
	if (!processingMessageId) return
	setIsProcessing(false)
	setMessages((prev) =>
		prev.map((msg) =>
			msg.id === processingMessageId
				? { ...msg, content: errorMessage, status: "error" }
				: msg,
		),
	)
})

const handleStreamComplete = useMemoizedFn(() => {
	console.log(`handleStreamComplete called, processingMessageId=${processingMessageId}`)
	setIsProcessing(false)
	setStreamResponse(null)

	if (processingMessageId) {
		setMessages((prev) =>
			prev.map((msg) => {
				if (msg.id === processingMessageId) {
					console.log(
						`Updating message ${msg.id} status to done, current status: ${msg.status}, content: ${msg.content}`,
					)
					return {
						...msg,
						status: "done",
						confirmOperation: msg.confirmOperation || undefined,
					}
			}
			return msg
		}),
	)
}

setProcessingMessageId(null)
})

const handleRetry = useMemoizedFn(async (messageId: string) => {
	const errorMessage = messages.find(
		(msg) => msg.id === messageId && msg.status === "error" && msg.role === "assistant",
	)

	if (!errorMessage) return

	const errorIndex = messages.findIndex((msg) => msg.id === messageId)
	if (errorIndex <= 0) return

	let userMessageIndex = errorIndex - 1
	while (userMessageIndex >= 0) {
		if (messages[userMessageIndex].role === "user") {
			break
		}
		userMessageIndex--
	}

	if (userMessageIndex < 0) return

	const userMessage = messages[userMessageIndex]

	if (isProcessing) {
		antdMessage.info(t("flowAssistant.waitForProcessing", { ns: "flow" }))
		return
	}

	setMessages((prev) => prev.filter((msg) => msg.id !== messageId))

	const newAssistantMessageId = Date.now().toString()
	const loadingAssistantMessage: Message = {
		id: newAssistantMessageId,
		role: "assistant",
		content: "",
		status: "loading",
	}

	setMessages((prev) => [...prev, loadingAssistantMessage])
	setForceScroll(true)
	setProcessingMessageId(newAssistantMessageId)
	setIsProcessing(true)

	try {
		const result = await sendAgentMessage(userMessage.content, {
			conversationId: conversationId || "temp-conversation",
			includeFlowData: false,
			})

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
				return
			}

			if (result.stream) {
				setStreamResponse(result.stream)
			} else {
				setMessages((prev) =>
					prev.map((msg) =>
						msg.id === newAssistantMessageId
							? {
									...msg,
									content: result.contentStr || "No content",
									status: "done",
							  }
							: msg,
					),
				)
				setIsProcessing(false)
			}
		} catch (error) {
			console.error("Error retrying message:", error)
			const errorMessage = error instanceof Error ? error.message : String(error)

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

			antdMessage.error(t("flowAssistant.retryError", { ns: "flow" }))
			setIsProcessing(false)
		}
	})

	const handleAbortStream = useMemoizedFn(() => {
		if (streamResponse) {
			console.log("Abort SSE stream")

			setStreamResponse(null)
			setIsProcessing(false)

			if (processingMessageId) {
				setMessages((prev) =>
					prev.map((msg) =>
						msg.id === processingMessageId
							? {
									...msg,
									content: msg.content + "\n\n[stopped]",
									status: "done",
							  }
							: msg,
					),
				)
				setProcessingMessageId(null)
			}

			antdMessage.info(
				t("flowAssistant.messageStopped", { ns: "flow", defaultValue: "Message stopped" }),
			)
		}
	})

	const sendMessage = useMemoizedFn(async () => {
		if (!inputValue.trim() || isProcessing) return

		setCommandQueue([])

		const userMessage = inputValue.trim()
		setInputValue("")

		const newUserMessage: Message = {
			id: Date.now().toString(),
			role: "user",
			content: userMessage,
			status: "done",
		}

		const assistantMessageId = (Date.now() + 1).toString()
		const loadingAssistantMessage: Message = {
			id: assistantMessageId,
			role: "assistant",
			content: "",
			status: "loading",
		}

		setMessages((prev) => [...prev, newUserMessage, loadingAssistantMessage])
		setForceScroll(true)
		lastMessageCountRef.current = messages.length + 2

		setIsProcessing(true)

		try {
			const result = await sendAgentMessage(userMessage, {
				conversationId: conversationId || "temp-conversation",
				includeFlowData: true,
				getFetchFlowData: getFlowData,
			})

			if (result.isError) {
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
				return
			}

			if (result.stream) {
				setStreamResponse(result.stream)
				setProcessingMessageId(assistantMessageId)
			} else if (result.contentStr) {
				setMessages((prev) =>
					prev.map((msg) =>
						msg.id === assistantMessageId
							? {
									...msg,
									content: result.contentStr || "No content",
									status: "done",
							  }
							: msg,
					),
				)
				setIsProcessing(false)
			}
		} catch (error) {
			console.error("Error processing instruction:", error)
			const errorMessage = error instanceof Error ? error.message : String(error)

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
		}
	})

	return (
		<Resizable
			className={styles.flowAssistant}
			style={{
				position: "fixed",
				top: position.y,
				left: position.x,
				right: position.x === undefined ? "34px" : "auto",
			}}
			size={{ width: size.width, height: size.height }}
			minWidth={MIN_WIDTH}
			minHeight={MIN_HEIGHT}
			maxWidth={MAX_WIDTH}
			maxHeight={MAX_HEIGHT}
			onResizeStop={handleResizeStop}
			enable={{
				top: false,
				right: true,
				bottom: true,
				left: true,
				topRight: false,
				bottomRight: true,
				bottomLeft: true,
				topLeft: false,
			}}
			handleStyles={{
				right: { width: "6px", right: "0", cursor: "ew-resize" },
				left: { width: "6px", left: "0", cursor: "ew-resize" },
				bottom: { height: "6px", bottom: "0", cursor: "ns-resize" },
				bottomRight: {
					width: "12px",
					height: "12px",
					right: "0",
					bottom: "0",
					cursor: "nwse-resize",
				},
				bottomLeft: {
					width: "12px",
					height: "12px",
					left: "0",
					bottom: "0",
					cursor: "nesw-resize",
				},
			}}
			handleClasses={{
				right: styles.resizeHandle,
				bottom: styles.resizeHandle,
				left: styles.resizeHandle,
				bottomRight: styles.resizeCornerHandle,
				bottomLeft: styles.resizeCornerHandle,
			}}
		>
			{/* StreamProcessor renders streaming responses */}

			<StreamProcessor
				responseBody={streamResponse}
				messageId={processingMessageId!}
				onTextUpdate={handleTextUpdate}
				onCommandsReceived={handleCommandsReceived}
				onError={handleStreamError}
				onComplete={handleStreamComplete}
				userScrolling={userScrolling}
				onCommandProcessingStatusChange={handleCommandProcessingStatusChange}
			/>

			{/* CommandProcessor executes flow commands */}

			<CommandProcessor
				commands={commandQueue}
				messageId={processingMessageId!}
				executeOperations={flowOperations.executeOperations}
				flowId={flow?.id || ""}
				commandExecutionRef={flowOperations.commandExecutionRef}
				onCommandUpdate={handleCommandUpdate}
				onComplete={handleCommandComplete}
			/>

			<Card
				title={
					<div
						className={styles.titleContainer}
						onMouseDown={handleDragStart}
						style={{ cursor: "move", width: "100%" }}
					>
						{t("flowAssistant.title", { ns: "flow" })}
					</div>
				}
				extra={
					<Button
						type="text"
						onClick={onClose}
						icon={<IconX size={16} className={styles.closeIcon} />}
					/>
				}
				className={styles.card}
				styles={{ body: { padding: 0 } }}
				variant="borderless"
			>
				<div className={styles.messageContainer} onScroll={handleMessagesScroll}>
					<List
						itemLayout="horizontal"
						dataSource={messages}
						renderItem={(item: Message) => (
							<List.Item style={{ padding: 0, border: "none" }}>
								<MessageItem
									{...item}
									onConfirm={handleConfirmOperation}
									onCancel={handleCancelOperation}
									onRetry={handleRetry}
								/>
							</List.Item>
						)}
					/>
					<div ref={messagesEndRef} />
				</div>

				<div className={styles.inputContainer}>
					<TextArea
						value={inputValue}
						onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) =>
							setInputValue(e.target.value)
						}
						placeholder={t("flowAssistant.inputPlaceholder", { ns: "flow" })}
						autoSize={{ minRows: 1, maxRows: 4 }}
						disabled={isProcessing}
						onPressEnter={(e: React.KeyboardEvent<HTMLTextAreaElement>) => {
							if (!e.shiftKey) {
								e.preventDefault()
								sendMessage()
							}
						}}
					/>
					{isProcessing ? (
						<Button
							type="primary"
							danger
							icon={<IconBan size={16} />}
							onClick={handleAbortStream}
						/>
					) : (
						<Button
							type="primary"
							icon={<IconSend size={16} />}
							onClick={sendMessage}
							disabled={!inputValue.trim()}
						/>
					)}
				</div>
			</Card>
		</Resizable>
	)
}






