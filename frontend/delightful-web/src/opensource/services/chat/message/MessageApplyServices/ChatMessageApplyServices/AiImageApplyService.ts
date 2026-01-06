import MessageService from "@/opensource/services/chat/message/MessageService"
import type { AIImagesMessage, HDImageMessage } from "@/types/chat/conversation_message"
import { AIImagesDataType, HDImageDataType } from "@/types/chat/conversation_message"
import type { SeqResponse } from "@/types/request"
import type { ApplyMessageOptions } from "./types"
import ConversationStore from "@/opensource/stores/chatNew/conversation"
import MessageStore from "@/opensource/stores/chatNew/message"
import chatTopicService from "@/opensource/services/chat/topic"

class AiImageApplyService {
	aiImageMessageIdMap: Record<string, string> = {}

	tempMessageMap: Record<string, SeqResponse<AIImagesMessage | HDImageMessage>> = {}

	/**
	 * Apply AI image messages.
	 * @param message AI image message object.
	 * @param options Apply options.
	 */
	apply(message: SeqResponse<AIImagesMessage>, options: ApplyMessageOptions) {
		const { isHistoryMessage } = options

		const {
			message: { app_message_id, ai_image_card },
			message_id,
		} = message

		const localMessageSeq = this.tempMessageMap[app_message_id] as
			| SeqResponse<AIImagesMessage>
			| undefined

		const localMessage = localMessageSeq?.message as AIImagesMessage | undefined

		// Handle by message type
		switch (ai_image_card?.type) {
			case AIImagesDataType.StartGenerate:
				if (isHistoryMessage) return
				// For start-generate messages with no local record, add the message
				if (!this.aiImageMessageIdMap[app_message_id] && !isHistoryMessage) {
					// Add the message to DB and memory
					MessageService.addReceivedMessage(message)
					// Update message ID mapping
					this.aiImageMessageIdMap[app_message_id] = message_id
					this.tempMessageMap[app_message_id] = message
				}
				break

			case AIImagesDataType.ReferImage:
				// Referenced image messages are added directly
				MessageService.addReceivedMessage(message)
				break

			case AIImagesDataType.Error:
				if (isHistoryMessage) return
				// Error message handling
				if (!localMessage?.ai_image_card) {
					// No local message; add directly
					MessageService.addReceivedMessage(message)
				} else if (localMessageSeq) {
					// Local message exists; update it
					this.updateOldAiImageMessage(message)
				}
				break

			case AIImagesDataType.GenerateComplete:
				// Generation complete handling
				if (!localMessage?.ai_image_card || !localMessageSeq) {
					// No local message, likely a history application; add directly
					if (isHistoryMessage) MessageService.addReceivedMessage(message)
				} else {
					this.updateOldAiImageMessage(message)
				}
				break

			default:
				break
		}

		if (
			!isHistoryMessage &&
			ConversationStore.currentConversation?.id === message.conversation_id &&
			ConversationStore.currentConversation?.current_topic_id === message.message.topic_id
		) {
			// If AI conversation and message list length is 2, trigger smart rename
			if (MessageStore.messages.length === 2 && !isHistoryMessage) {
				// Call smart renaming
				chatTopicService.getAndSetDelightfulTopicName(message.message.topic_id ?? "")
			}
		}
	}

	/**
	 * Apply HD image messages.
	 * @param message HD image message object.
	 * @param options Apply options.
	 */
	applyHDImageMessage(message: SeqResponse<HDImageMessage>, options: ApplyMessageOptions) {
		const { isHistoryMessage } = options

		const {
			message: { app_message_id, image_convert_high_card },
			message_id,
		} = message

		const localMessageSeq = this.tempMessageMap[app_message_id] as
			| SeqResponse<AIImagesMessage>
			| undefined

		const localMessage = localMessageSeq?.message as HDImageMessage | undefined

		// Handle by message type
		switch (image_convert_high_card?.type) {
			case HDImageDataType.StartGenerate:
				if (isHistoryMessage) return
				// For start-generate messages with no local record, add the message
				if (!this.aiImageMessageIdMap[app_message_id] && !isHistoryMessage) {
					// Add the message to DB and memory
					MessageService.addReceivedMessage(message)
					// Update message ID mapping
					this.aiImageMessageIdMap[app_message_id] = message_id
					this.tempMessageMap[app_message_id] = message
				}
				break
			case HDImageDataType.Error:
				if (isHistoryMessage) return
				// Error message handling
				if (!localMessage?.image_convert_high_card) {
					// No local message; add directly
					MessageService.addReceivedMessage(message)
				} else if (localMessageSeq) {
					// Local message exists; update it
					this.updateOldAiImageMessage(message)
				}
				break

			case HDImageDataType.GenerateComplete:
				// Generation complete handling
				if (!localMessage?.image_convert_high_card || !localMessageSeq) {
					// No local message, likely a history application; add directly
					if (isHistoryMessage) MessageService.addReceivedMessage(message)
				} else {
					this.updateOldAiImageMessage(message)
				}
				break

			default:
				break
		}
	}

	/**
	 * Update an existing AI image message with a newer one.
	 * @param message The new message.
	 */
	updateOldAiImageMessage(message: SeqResponse<AIImagesMessage | HDImageMessage>) {
		const {
			message: { app_message_id, topic_id },
			message_id,
			conversation_id,
		} = message

		const oldMessageId = this.aiImageMessageIdMap[app_message_id]

		this.aiImageMessageIdMap[app_message_id] = message_id
		this.tempMessageMap[app_message_id] = message

		MessageService.addReceivedMessage(message)
		if (oldMessageId && oldMessageId !== message_id) {
			MessageService.removeMessage(conversation_id, oldMessageId, topic_id ?? "")
		}
	}
}

export default new AiImageApplyService()
