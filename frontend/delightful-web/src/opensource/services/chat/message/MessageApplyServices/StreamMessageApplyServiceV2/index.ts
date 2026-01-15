import type { SeqResponse, StreamResponseV2 } from "@/types/request"
import { StreamStatus } from "@/types/request"
import type {
	AggregateAISearchCardConversationMessage,
	AggregateAISearchCardConversationMessageV2,
	ConversationMessage,
	MarkdownConversationMessage,
	TextConversationMessage,
} from "@/types/chat/conversation_message"
import {
	AggregateAISearchCardV2Status,
	ConversationMessageType,
} from "@/types/chat/conversation_message"
import Logger from "@/utils/log/Logger"
import { isUndefined } from "lodash-es"
import AiSearchApplyService from "../ChatMessageApplyServices/AiSearchApplyService"
import MessageService from "../../MessageService"
import { appendObject, updateAggregateAISearchCardV2Status } from "./utils"
import ConversationService from "../../../conversation/ConversationService"
import { toJS } from "mobx"

const console = new Logger("StreamMessageApplyServiceV2", "blue", { console: false })

/**
 * Streaming message manager.
 *
 * @class StreamMessageApplyService
 */
class StreamMessageApplyServiceV2 {
	messageConversationMap: Record<
		string,
		{
			conversationId: string
			topicId: string
			messageId: string
			type: ConversationMessageType
		}
	> = {}

	/**
	 * Temporary cache for streaming messages.
	 */
	streamMessageMap: Record<string, StreamResponseV2[]> = {}

	/**
	 * Cache a streaming message temporarily.
	 * @param streamMessage The streaming message.
	 */
	addCacheStreamMessage(streamMessage: StreamResponseV2) {
		if (!this.streamMessageMap[streamMessage.target_seq_id]) {
			this.streamMessageMap[streamMessage.target_seq_id] = []
		}
		this.streamMessageMap[streamMessage.target_seq_id].push(streamMessage)
	}

	/**
	 * Check if a cached streaming message exists for a target seq_id.
	 * @param targetSeqId Target sequence ID.
	 * @returns Whether cached streaming messages exist.
	 */
	hasCacheStreamMessage(targetSeqId: string) {
		if (!this.streamMessageMap[targetSeqId]) {
			return false
		}
		return this.streamMessageMap[targetSeqId].length > 0
	}

	/**
	 * Apply cached streaming messages for a target seq_id.
	 * @param targetSeqId Target sequence ID.
	 */
	applyCacheStreamMessage(
		targetSeqId: string,
		applyFn: (streamMessage: StreamResponseV2) => void,
	) {
		if (
			!this.streamMessageMap[targetSeqId] ||
			this.streamMessageMap[targetSeqId].length === 0
		) {
			return
		}

		const streamMessages = this.streamMessageMap[targetSeqId].slice()
		// Remove cached streaming messages
		delete this.streamMessageMap[targetSeqId]

		streamMessages.forEach((streamMessage) => {
			applyFn(streamMessage)
		})
	}

	/**
	 * Record message info for later lookup during streaming updates.
	 * @param message Message whose identifiers to record.
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
			case ConversationMessageType.AggregateAISearchCardV2:
				this.messageConversationMap[message.message_id] = {
					conversationId: message.conversation_id,
					topicId: message.message.topic_id ?? "",
					messageId: message.message_id,
					type: message.message.type,
				}
				console.log(
					`[recordMessageInfo] Record AI search card V2 message info, messageId: ${message.message_id}, message:`,
					this.messageConversationMap[message.message_id],
				)
				break
			default:
				break
		}
	}

	/**
	 * Query recorded message info.
	 * @param messageId Message ID (or target seq_id for some message types).
	 * @returns Recorded message info.
	 */
	queryMessageInfo(messageId: string) {
		return this.messageConversationMap[messageId]
	}

	apply(streamMessage: StreamResponseV2) {
		console.log(`[apply] Start applying stream message, target seq ID: ${streamMessage.target_seq_id}`)

		const targetSeqInfo = this.queryMessageInfo(streamMessage.target_seq_id)
		const aggregateAISearchCardSeqInfo = this.queryMessageInfo(
			AiSearchApplyService.getAppMessageIdByLLMResponseSeqId(streamMessage.target_seq_id),
		)

		if (!targetSeqInfo && !aggregateAISearchCardSeqInfo) {
			console.log(`[apply] Message info not found, caching first, streamMessage:`, streamMessage)
			// If message info not found, cache first and apply after message info is updated
			this.addCacheStreamMessage(streamMessage)
			return
		}

		const type = targetSeqInfo?.type ?? aggregateAISearchCardSeqInfo?.type

		switch (type) {
			case ConversationMessageType.Text:
				console.log(`[apply] Handle text message type`)
				this.applyCacheStreamMessage(
					streamMessage.target_seq_id,
					this.applyTextStreamMessage,
				)
				this.applyTextStreamMessage(streamMessage)
				break
			case ConversationMessageType.Markdown:
				console.log(`[apply] Handle Markdown message type`)
				this.applyCacheStreamMessage(
					streamMessage.target_seq_id,
					this.applyMarkdownStreamMessage,
				)
				this.applyMarkdownStreamMessage(streamMessage)
				break
			case ConversationMessageType.AggregateAISearchCard:
				console.log(`[apply] Handle AI search card message type`)
				this.applyCacheStreamMessage(
					streamMessage.target_seq_id,
					this.applyAggregateAISearchCardStreamMessage,
				)
				this.applyAggregateAISearchCardStreamMessage(streamMessage)
				break
			case ConversationMessageType.AggregateAISearchCardV2:
				console.log(`[apply] Handle AI search card V2 message type`)
				this.applyCacheStreamMessage(
					streamMessage.target_seq_id,
					this.applyAggregateAISearchCardV2StreamMessage,
				)
				this.applyAggregateAISearchCardV2StreamMessage(streamMessage)
				break
			default:
			console.log(`[apply] Unknown message type`)
			break
	}
	}

	/**
	 * Apply streaming updates for Text messages.
	 * @param streamMessage Streaming payload.
	 */
	applyTextStreamMessage = (streamMessage: StreamResponseV2) => {
		const {
		streams: {
				stream_options: { status } = { status: StreamStatus.Streaming },
				...keyPaths
			},
			target_seq_id,
		} = streamMessage
	console.log(`[applyTextStreamMessage] Start handling text stream message, status: ${status}`)
	const { messageId, conversationId, topicId } = this.queryMessageInfo(target_seq_id)!

	if ([StreamStatus.Start, StreamStatus.Streaming].includes(status)) {
		MessageService.updateMessage(conversationId, topicId, messageId, (m) => {
			const textMessage = m.message as TextConversationMessage

			for (const keyPath of Object.keys(keyPaths)) {
				appendObject(textMessage.text, keyPath.split("."), keyPaths[keyPath])
			}

			return m
		})
	} else if (status === StreamStatus.End) {
		console.log(`[applyTextStreamMessage] Handle end status message`)
		MessageService.updateMessage(conversationId, topicId, messageId, (m) => {
			const textMessage = m.message as TextConversationMessage
			if (textMessage.text) {
				if (textMessage.text.stream_options) {
					textMessage.text.stream_options.status = StreamStatus.End
				}
			}
			return m
		})

		// Update conversation last message summary
		ConversationService.updateLastReceiveMessage(conversationId, {
				time: Date.now() / 1000,
				seq_id: messageId,
				type: ConversationMessageType.Text,
				text: (keyPaths.content as string).slice(0, 50),
				topic_id: topicId,
			})

			// Persist to DB
			MessageService.updateDbMessage(messageId, conversationId, {
				"message.text.content": keyPaths.content,
				"message.text.reasoning_content": keyPaths.reasoning_content,
				"message.text.stream_options.status": StreamStatus.End,
			} as Partial<SeqResponse<ConversationMessage>>)
		}
	console.log(`[applyTextStreamMessage] Text stream message handling completed`)
}

/**
 * Apply streaming updates for Markdown messages.
 * @param streamMessage Streaming payload.
 */
applyMarkdownStreamMessage = (streamMessage: StreamResponseV2) => {
		const {
			streams: {
				stream_options: { status } = { status: StreamStatus.Streaming },
				...keyPaths
			},
			target_seq_id,
		} = streamMessage
	console.log(`[applyMarkdownStreamMessage] Start handling Markdown stream message, status: ${status}`)

	const { messageId, conversationId, topicId } = this.queryMessageInfo(target_seq_id)

	if ([StreamStatus.Streaming, StreamStatus.Start].includes(status)) {
		MessageService.updateMessage(conversationId, topicId, messageId, (m) => {
			const textMessage = m.message as MarkdownConversationMessage

			for (const keyPath of Object.keys(keyPaths)) {
				appendObject(textMessage.markdown, keyPath.split("."), keyPaths[keyPath])
			}

			return { ...m }
		})
	} else if (status === StreamStatus.End) {
		console.log(`[applyMarkdownStreamMessage] Handle end status message`)
			// Update message status
			MessageService.updateMessage(conversationId, topicId, messageId, (m) => {
				const markdownMessage = m.message as MarkdownConversationMessage
				if (markdownMessage.markdown) {
					// Note: Do not update content here; typing effect updates during streaming
					if (markdownMessage.markdown.stream_options) {
						markdownMessage.markdown.stream_options.status = StreamStatus.End
					}
				}
				return { ...m }
			})

			// Update conversation last message summary
			ConversationService.updateLastReceiveMessage(conversationId, {
				time: Date.now() / 1000,
				seq_id: messageId,
				type: ConversationMessageType.Markdown,
				text: (keyPaths.content as string).slice(0, 50),
				topic_id: topicId,
			})

			// Persist to DB
			MessageService.updateDbMessage(messageId, conversationId, {
				"message.markdown.content": keyPaths.content,
				"message.markdown.reasoning_content": keyPaths.reasoning_content,
				"message.markdown.stream_options.status": StreamStatus.End,
			} as Partial<SeqResponse<ConversationMessage>>)
		}
	console.log(`[applyMarkdownStreamMessage] Markdown stream message handling completed`)
}

/**
 * Apply streaming updates for Aggregate AI Search Card messages.
 * @param streamMessage Streaming payload.
 */
applyAggregateAISearchCardStreamMessage = (message: StreamResponseV2) => {
	const {
		streams: {
				stream_options: { status } = { status: StreamStatus.Streaming },
				...keyPaths
			},
			target_seq_id,
		} = message

		console.log(
		`[applyAggregateAISearchCardStreamMessage] Start handling AI search card stream message, status: ${status}`,
	)

	const { messageId, conversationId, topicId } = this.queryMessageInfo(
		AiSearchApplyService.getAppMessageIdByLLMResponseSeqId(target_seq_id),
	)

	if (!isUndefined(status) && [StreamStatus.Streaming, StreamStatus.Start].includes(status)) {
		MessageService.updateMessage(conversationId, topicId, messageId, (m) => {
			const textMessage = m.message as AggregateAISearchCardConversationMessage<false>

			for (const keyPath of Object.keys(keyPaths)) {
				const keyPathsArray = keyPath.split(".")
				appendObject(
					textMessage.aggregate_ai_search_card,
					keyPathsArray,
					keyPaths[keyPath],
				)
			}

			return { ...m }
		})
	} else if (status === StreamStatus.End) {
		console.log(`[applyAggregateAISearchCardStreamMessage] Handle end status message`)
		MessageService.updateMessage(conversationId, topicId, messageId, (m) => {
			const textMessage = m.message as AggregateAISearchCardConversationMessage
			if (textMessage.aggregate_ai_search_card) {
				// Note: Do not update content here; typing effect updates during streaming
				if (textMessage.aggregate_ai_search_card.stream_options) {
					textMessage.aggregate_ai_search_card.stream_options.status =
						StreamStatus.End
				}
			}
			return { ...m }
		})

		// Update conversation last message summary
		ConversationService.updateLastReceiveMessage(conversationId, {
				time: Date.now() / 1000,
				seq_id: messageId,
				type: ConversationMessageType.AggregateAISearchCard,
				text: (keyPaths.llm_response as string).slice(0, 50),
				topic_id: topicId,
			})

			// Persist to DB
			MessageService.updateDbMessage(messageId, conversationId, {
				"message.aggregate_ai_search_card.llm_response": keyPaths.llm_response,
				"message.aggregate_ai_search_card.reasoning_content": keyPaths.reasoning_content,
				"message.aggregate_ai_search_card.stream_options.status": StreamStatus.End,
			} as Partial<SeqResponse<ConversationMessage>>)
		}
	console.log(`[applyAggregateAISearchCardStreamMessage] AI search card stream message handling completed`)
}

/**
 * Apply streaming updates for Aggregate AI Search Card V2 messages.
 * @param streamMessage Streaming payload.
 */
applyAggregateAISearchCardV2StreamMessage = (streamMessage: StreamResponseV2) => {
	const {
		streams: {
				stream_options: { status } = { status: StreamStatus.Streaming },
				...keyPaths
			},
			target_seq_id,
		} = streamMessage

	console.log(`Start handling AI search card V2 stream message, status: ${status}`)

	const { messageId, conversationId, topicId } = this.queryMessageInfo(target_seq_id)

	if ([StreamStatus.Streaming, StreamStatus.Start].includes(status)) {
		const updated = MessageService.updateMessage(
			conversationId,
			topicId,
			messageId,
			(m) => {
				const textMessage = m.message as AggregateAISearchCardConversationMessageV2

				for (const keyPath of Object.keys(keyPaths)) {
					const keyPathsArray = keyPath.split(".")

					appendObject(
						textMessage.aggregate_ai_search_card_v2,
						keyPathsArray,
						keyPaths[keyPath],
					)

					// Update status
					updateAggregateAISearchCardV2Status(
						textMessage.aggregate_ai_search_card_v2,
						keyPath,
						keyPaths[keyPath],
					)
				}

				return { ...m }
			},
		)

		console.log(` Update message:`, toJS(updated))
	} else if (status === StreamStatus.End) {
		console.log(`Handle end status message`)
	// Update message status
	MessageService.updateMessage(conversationId, topicId, messageId, (m) => {
				const textMessage = m.message as AggregateAISearchCardConversationMessageV2
				if (textMessage.aggregate_ai_search_card_v2) {
					// Note: Do not update content here; typing effect updates during streaming
					if (textMessage.aggregate_ai_search_card_v2.stream_options) {
						textMessage.aggregate_ai_search_card_v2.stream_options.status =
							StreamStatus.End
						textMessage.aggregate_ai_search_card_v2.status =
							AggregateAISearchCardV2Status.isEnd
					}
				}
				return { ...m }
	})

	// Update conversation last message summary
	ConversationService.updateLastReceiveMessage(conversationId, {
		time: Date.now() / 1000,
		seq_id: messageId,
		type: ConversationMessageType.AggregateAISearchCardV2,
		text:
			(
				keyPaths.summary as { content: string; reasoning_content: string }
			)?.content?.slice(0, 50) ?? "",
		topic_id: topicId,
	})

	// Persist to DB
	MessageService.updateDbMessage(messageId, conversationId, {
		"message.aggregate_ai_search_card_v2": { ...streamMessage.streams },
	} as Partial<SeqResponse<ConversationMessage>>)
}
}
}

export default new StreamMessageApplyServiceV2()
