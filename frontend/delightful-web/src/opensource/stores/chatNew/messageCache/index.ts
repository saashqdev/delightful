import type { FullMessage, MessagePage } from "@/types/chat/message"

interface ConversationMessages {
	cacheData: MessagePage
	lastUpdated: number
	priority: number
}

interface TargetType {
	id: string | null
	priority: number
	lastUpdated: number
}

class MessageCache {
	private cache: Map<string, ConversationMessages>

	private readonly maxSize: number

	private readonly maxMessages: number

	constructor(maxSize: number = 10, maxMessages: number = 50) {
		this.cache = new Map()
		this.maxSize = maxSize
		this.maxMessages = maxMessages
	}

	cacheKey(conversationId: string, topicId: string = "") {
		return `${conversationId}-${topicId}`
	}

	/**
	 * Set conversation message with priority support
	 */
	public set(
		conversationId: string,
		topicId: string,
		cacheData: MessagePage,
		priority: number = 0,
	): void {
		if (this.cache.size >= this.maxSize && !this.cache.has(conversationId)) {
			this.removeLowestPriorityLRU()
		}

		const limitedMessages = cacheData?.messages?.slice(0, this.maxMessages)

		cacheData.messages = limitedMessages

		this.cache.set(this.cacheKey(conversationId, topicId), {
			cacheData,
			lastUpdated: Date.now(),
			priority,
		})
	}

	/**
	 * Get conversation message
	 */
	public get(conversationId: string, topicId: string = ""): MessagePage {
		const conversation = this.cache.get(this.cacheKey(conversationId, topicId))
		if (conversation) {
			// Conversation switched to current, update last updated time
			conversation.lastUpdated = Date.now()
			return conversation.cacheData
		}

		return { messages: [] }
	}

	/**
	 * Paginate get conversation message
	 */
	public getPage(
		conversationId: string,
		topicId: string,
		page: number,
		pageSize: number,
	): MessagePage {
		const conversation = this.cache.get(this.cacheKey(conversationId, topicId ?? ""))
		if (conversation) {
			const { messages } = conversation.cacheData

			return {
				messages: messages.slice((page - 1) * pageSize, page * pageSize),
				page,
				pageSize,
				totalPages: conversation.cacheData.totalPages,
			}
		}
		return { messages: [] }
	}

	/**
	 * Add multiple messages
	 */
	public addMessages(conversationId: string, topicId: string, data: MessagePage) {
		const conversation = this.cache.get(this.cacheKey(conversationId, topicId))
		if (conversation) {
			conversation.cacheData.messages.push(...data.messages)
			conversation.cacheData.totalPages = data.totalPages
			conversation.cacheData.page = data.page
		}
	}

	/**
	 * Add or replace single message
	 */
	public addOrReplaceMessage(
		conversationId: string,
		topicId: string,
		message: FullMessage,
	): void {
		const cache = this.cache.get(this.cacheKey(conversationId, topicId))
		if (cache) {
			const cacheMessages = cache.cacheData.messages

			// If message exists, replace it
			const index = cacheMessages.findIndex((item) => item.message_id === message.message_id)
			if (index !== -1) {
				cacheMessages[index] = message
			} else {
				// If message doesn't exist, add it
				cache.cacheData.messages.unshift(message)
				cache.cacheData.messages = cache.cacheData.messages.slice(0, this.maxMessages)
			}

			// Update last updated time
			cache.lastUpdated = +Date.now()
		} else {
			this.set(conversationId, topicId, {
				page: 1,
				pageSize: 10,
				totalPages: 1,
				messages: [message],
			})
		}
	}

	/**
	 * Remove conversation with lowest priority and least recently used
	 */
	private removeLowestPriorityLRU(): void {
		let targetId: string | null = null
		let lowestPriority = Infinity
		let oldestTimestamp = Infinity

		const entries = [...this.cache.entries()]
		const target = entries.reduce<TargetType>(
			(lowest, [id, conversation]) => {
				const isLowerPriority = conversation.priority < lowest.priority
				const isSamePriorityButOlder =
					conversation.priority === lowest.priority &&
					conversation.lastUpdated < lowest.lastUpdated

				if (isLowerPriority || isSamePriorityButOlder) {
					return {
						id,
						priority: conversation.priority,
						lastUpdated: conversation.lastUpdated,
					}
				}
				return lowest
			},
			{
				id: null,
				priority: lowestPriority,
				lastUpdated: oldestTimestamp,
			},
		)

		targetId = target.id
		lowestPriority = target.priority
		oldestTimestamp = target.lastUpdated

		if (targetId) {
			this.cache.delete(targetId)
		}
	}

	/**
	 * Clear cache for specified conversation
	 */
	public clear(conversationId: string, topicId: string): void {
		this.cache.delete(this.cacheKey(conversationId, topicId))
	}

	/**
	 * Clear all cache
	 */
	public clearAll(): void {
		this.cache.clear()
	}

	/**
	 * Get cache size
	 */
	public size(): number {
		return this.cache.size
	}

	/**
	 * Check if conversation is in cache
	 */
	public has(conversationId: string, topicId: string): boolean {
		return this.cache.has(this.cacheKey(conversationId, topicId))
	}

	/**
	 * Get all cached conversation IDs
	 */
	public getConversationIds(): string[] {
		return Array.from(this.cache.keys())
	}

	/**
	 * Update conversation priority
	 */
	public updatePriority(conversationId: string, topicId: string, priority: number): void {
		const conversation = this.cache.get(this.cacheKey(conversationId, topicId))
		if (conversation) {
			conversation.priority = priority
			conversation.lastUpdated = Date.now()
		}
	}

	/**
	 * Find message by sender ID
	 * @param senderId Sender ID
	 * @returns First message found, or null if not found
	 */
	public findMessageBySenderId(senderId: string): FullMessage | null {
		const allMessages = Array.from(this.cache.values()).flatMap(
			(messages) => messages.cacheData.messages,
		)

		return allMessages.find((msg) => msg.sender_id === senderId) || null
	}
}

// Singleton
export default new MessageCache()
