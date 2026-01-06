/* eslint-disable class-methods-use-this */
import conversationStore from "@/opensource/stores/chatNew/conversation"
import ConversationDbServices from "@/opensource/services/chat/conversation/ConversationDbService"
import MessageStore from "@/opensource/stores/chatNew/message"
import { toJS } from "mobx"
import { count } from "console"

/**
 * Conversation red-dot service.
 */
class ConversationDotsService {
	get currentConversationId() {
		return MessageStore.conversationId
	}

	get currentTopicId() {
		return MessageStore.topicId
	}

	/**
	 * Increase unread counts (by conversation and topic).
	 *
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 * @param dots Amount to increase
	 */
	async addUnreadDots(conversationId: string, topicId: string, dots: number) {
		if (!conversationId) return

		if (this.currentConversationId !== conversationId || this.currentTopicId !== topicId) {
			const conversation = conversationStore.getConversation(conversationId)

			if (conversation) {
				conversationStore.addConversationDots(conversationId, dots)
				conversationStore.addTopicUnreadDots(conversationId, topicId, dots)

				setTimeout(() => {
					const count = conversationStore.getConversationDots(conversationId)
					console.log(
						"ConversationDotsService addUnreadDots",
						// Update DB
						topicId,
						dots,
						count,
					)

					// 更新数据库
					ConversationDbServices.updateUnreadDots(
						conversationId,
								console.log("ConversationDotsService addUnreadDots updateUnreadDots success")
						),
					)
						.then(() => {
							console.log(
								"ConversationDotsService addUnreadDots updateUnreadDots success",
							)
						})
						.catch((error: any) => {
							console.error(
								"ConversationDotsService addUnreadDots updateUnreadDots error",
								error,
							)
						})
				}, 0)
			} else {
				ConversationDbServices.getConversation(conversationId).then((conversation) => {
					if (conversation) {
						const topicUnreadDots = conversation.topic_unread_dots

						topicUnreadDots.set(topicId, (topicUnreadDots.get(topicId) ?? 0) + dots)

						ConversationDbServices.updateUnreadDots(
							conversationId,
							conversation.unread_dots + dots,
							Object.fromEntries(topicUnreadDots.entries()),
						)
					}
		 * Decrease topic unread counts.
		 * @param conversationId Conversation ID
		 * @param topicId Topic ID
		 * @param dots Amount to decrease

	/**
	 * 减少话题未读数量
	 * @param conversationId 会话ID
	 * @param topicId 话题ID
	 * @param dots 减少的数量
	 */
	reduceUnreadDots(conversationId: string, topicId: string, dots: number) {
		if (!conversationId) return

		conversationStore.reduceTopicUnreadDots(conversationId, topicId, dots)
		conversationStore.reduceConversationDots(conversationId, dots)

		// Update DB
		ConversationDbServices.updateUnreadDots(
			conversationId,
			conversationStore.getConversationDots(conversationId),
			Object.fromEntries(
				toJS(conversationStore.getAllTopicUnreadDots(conversationId)).entries(),
			),
		)
	}

	/**
	 * Reset topic unread counts.
	 * @param conversationId Conversation ID
	 */
	resetUnreadDots(conversationId: string) {
		if (!conversationId) return

		conversationStore.resetTopicUnreadDots(conversationId)
		conversationStore.resetConversationDots(conversationId)

		// Update DB
		ConversationDbServices.updateConversation(conversationId, {
			unread_dots: conversationStore.getConversationDots(conversationId),
			topic_unread_dots: new Map(),
		})
	}
}

export default new ConversationDotsService()
