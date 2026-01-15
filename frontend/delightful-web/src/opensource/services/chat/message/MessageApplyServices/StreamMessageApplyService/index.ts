import type { StreamResponse, SeqResponse } from "@/types/request"
import { StreamStatus } from "@/types/request"
import type {
	AggregateAISearchCardConversationMessage,
	ConversationMessage,
	MarkdownConversationMessage,
	TextConversationMessage,
} from "@/types/chat/conversation_message"
import { ConversationMessageType } from "@/types/chat/conversation_message"
import Logger from "@/utils/log/Logger"
import { isUndefined } from "lodash-es"
import AiSearchApplyService from "../ChatMessageApplyServices/AiSearchApplyService"
import MessageService from "../../MessageService"
import type { StreamMessageTask } from "./types"
import { sliceMessage } from "./utils"
import ConversationService from "../../../conversation/ConversationService"

const console = new Logger("StreamMessageApplyService", "blue", { console: false })

/**
 * Streaming message manager
 * @deprecated Deprecated, use StreamMessageApplyServiceV2 instead
 *
 * @class StreamMessageApplyService
 */
class StreamMessageApplyService {
	taskMap: Record<string, StreamMessageTask>

	messageConversationMap: Record<
		string,
		{
			conversationId: string
			topicId: string
			messageId: string
			type: ConversationMessageType
		}
	> = {}

	constructor() {
		this.taskMap = {}
	}

	/**
	 * Record message info
	 * @param message Message
	 */
	recordMessageInfo(
		message:
			| SeqResponse<ConversationMessage>
			| SeqResponse<AggregateAISearchCardConversationMessage<true>>,
	) {
		switch (message.message.type) {
			case ConversationMessageType.Text:
				this.messageConversationMap[message.message_id] = {
					conversationId: message.conversation_id,
					topicId: message.message.topic_id ?? "",
					messageId: message.message_id,
					type: message.message.type,
				}
				break
			case ConversationMessageType.Markdown:
				this.messageConversationMap[message.message_id] = {
					conversationId: message.conversation_id,
					topicId: message.message.topic_id ?? "",
					messageId: message.message_id,
					type: message.message.type,
				}
				break
			case ConversationMessageType.AggregateAISearchCard:
				this.messageConversationMap[message.message.app_message_id] = {
					conversationId: message.conversation_id,
					topicId: message.message.topic_id ?? "",
					messageId: message.message_id,
					type: message.message.type,
				}
				break
			default:
				break
		}
	}

	/**
	 * Query message info
	 * @param messageId Message ID
	 * @returns Message info
	 */
	queryMessageInfo(messageId: string) {
		return this.messageConversationMap[messageId]
	}

	/**
	 * Add a task
	 * @param messageId Message ID
	 * @param message Message
	 */
	addToTaskMap(messageId: string, message: StreamResponse, run: boolean = true) {
		console.log(
			`[addToTaskMap] Start adding task, message ID: ${messageId}, execute immediately: ${run}`,
		)
		const task = this.taskMap[messageId]

		if (task) {
			// Split message into small chunks and enqueue
			const slicedMessages = sliceMessage(message)
			task.tasks.push(...slicedMessages)

			if (task.status === "init") {
				task.status = "doing"
			}

			if (!task.triggeredRender && run) {
				this.executeType(messageId)
			}
		} else {
			// Create a new task and add message
			const slicedMessages = sliceMessage(message)
			this.taskMap[messageId] = {
				status: "init",
				tasks: slicedMessages,
				triggeredRender: false,
			}

			if (run) {
				this.executeType(messageId)
			}
		}
	}

	/**
	 * Check if a task exists
	 * @param messageId Message ID
	 * @returns Whether task exists
	 */
	hasTask(messageId: string) {
		return this.taskMap[messageId]
	}

	/**
	 * Finish a task
	 * @param messageId Message ID
	 */
	finishTask(messageId: string) {
		const task = this.taskMap[messageId]
		if (task) {
			task.status = "done"
		}
	}

	/**
	 * Create a task object
	 * @param message Message
	 * @returns Task object
	 */
	static createObject(message?: StreamResponse): StreamMessageTask {
		return {
			status: "init",
			tasks: message ? sliceMessage(message) : [],
			triggeredRender: false,
		}
	}

	/**
	 * Remove a task from the list
	 * @param messageId Message ID
	 */
	removeFromTaskMap(messageId: string) {
		delete this.taskMap[messageId]
	}

	/**
	 * Change task ID
	 * @param oldMessageId Old message ID
	 * @param newMessageId New message ID
	 */
	changeTaskId(oldMessageId: string, newMessageId: string) {
		this.taskMap[newMessageId] = this.taskMap[oldMessageId]
		delete this.taskMap[oldMessageId]
	}

	/**
	 * Execute a task
	 * @param messageId Message ID
	 */
	executeType(messageId: string) {
		console.log(`[executeType] Start executing task, message ID: ${messageId}`)
		const task = this.taskMap[messageId]
		if (task) {
			task.triggeredRender = true
			console.log(
				`[executeType] Task status: ${task.status}, remaining tasks: ${task.tasks.length}`,
			)

			// Create character buffer for smooth output
			const buffer: StreamResponse[] = []
			// Base interval (ms) to control output speed
			const baseInterval = 10
			// Last output timestamp
			let lastOutputTime = Date.now()
			// Character output interval (ms)
			let outputInterval = baseInterval
			// Preload threshold: refill buffer when below this
			const bufferThreshold = 10
			// Average output rate (chars/sec)
			const avgOutputRate = 40
			// Window size for average speed calculation
			const speedWindowSize = 20
			// Recent output intervals
			const recentIntervals: number[] = []

			const processNextChunk = () => {
				const now = Date.now()
				const timeSinceLastOutput = now - lastOutputTime

				// Refill the buffer if needed
				if (buffer.length < bufferThreshold && task.tasks.length > 0) {
					// Move new chunks from task queue to buffer
					const newChars = task.tasks.splice(0, Math.min(30, task.tasks.length))
					buffer.push(...newChars)
				}

				// Handle output logic
				if (buffer.length > 0 && timeSinceLastOutput >= outputInterval) {
					// Dequeue one chunk and process
					const message = buffer.shift()
					if (message) {
						this.appendStreamMessage(messageId, message)
					}

					// Update timestamp
					lastOutputTime = now

					// Adjust interval based on content complexity
					const contentLength =
						message?.content?.length ||
						message?.llm_response?.length ||
						message?.reasoning_content?.length ||
						0

					// Smooth typewriter effect by adjusting interval
					// Record current interval
					recentIntervals.push(outputInterval)
					// Keep window size fixed
					if (recentIntervals.length > speedWindowSize) {
						recentIntervals.shift()
					}

					// Calculate average interval for smoother output
					const avgInterval =
						recentIntervals.reduce((sum, val) => sum + val, 0) /
						(recentIntervals.length || 1)

					// Dynamically adjust output interval
					if (task.status === "done") {
						// Task finished: slightly speed up but keep smooth
						outputInterval = Math.max(10, avgInterval * 0.9)
					} else {
						// Adjust based on content length and avg rate
						const targetInterval = 1000 / avgOutputRate // Target interval time
						// Smooth transition between avg and target interval
						outputInterval = avgInterval * 0.7 + targetInterval * 0.3
						// Content length effect: slightly extend for longer chunks
						if (contentLength > 1) {
							outputInterval = Math.min(outputInterval * 1.2, 80)
						}
						// Ensure interval within reasonable bounds
						outputInterval = Math.max(15, Math.min(outputInterval, 50))
					}
				}

				// Continue processing or end condition
				if (buffer.length > 0 || task.tasks.length > 0 || task.status !== "done") {
					// Use requestAnimationFrame for smoother updates
					requestAnimationFrame(() => {
						processNextChunk()
					})
				} else if (
					task.status === "done" &&
					buffer.length === 0 &&
					task.tasks.length === 0
				) {
					// All tasks completed, mark stream end
					this.removeFromTaskMap(messageId)
				}
			}

			// Start processing
			processNextChunk()
		} else {
			console.log(`[executeType] Task does not exist, creating new task: ${messageId}`)
			this.taskMap[messageId] = StreamMessageApplyService.createObject()
		}
	}

	/**
	 * Append streaming message
	 * @param targetId Target ID
	 * @param message Message
	 */
	appendStreamMessage(targetId: string, message: StreamResponse) {
		const { content, reasoning_content, llm_response } = message
		const targetSeqInfo = this.queryMessageInfo(targetId)
		const aISearchSeqInfo = this.queryMessageInfo(
			AiSearchApplyService.getAppMessageIdByLLMResponseSeqId(targetId),
		)

		switch (true) {
			case Boolean(content):
				if (!targetSeqInfo) return
				MessageService.updateMessage(
					targetSeqInfo.conversationId,
					targetSeqInfo.topicId,
					targetSeqInfo.messageId,
					(m) => {
						const textMessage = m.message as TextConversationMessage
						if (textMessage.text) {
							textMessage.text.content = (textMessage.text.content || "") + content
						}
						return m
					},
				)
				break
			case Boolean(llm_response):
				// Update AI search cache data
				AiSearchApplyService.appendContent(targetId, llm_response)
				break
			case Boolean(reasoning_content):
				if (targetSeqInfo) {
					MessageService.updateMessage(
						targetSeqInfo.conversationId,
						targetSeqInfo.topicId,
						targetSeqInfo.messageId,
						(m) => {
							const textMessage = m.message as
								| TextConversationMessage
								| MarkdownConversationMessage
							switch (true) {
								case textMessage.type === ConversationMessageType.Text:
									if (textMessage.text) {
										textMessage.text.reasoning_content =
											(textMessage.text.reasoning_content || "") +
											reasoning_content
										if (textMessage.text.stream_options) {
											textMessage.text.stream_options.status =
												StreamStatus.Streaming
											textMessage.text.stream_options.stream = true
										}
									}
									break
								case textMessage.type === ConversationMessageType.Markdown:
									if (textMessage.markdown) {
										textMessage.markdown.reasoning_content =
											(textMessage.markdown.reasoning_content || "") +
											reasoning_content
										if (textMessage.markdown.stream_options) {
											textMessage.markdown.stream_options.status =
												StreamStatus.Streaming
											textMessage.markdown.stream_options.stream = true
										}
									}
									break
								default:
									break
							}
							return m
						},
					)
				} else if (aISearchSeqInfo) {
					AiSearchApplyService.appendReasoningContent(targetId, reasoning_content)
				}
				break
			default:
				break
		}
	}

	apply(streamMessage: StreamResponse) {
		console.log(
			`[apply] Start applying stream message, target seq ID: ${streamMessage.target_seq_id}`,
		)

		const targetSeqInfo = this.queryMessageInfo(streamMessage.target_seq_id)
		const aggregateAISearchCardSeqInfo = this.queryMessageInfo(
			AiSearchApplyService.getAppMessageIdByLLMResponseSeqId(streamMessage.target_seq_id),
		)

		if (!targetSeqInfo && !aggregateAISearchCardSeqInfo) return

		const type = targetSeqInfo?.type ?? aggregateAISearchCardSeqInfo?.type

		switch (type) {
			case ConversationMessageType.Text:
				console.log(`[apply] Handle text message type`)
				this.applyTextStreamMessage(streamMessage)
				break
			case ConversationMessageType.Markdown:
				console.log(`[apply] Handle Markdown message type`)
				this.applyMarkdownStreamMessage(streamMessage)
				break
			case ConversationMessageType.AggregateAISearchCard:
				console.log(`[apply] Handle AI search card message type`)
				this.applyAggregateAISearchCardStreamMessage(streamMessage)
				break
			default:
				console.log(`[apply] Unknown message type, using default handler`)
		}
	}

	/**
	 * Apply text streaming message
	 * @param streamMessage Streaming message
	 */
	applyTextStreamMessage(streamMessage: StreamResponse) {
		console.log(
			`[applyTextStreamMessage] Start handling text stream message, status: ${streamMessage.status}`,
		)
		const { target_seq_id, reasoning_content, status, content } = streamMessage
		const { messageId, conversationId, topicId } = this.queryMessageInfo(target_seq_id)!

		if ([StreamStatus.Start, StreamStatus.Streaming].includes(status)) {
			if (reasoning_content) {
				console.log(`[applyTextStreamMessage] Handle reasoning content`)
				this.addToTaskMap(target_seq_id, streamMessage)
			} else if (content) {
				console.log(`[applyTextStreamMessage] Handle content`)
				this.addToTaskMap(target_seq_id, streamMessage)
			}
		} else if (status === StreamStatus.End) {
			console.log(`[applyTextStreamMessage] Handle end status message`)

			// Update message status
			MessageService.updateMessage(conversationId, topicId, messageId, (m) => {
				const textMessage = m.message as TextConversationMessage
				if (textMessage.text) {
					if (textMessage.text.stream_options) {
						textMessage.text.stream_options.status = StreamStatus.End
					}
				}
				return m
			})

			// Update last message
			ConversationService.updateLastReceiveMessage(conversationId, {
				time: Date.now(),
				seq_id: messageId,
				type: ConversationMessageType.Text,
				text: content.slice(0, 50),
				topic_id: topicId,
			})

			// Persist to DB
			MessageService.updateDbMessage(messageId, conversationId, {
				"message.text.content": content,
				"message.text.reasoning_content": reasoning_content,
				"message.text.stream_options.status": StreamStatus.End,
			} as Partial<SeqResponse<ConversationMessage>>)
		}
		this.finishTask(target_seq_id)
		console.log(`[applyTextStreamMessage] Text stream message handling completed`)
	}

	/**
	 * Apply markdown streaming message
	 * @param streamMessage Streaming message
	 */
	applyMarkdownStreamMessage(streamMessage: StreamResponse) {
		console.log(
			`[applyMarkdownStreamMessage] Start handling Markdown stream message, status: ${streamMessage.status}`,
		)
		const { reasoning_content, content, status, target_seq_id } = streamMessage
		const { messageId, conversationId, topicId } = this.queryMessageInfo(target_seq_id)!

		if ([StreamStatus.Streaming, StreamStatus.Start].includes(status)) {
			if (reasoning_content) {
				console.log(`[applyMarkdownStreamMessage] Handle reasoning content`)
				this.addToTaskMap(target_seq_id, streamMessage)
			} else if (content) {
				console.log(`[applyMarkdownStreamMessage] Handle content`)
				this.addToTaskMap(target_seq_id, streamMessage)
			}
		} else if (status === StreamStatus.End) {
			console.log(`[applyMarkdownStreamMessage] Handle end status message`)
			// Update message status
			MessageService.updateMessage(conversationId, topicId, messageId, (m) => {
				const markdownMessage = m.message as MarkdownConversationMessage
				if (markdownMessage.markdown) {
					// Note: Do not update content here; streaming updates handle it
					if (markdownMessage.markdown.stream_options) {
						markdownMessage.markdown.stream_options.status = StreamStatus.End
					}
				}
				return m
			})

			// Update last message
			ConversationService.updateLastReceiveMessage(conversationId, {
				time: Date.now(),
				seq_id: messageId,
				type: ConversationMessageType.Markdown,
				text: content.slice(0, 50),
				topic_id: topicId,
			})

			// Persist to DB
			MessageService.updateDbMessage(messageId, conversationId, {
				"message.markdown.content": content,
				"message.markdown.reasoning_content": reasoning_content,
				"message.markdown.stream_options.status": StreamStatus.End,
			} as Partial<SeqResponse<ConversationMessage>>)
		}
		this.finishTask(target_seq_id)
		console.log(`[applyMarkdownStreamMessage] Markdown stream message handling completed`)
	}

	/**
	 * Apply Aggregate AI Search Card streaming message
	 * @param streamMessage Streaming message
	 */
	applyAggregateAISearchCardStreamMessage(message: StreamResponse) {
		console.log(
			`[applyAggregateAISearchCardStreamMessage] Start handling AI search card stream message, status: ${message.status}`,
		)
		const { reasoning_content, llm_response } = message
		const { status, target_seq_id } = message
		const { messageId, conversationId, topicId } = this.queryMessageInfo(
			AiSearchApplyService.getAppMessageIdByLLMResponseSeqId(target_seq_id),
		)!

		if (!isUndefined(status) && [StreamStatus.Streaming, StreamStatus.Start].includes(status)) {
			if (reasoning_content) {
				console.log(`[applyAggregateAISearchCardStreamMessage] Handle reasoning content`)
				this.addToTaskMap(target_seq_id, message)
			} else if (llm_response) {
				console.log(`[applyAggregateAISearchCardStreamMessage] Handle LLM response content`)
				this.addToTaskMap(target_seq_id, message)
			}
		} else if (status === StreamStatus.End) {
			console.log(`[applyAggregateAISearchCardStreamMessage] Handle end status message`)

			// Update root question answer
			MessageService.updateMessage(conversationId, topicId, messageId, (m) => {
				const textMessage = m.message as AggregateAISearchCardConversationMessage
				if (textMessage.aggregate_ai_search_card) {
					// Note: Do not update content here; streaming updates handle it
					if (textMessage.aggregate_ai_search_card.stream_options) {
						textMessage.aggregate_ai_search_card.stream_options.status =
							StreamStatus.End
					}
				}
				return m
			})

			// Update last message
			ConversationService.updateLastReceiveMessage(conversationId, {
				time: Date.now(),
				seq_id: messageId,
				type: ConversationMessageType.AggregateAISearchCard,
				text: llm_response.slice(0, 50),
				topic_id: topicId,
			})

			// Persist to DB
			MessageService.updateDbMessage(messageId, conversationId, {
				"message.aggregate_ai_search_card.llm_response": llm_response,
				"message.aggregate_ai_search_card.reasoning_content": reasoning_content,
				"message.aggregate_ai_search_card.stream_options.status": StreamStatus.End,
			} as Partial<SeqResponse<ConversationMessage>>)

			this.finishTask(target_seq_id)
		}
		console.log(
			`[applyAggregateAISearchCardStreamMessage] AI search card stream message handling completed`,
		)
		if ([StreamStatus.Start, StreamStatus.Streaming].includes(status)) {
			if (reasoning_content) {
				this.addToTaskMap(target_seq_id, streamMessage, false)
			} else if (content) {
				this.addToTaskMap(target_seq_id, streamMessage, false)
			}
		} else if (status === StreamStatus.End) {
			this.finishTask(target_seq_id)
		}
	}
}

export default new StreamMessageApplyService()
