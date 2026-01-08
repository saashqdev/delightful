import { FullMessage, ApplyMessageOptions } from "@/types/chat/message"
import MessageStore from "@/opensource/stores/chatNew/message"
import MessageCacheStore from "@/opensource/stores/chatNew/messageCache"
import MessageService from "./MessageService"
import {
	ConversationMessageSend,
	ConversationMessageStatus,
	SendStatus,
	ConversationMessage,
} from "@/types/chat/conversation_message"
import conversationStore from "@/opensource/stores/chatNew/conversation"
import { SeqResponse } from "@/types/request"
import { userStore } from "@/opensource/models/user"
import MessageApplyServices from "./MessageApplyServices"
import { CMessage } from "@/types/chat"

class MessageDispatchService {
	addSendMessage(renderMessage: FullMessage, message: ConversationMessageSend) {
		// If current conversation matches, append to the live list
		const { currentConversation } = conversationStore

		if (
			currentConversation?.id === message.conversation_id &&
			currentConversation.current_topic_id === message.message.topic_id
		) {
			MessageStore.addSendMessage(renderMessage)
			console.log("Send message to current conversation", message.conversation_id, "Message ID", message.message_id)
		} else {
			console.log(
				"Send message to non-current conversation",
				message.conversation_id,
				"Current conversation",
				currentConversation?.id,
				"Message ID",
				message.message_id,
			)
		}

		// Add to pending-send queue
		MessageService.addPendingMessage(message)
	}

	/**
	 * Add a received message.
	 */
	addReceivedMessage(fullMessage: FullMessage) {
		// If current conversation/topic match, append to live queue
		if (
			MessageStore.conversationId === fullMessage.conversation_id &&
			MessageStore.topicId === fullMessage.message.topic_id
		) {
			// Check attachment expiration
			MessageService.checkMessageAttachmentExpired([fullMessage])

			MessageStore.addReceivedMessage(fullMessage)
			if (!fullMessage.is_self) MessageService.sendReadReceipt([fullMessage])
		} else {
			MessageCacheStore.addOrReplaceMessage(
				fullMessage.conversation_id,
				fullMessage.message.topic_id ?? "",
				fullMessage,
			)
		}
	}

	updateSendMessage(message: SeqResponse<ConversationMessage>, sendStatus: SendStatus) {
		// If current conversation/topic match, append to live queue
		if (
			MessageStore.conversationId === message.conversation_id &&
			MessageStore.topicId === message.message.topic_id
		) {
			const fullMessage = MessageService.formatMessage(message, userStore.user.userInfo)

			// Check attachment expiration
			MessageService.checkMessageAttachmentExpired([fullMessage])

			MessageStore.addReceivedMessage(fullMessage)
			if (!fullMessage.is_self) MessageService.sendReadReceipt([fullMessage])
		} else {
			const fullMessage = MessageService.formatMessage(message, userStore.user.userInfo)
			MessageCacheStore.addOrReplaceMessage(
				message.conversation_id,
				message.message.topic_id ?? "",
				fullMessage,
			)
		}

		// Update message status
		MessageStore.updateMessageSendStatus(message.message_id, sendStatus)
	}

	/**
	 * Update message status.
	 * @param messageId Message ID
	 * @param sendStatus Send status
	 * @param seenStatus Seen status
	 */
	updateMessageStatus(
		messageId: string,
		sendStatus: SendStatus | undefined,
		seenStatus: ConversationMessageStatus | undefined,
	) {
		if (sendStatus) {
			MessageStore.updateMessageSendStatus(messageId, sendStatus)
			MessageService.updatePendingMessageStatus(messageId, sendStatus, false)
		}

		if (seenStatus) {
			MessageStore.updateMessageSeenStatus(messageId, seenStatus)
		}
	}

	/**
	 * Update message ID from a temp ID.
	 * @param tempId Temporary ID
	 * @param messageId Message ID
	 */
	updateMessageId(tempId: string, messageId: string) {
		MessageStore.updateMessageId(tempId, messageId)
	}

	/**
	 * Apply a message to the system.
	 */
	applyMessage(message: SeqResponse<CMessage>, options: ApplyMessageOptions) {
		console.log("Apply message ==========", message, options)
		// Skip messages not for current organization or delightful account
		if (
			message.organization_code !== userStore.user.userInfo?.organization_code ||
			message.delightful_id !== userStore.user.userInfo?.delightful_id
		) {
			return
		}

		MessageApplyServices.doApplyMessage(message, options)
	}
}

export default new MessageDispatchService()
