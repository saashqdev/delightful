import chatDb from "@/opensource/database/chat"
import Conversation from "@/opensource/models/chat/conversation"
import { ConversationObject } from "@/opensource/models/chat/conversation/types"
import { ConversationStatus } from "@/types/chat/conversation"
import { cloneDeep } from "lodash-es"

class ConversationDbServices {
	/**
	 * Load conversations from database
	 * @param organizationCode Organization code
	 * @param userId User ID
	 * @returns Conversation list
	 */
	loadNormalConversationsFromDB(organizationCode: string, userId: string) {
		return chatDb
			.getConversationTable()
			?.where({
				user_organization_code: organizationCode,
				receive_organization_code: organizationCode,
				status: ConversationStatus.Normal,
				user_id: userId,
			})
			.toArray()
	}

	/**
	 * Get conversation
	 * @param conversationId Conversation ID
	 * @returns Conversation
	 */
	getConversation(conversationId: string): Promise<ConversationObject | undefined> {
		return chatDb
			.getConversationTable()
			?.where({ id: conversationId })
			.first()
			.then((c) => (c ? new Conversation(c) : undefined))
	}

	/**
	 * Save conversation to database
	 * @param conversation Conversation
	 */
	addConversationsToDB(conversations: Conversation[]) {
		return chatDb
			.getConversationTable()
			.bulkPut(conversations.map((conversation) => cloneDeep(conversation.toObject())))
			.then((res) => {
				console.log("addConversation success", res)
			})
			.catch((err) => {
				console.error("addConversation error", err)
			})
	}

	/**
	 * Update conversation
	 * @param filteredConversationList Conversation list
	 */
	updateConversations(filteredConversationList: ConversationObject[]) {
		return chatDb.getConversationTable()?.bulkPut(filteredConversationList)
	}

	/**
	 * Update conversation
	 * @param id Conversation ID
	 * @param data Data to update
	 */
	updateConversation(id: string, data: Partial<Conversation>) {
		return chatDb.getConversationTable()?.update(id, data)
	}

	/**
	 * Delete conversation
	 * @param id Conversation ID
	 */
	deleteConversation(id: string) {
		return chatDb.getConversationTable()?.delete(id)
	}

	deleteConversations(ids: string[]) {
		return chatDb.getConversationTable()?.bulkDelete(ids)
	}

	/**
	 * Update conversation current topic ID
	 * @param id Conversation ID
	 * @param topicId Topic ID
	 */
	updateCurrentTopicId(id: string, topicId: string) {
		return chatDb.getConversationTable()?.update(id, { current_topic_id: topicId })
	}

	/**
	 * Update conversation pin status
	 * @param id Conversation ID
	 * @param isTop Whether to pin
	 */
	updateTopStatus(id: string, isTop: 0 | 1) {
		return chatDb.getConversationTable()?.update(id, { is_top: isTop })
	}

	/**
	 * Update conversation do not disturb status
	 * @param id Conversation ID
	 * @param isNotDisturb Whether to enable do not disturb
	 */
	updateNotDisturbStatus(id: string, isNotDisturb: 0 | 1) {
		return chatDb.getConversationTable()?.update(id, { is_not_disturb: isNotDisturb })
	}

	/**
	 * Update conversation topic default open status
	 * @param id Conversation ID
	 * @param open Whether to open
	 */
	updateTopicDefaultOpen(id: string, open: boolean) {
		return chatDb.getConversationTable()?.update(id, { topic_default_open: open })
	}

	/**
	 * Update conversation status (hide/show)
	 * @param id Conversation ID
	 * @param status Status
	 */
	updateStatus(id: string, status: number) {
		return chatDb.getConversationTable()?.update(id, { status })
	}

	/**
	 * Update conversation unread count
	 * @param id Conversation ID
	 * @param unreadDots Unread count
	 * @param topicUnreadDots Topic unread count
	 */
	updateUnreadDots(id: string, unreadDots: number, topicUnreadDots: Record<string, number>) {
		return chatDb.getConversationTable()?.update(id, {
			unread_dots: unreadDots,
			topic_unread_dots: new Map(Object.entries(topicUnreadDots)),
		})
	}
}

export default new ConversationDbServices()
