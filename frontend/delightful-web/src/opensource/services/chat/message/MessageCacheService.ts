/* eslint-disable class-methods-use-this */
import type { FullMessage } from "@/types/chat/message"
import conversationStore from "@/opensource/stores/chatNew/conversation"
import topicStore from "@/opensource/stores/chatNew/topic"
import MessageCacheStore from "@/opensource/stores/chatNew/messageCache"
import type { User } from "@/types/user"
import MessageService from "./MessageService"
import { userStore } from "@/opensource/models/user"

interface PrewarmItem {
	conversationId: string
	topicId: string
	priority: number
}

class MessageCacheService {
	private readonly ADJACENT_COUNT = 5 // 5 conversations before and after

	/**
	 * Prewarm a conversation's messages.
	 * @param conversationId Conversation ID.
	 * @param priority Priority for prewarming.
	 * @returns The prewarmed message list.
	 */
	private async prewarmMessage(
		conversationId: string,
		topicId: string,
		priority: number,
		userInfo?: User.UserInfo | null,
	) {
		try {
			const messages = await MessageService.getMessagesByPage(
				conversationId,
				topicId,
				1,
				10,
				userInfo,
			)
			MessageCacheStore.set(conversationId, topicId, messages, priority)
			return messages
		} catch (error) {
			console.error("Failed to prewarm conversation:", error)
			return []
		}
	}

	/**
	 * Collect conversations to prewarm.
	 * @returns List of conversations to prewarm with priority.
	 */
	private collectPrewarmConversationItems(): PrewarmItem[] {
		const { conversations } = conversationStore
		if (!Object.keys(conversations).length) {
			return []
		}

		const items: PrewarmItem[] = []
		const conversationList = Object.values(conversations)
		const anchorConversation = conversationStore.currentConversation || conversationList[0]
		if (!anchorConversation) {
			return []
		}

		const anchorIndex = conversationList.findIndex((c) => c.id === anchorConversation.id)

		// No need to prewarm the current conversation
		// if (anchorConversation.last_receive_message) {
		// 	items.push({
		// 		conversationId: anchorConversation.id,
		// 		topicId: anchorConversation.current_topic_id,
		// 		priority: 1,
		// 	})
		// }

		// Get 5 conversations before and after the anchor
		for (let i = 1; i <= this.ADJACENT_COUNT; i += 1) {
			// Previous conversations
			const prevIndex = anchorIndex - i
			if (prevIndex >= 0) {
				const prevConversation = conversationList[prevIndex]
				if (prevConversation?.last_receive_message) {
					// Priority decays with distance
					const priority = 0.5 * (1 - i / (this.ADJACENT_COUNT + 1))
					items.push({
						conversationId: prevConversation.id,
						topicId: prevConversation.current_topic_id,
						priority,
					})
				}
			}

			// Next conversations
			const nextIndex = anchorIndex + i
			if (nextIndex < conversationList.length) {
				const nextConversation = conversationList[nextIndex]
				if (nextConversation?.last_receive_message) {
					// Priority decays with distance
					const priority = 0.5 * (1 - i / (this.ADJACENT_COUNT + 1))
					items.push({
						conversationId: nextConversation.id,
						topicId: nextConversation.current_topic_id,
						priority,
					})
				}
			}
		}

		// Put other conversations at the end
		conversationList.forEach((conversation) => {
			const isAdjacent =
				Math.abs(
					conversationList.findIndex((c) => c.id === conversation.id) - anchorIndex,
				) <= this.ADJACENT_COUNT

			if (!isAdjacent && conversation.last_receive_message) {
				items.push({
					conversationId: conversation.id,
					topicId: conversation.current_topic_id,
					priority: 0,
				})
			}
		})

		return items
	}

	/**
	 * Initialize conversation messages prewarming.
	 */
	initConversationsMessage(userInfo?: User.UserInfo | null) {
		const items = this.collectPrewarmConversationItems()
		if (!items.length) {
			return
		}

		// If user info not provided, pull from store to avoid org-switch issues
		if (!userInfo || !userInfo?.user_id) {
			userInfo = userStore.user.userInfo
		}

		// Sort by priority (desc)
		items.sort((a, b) => b.priority - a.priority)

		let currentIndex = 0
		const processNextItem = () => {
			if (currentIndex >= items.length) return

			const item = items[currentIndex]
			this.prewarmMessage(item.conversationId, item.topicId, item.priority, userInfo)
			currentIndex += 1

			requestIdleCallback(() => processNextItem())
		}

		requestIdleCallback(() => processNextItem())
	}

	/**
	 * Collect topics to prewarm for the current conversation.
	 * @returns List of topics to prewarm with priority.
	 */
	private collectPrewarmTopicsItems(): PrewarmItem[] {
		const { topicList } = topicStore
		if (!topicList.length) {
			return []
		}

		if (!conversationStore.currentConversation) {
			return []
		}

		const currentTopic = conversationStore.currentConversation?.current_topic_id
		if (!currentTopic) {
			return []
		}

		const items: PrewarmItem[] = []
		// No need to prewarm the current topic
		// items.push({
		// 	conversationId: conversationStore.currentConversation?.id,
		// 	topicId: currentTopic,
		// 	priority: 1,
		// })

		const anchorIndex = topicList.findIndex((t) => t.id === currentTopic)

		// Get 5 topics before and after the anchor
		for (let i = 1; i <= this.ADJACENT_COUNT; i += 1) {
			// Previous topics
			const prevIndex = anchorIndex - i
			if (prevIndex >= 0) {
				const prevTopic = topicList[prevIndex]
				if (prevTopic) {
					// Priority decays with distance
					const priority = 0.5 * (1 - i / (this.ADJACENT_COUNT + 1))
					items.push({
						conversationId: conversationStore.currentConversation?.id,
						topicId: prevTopic.id,
						priority,
					})
				}
			}

			// Next topics
			const nextIndex = anchorIndex + i
			if (nextIndex < topicList.length) {
				const nextTopic = topicList[nextIndex]
				if (nextTopic) {
					// Priority decays with distance
					const priority = 0.5 * (1 - i / (this.ADJACENT_COUNT + 1))
					items.push({
						conversationId: conversationStore.currentConversation?.id,
						topicId: nextTopic.id,
						priority,
					})
				}
			}
		}

		return items
	}

	/**
	 * Initialize topic messages prewarming for current conversation.
	 */
	initTopicsMessage(userInfo?: User.UserInfo | null) {
		const items = this.collectPrewarmTopicsItems()
		if (!items.length) {
			return
		}

		// Ensure current user info to avoid mismatched org during switching
		if (!userInfo || !userInfo?.user_id) {
			userInfo = userStore.user.userInfo
		}

		items.sort((a, b) => b.priority - a.priority)

		let currentIndex = 0
		const processNextItem = () => {
			if (currentIndex >= items.length) return

			const item = items[currentIndex]
			this.prewarmMessage(item.conversationId, item.topicId, item.priority, userInfo)
			currentIndex += 1

			requestIdleCallback(() => processNextItem())
		}

		requestIdleCallback(() => processNextItem())
	}

	addConversation(conversationId: string, topicId: string) {
		requestIdleCallback(() => {
			this.prewarmMessage(conversationId, topicId, 1)
		})
	}

	getMessages(conversationId: string, topicId: string): FullMessage[] | undefined {
		return MessageCacheStore.get(conversationId, topicId)?.messages
	}

	clear(conversationId: string, topicId: string = "") {
		MessageCacheStore.clear(conversationId, topicId)
	}

	/**
	 * Check if cache exists for a conversation/topic.
	 * @param conversationId Conversation ID.
	 * @param topicId Topic ID.
	 * @returns Whether cache exists.
	 */
	hasCache(conversationId: string, topicId: string) {
		return MessageCacheStore.has(conversationId, topicId)
	}

	/**
	 * Mark a message as revoked in the cache.
	 * @param conversationId Conversation ID.
	 * @param topicId Topic ID.
	 * @param message_id Message ID.
	 */
	updateMessageRevoked(conversationId: string, topicId: string, message_id: string) {
		const cache = MessageCacheStore.get(conversationId, topicId)
		if (cache) {
			cache.messages = cache.messages.map((message) => {
				if (message.message_id === message_id) {
					message.revoked = true
				}
				return message
			})
		}
	}

	/**
	 * Remove a message from the cache.
	 * @param conversationId Conversation ID.
	 * @param messageId Message ID.
	 * @param topicId Topic ID.
	 */
	removeMessageInCache(conversationId: string, messageId: string, topicId: string) {
		const cache = MessageCacheStore.get(conversationId, topicId)
		if (cache) {
			cache.messages = cache.messages.filter((message) => message.message_id !== messageId)
		}
	}

	/**
	 * Remove all messages for a topic from cache.
	 * @param conversationId Conversation ID.
	 * @param topicId Topic ID.
	 */
	removeTopicMessages(conversationId: string, topicId: string) {
		MessageCacheStore.clear(conversationId, topicId)
	}

	/**
	 * Update a message in the cache.
	 * @param conversationId Conversation ID.
	 * @param topicId Topic ID.
	 * @param messageId Message ID.
	 * @param replace Replacement object or function.
	 */
	updateMessage(
		conversationId: string,
		topicId: string,
		messageId: string,
		replace: FullMessage | ((message: FullMessage) => FullMessage),
	): FullMessage | undefined {
		const cache = MessageCacheStore.get(conversationId, topicId)
		if (cache) {
			const messageIndex = cache.messages.findIndex((m) => m.message_id === messageId)
			if (messageIndex !== -1) {
				cache.messages[messageIndex] =
					typeof replace === "function" ? replace(cache.messages[messageIndex]) : replace
				return cache.messages[messageIndex]
			}
		}
		return undefined
	}
}

export default new MessageCacheService()
