/* eslint-disable class-methods-use-this */
import type {
	AIImagesMessage,
	AggregateAISearchCardConversationMessage,
	AggregateAISearchCardConversationMessageV2,
	ConversationMessage,
} from "@/types/chat/conversation_message"
import {
	AggregateAISearchCardV2Status,
	AIImagesDataType,
	ConversationMessageStatus,
	ConversationMessageType,
	HDImageDataType,
} from "@/types/chat/conversation_message"
import pubsub from "@/utils/pubsub"
import type { SeqResponse } from "@/types/request"

// Import message service
import MessageService from "@/opensource/services/chat/message/MessageService"

// Message store and state management
import type { CMessage } from "@/types/chat"
import ConversationService from "@/opensource/services/chat/conversation/ConversationService"
import { getSlicedText } from "@/opensource/services/chat/conversation/utils"
import ConversationStore from "@/opensource/stores/chatNew/conversation"
import MessageStore from "@/opensource/stores/chatNew/message"
import chatTopicService from "@/opensource/services/chat/topic"
import DotsService from "@/opensource/services/chat/dots/DotsService"
import AiImageApplyService from "./AiImageApplyService"
import AiSearchApplyService from "./AiSearchApplyService"
import { bigNumCompare } from "@/utils/string"
import OrganizationDotsStore from "@/opensource/stores/chatNew/dots/OrganizationDotsStore"
import { userStore } from "@/opensource/models/user"
import StreamMessageApplyServiceV2 from "../StreamMessageApplyServiceV2"
import ConversationDbService from "../../../conversation/ConversationDbService"
import { ApplyMessageOptions } from "@/types/chat/message"

// Message event listener type
type MessageEventListener = (message: SeqResponse<CMessage>, options?: ApplyMessageOptions) => void

/**
 * Chat message apply service
 * Handles various chat message types and applies business logic
 */
class ChatMessageApplyService {
	// Event listener collections
	private eventListeners: Record<string, MessageEventListener[]> = {
		onApply: [], // Fired when any message is applied
	}

	/**
	 * Subscribe to message-apply events
	 * @param eventName Event name
	 * @param listener Listener function
	 * @returns Unsubscribe function
	 */
	subscribe(eventName: string, listener: MessageEventListener): () => void {
		if (!this.eventListeners[eventName]) {
			this.eventListeners[eventName] = []
		}
		this.eventListeners[eventName].push(listener)

		// Return unsubscribe function
		return () => {
			this.unsubscribe(eventName, listener)
		}
	}

	/**
	 * Unsubscribe from message-apply events
	 * @param eventName Event name
	 * @param listener Listener to remove
	 */
	unsubscribe(eventName: string, listener: MessageEventListener): void {
		if (!this.eventListeners[eventName]) return

		this.eventListeners[eventName] = this.eventListeners[eventName].filter(
			(l) => l !== listener,
		)
	}

	/**
	 * Publish an event
	 * @param eventName Event name
	 * @param message Message
	 * @param options Apply options
	 */
	private publish(
		eventName: string,
		message: SeqResponse<CMessage>,
		options: ApplyMessageOptions = {},
	): void {
		if (!this.eventListeners[eventName]) return

		for (const listener of this.eventListeners[eventName]) {
			listener(message, options)
		}
	}

	/**
	 * Determine whether it is a chat message
	 * @param message Message
	 * @returns Whether it is chat type
	 */
	isChatMessage(message: SeqResponse<CMessage>) {
		return [
			ConversationMessageType.Text,
			ConversationMessageType.RichText,
			ConversationMessageType.Markdown,
			ConversationMessageType.DelightfulSearchCard,
			ConversationMessageType.Files,
			ConversationMessageType.Image,
			ConversationMessageType.Video,
			ConversationMessageType.Voice,
			ConversationMessageType.AggregateAISearchCard,
			ConversationMessageType.AggregateAISearchCardV2,
			ConversationMessageType.HDImage,
			ConversationMessageType.AiImage,
			ConversationMessageType.RecordingSummary,
		].includes(message.message.type as ConversationMessageType)
	}

	/**
	 * Determine whether a message should be considered history-displayable
	 * @param message Message
	 * @returns Whether it is considered chat history
	 */
	isChatHistoryMessage(message: SeqResponse<CMessage>) {
		if (message.message.type === ConversationMessageType.AiImage) {
			return message.message.ai_image_card?.type === AIImagesDataType.GenerateComplete
		}

		if (message.message.type === ConversationMessageType.HDImage) {
			return (
				message.message.image_convert_high_card?.type === HDImageDataType.GenerateComplete
			)
		}

		return [
			ConversationMessageType.Text,
			ConversationMessageType.RichText,
			ConversationMessageType.Markdown,
			ConversationMessageType.DelightfulSearchCard,
			ConversationMessageType.Files,
			ConversationMessageType.Image,
			ConversationMessageType.Video,
			ConversationMessageType.Voice,
			ConversationMessageType.AggregateAISearchCard,
			ConversationMessageType.AggregateAISearchCardV2,
			ConversationMessageType.HDImage,
			ConversationMessageType.AiImage,
			ConversationMessageType.RecordingSummary,
		].includes(message.message.type as ConversationMessageType)
	}

	/**
	 * Apply chat-type messages
	 * @param message Message to apply
	 * @param options Apply options
	 */
	apply(message: SeqResponse<CMessage>, options: ApplyMessageOptions = {}) {
		// const { isHistoryMessage = false } = options
		console.log("ChatMessageApplyService apply message =====> ", message, options)

		// Publish global message-apply event
		this.publish("onApply", message, options)

		switch (message.message.type) {
			case ConversationMessageType.Text:
				pubsub.publish("be_delightful_new_message", message)
				StreamMessageApplyServiceV2.recordMessageInfo(
					message as SeqResponse<ConversationMessage>,
				)
				this.applyConversationMessage(message as SeqResponse<ConversationMessage>, options)
				break
			case ConversationMessageType.Markdown:
				StreamMessageApplyServiceV2.recordMessageInfo(
					message as SeqResponse<ConversationMessage>,
				)
				this.applyConversationMessage(message as SeqResponse<ConversationMessage>, options)
				break
			case ConversationMessageType.AggregateAISearchCardV2:
				console.log(`[apply] 处理AI搜索卡片V2消息, message:`, message)
				StreamMessageApplyServiceV2.recordMessageInfo(
					message as SeqResponse<ConversationMessage>,
				)
				console.log(
					`[apply] 处理AI搜索卡片V2消息, message:`,
					StreamMessageApplyServiceV2.queryMessageInfo(message.message_id),
				)

				const msg = message as SeqResponse<AggregateAISearchCardConversationMessageV2>
				if (msg.message.aggregate_ai_search_card_v2?.status === undefined) {
					// Initialize status
					msg.message.aggregate_ai_search_card_v2!.status =
						AggregateAISearchCardV2Status.isSearching
				}

				this.applyConversationMessage(msg, options)

				console.log(
					`[apply] 处理AI搜索卡片V2消息, applied message:`,
					MessageStore.messages.find((m) => m.message_id === message.message_id),
				)
				break
			case ConversationMessageType.RichText:
			case ConversationMessageType.DelightfulSearchCard:
			case ConversationMessageType.Files:
			case ConversationMessageType.Image:
			case ConversationMessageType.Video:
			case ConversationMessageType.Voice:
				this.applyConversationMessage(message as SeqResponse<ConversationMessage>, options)
				break
			case ConversationMessageType.AggregateAISearchCard:
				this.applyAggregateAISearchCardMessage(
					message as SeqResponse<AggregateAISearchCardConversationMessage>,
				)
				break
			case ConversationMessageType.HDImage:
			case ConversationMessageType.AiImage:
				this.applyAiImageMessage(message as SeqResponse<AIImagesMessage>, options)
				break
			case ConversationMessageType.RecordingSummary:
				// TODO: Implement recording summary handler
				break
			default:
				break
		}
	}

	/**
	 * Apply a conversation message
	 * @param message Conversation message
	 */
	async applyConversationMessage(
		message: SeqResponse<ConversationMessage>,
		options: ApplyMessageOptions = {},
	) {
		const { isHistoryMessage = false } = options
		let conversation = ConversationStore.getConversation(message.conversation_id)

		if (!conversation) {
			await ConversationService.addNewConversationFromDB(message.conversation_id)
		}

		conversation = ConversationStore.getConversation(message.conversation_id)

		MessageService.addReceivedMessage(message)

		// For AI conversations, set current topic if absent
		if (!conversation.current_topic_id && message.message.topic_id) {
			conversation.setCurrentTopicId(message.message.topic_id ?? "")
			ConversationDbService.updateConversation(message.conversation_id, {
				current_topic_id: message.message.topic_id ?? "",
			})

			// If the current conversation is active, switch topic
			if (ConversationStore.currentConversation?.id === message.conversation_id) {
				ConversationService.switchTopic(message.conversation_id, message.message.topic_id)
			}
		}

		if (
			ConversationStore.currentConversation?.id === message.conversation_id &&
			ConversationStore.currentConversation?.current_topic_id === message.message.topic_id &&
			conversation?.isAiConversation
		) {
			// If AI conversation, when list length is 2, trigger smart rename
			if (MessageStore.messages.length === 2 && !isHistoryMessage) {
				// Call smart rename
				chatTopicService.getAndSetDelightfulTopicName(message.message.topic_id ?? "")
			}
		}

		// Update conversation last message
		ConversationService.updateLastReceiveMessage(message.conversation_id, {
			time: message.message.send_time,
			seq_id: message.seq_id,
			...getSlicedText(message.message, message.message.revoked),
			topic_id: message.message.topic_id ?? "",
		})

		if (!conversation.is_not_disturb) {
			// Move conversation to the top
			ConversationService.moveConversationFirst(message.conversation_id)

			// If not own message and seq_id > organization unread seq_id, add conversation unread dot
			if (
				!isHistoryMessage &&
				message.message.sender_id !== userStore.user.userInfo?.user_id &&
				message.message.status === ConversationMessageStatus.Unread &&
				bigNumCompare(
					message.seq_id,
					OrganizationDotsStore.getOrganizationDotSeqId(
						conversation.user_organization_code,
					),
				) > 0
			) {
				DotsService.addConversationUnreadDots(
					conversation.user_organization_code,
					message.conversation_id,
					message.message.topic_id ?? "",
					message.seq_id,
					1,
				)
			}
		}
	}

	/**
	 * Apply Aggregate AI Search Card message
	 * @param message Aggregate AI search message
	 */
	async applyAggregateAISearchCardMessage(
		message: SeqResponse<AggregateAISearchCardConversationMessage>,
	) {
		AiSearchApplyService.apply(message)
	}

	/**
	 * Apply AI image message
	 * @param message AI image message
	 * @param options Apply options
	 */
	applyAiImageMessage(message: SeqResponse<AIImagesMessage>, options: ApplyMessageOptions = {}) {
		AiImageApplyService.apply(message, options)
	}
}

export default new ChatMessageApplyService()
