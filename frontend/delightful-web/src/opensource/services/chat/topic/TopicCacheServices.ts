import Topic from "@/opensource/models/chat/topic"
import type { ConversationTopic } from "@/types/chat/topic"

class TopicCacheServices {
	topicCache: Map<string, Topic[]> = new Map()

	/**
	 * Get topic cache key
	 * @param conversationId Conversation ID
	 * @returns Topic cache key
	 */
	static getTopicCacheKey(conversationId: string) {
		return `topic_${conversationId}`
	}

	/**
	 * Get topic cache
	 * @param conversationId Conversation ID
	 * @returns Topic list
	 */
	getTopicCache(conversationId: string): Topic[] | undefined {
		return this.topicCache.get(conversationId)
	}

	/**
	 * Check whether topic cache exists
	 * @param conversationId Conversation ID
	 * @returns Whether exists
	 */
	hasTopicCache(conversationId: string): boolean {
		return this.topicCache.has(conversationId)
	}

	/**
	 * Set topic cache
	 * @param conversationId Conversation ID
	 * @param topicList Topic list
	 */
	setTopicCache(conversationId: string, topicList: Topic[]): void {
		this.topicCache.set(conversationId, topicList)
	}

	/**
	 * Clear topic cache for a specific conversation
	 * @param conversationId Conversation ID
	 */
	clearTopicCache(conversationId: string): void {
		this.topicCache.delete(conversationId)
	}

	/**
	 * Clear all topic caches
	 */
	clearAllTopicCache(): void {
		this.topicCache.clear()
	}

	/**
	 * Update a specific topic in cache
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 * @param updates Topic updates
	 */
	updateTopicInCache(
		conversationId: string,
		topicId: string,
		updates: Partial<ConversationTopic>,
	): void {
		const topicList = this.getTopicCache(conversationId)
		if (!topicList) return

		const updatedTopicList = topicList.map((topic) => {
			if (topic.id === topicId) {
				return new Topic({ ...topic, ...updates, updated_at: Date.now() })
			}
			return topic
		})

		this.setTopicCache(conversationId, updatedTopicList)
	}

	/**
	 * Delete topic from cache
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 */
	deleteTopicFromCache(conversationId: string, topicId: string): void {
		const topicList = this.getTopicCache(conversationId)
		if (!topicList) return

		const filteredTopicList = topicList.filter((topic) => topic.id !== topicId)
		this.setTopicCache(conversationId, filteredTopicList)
	}

	/**
	 * Add topic to cache
	 * @param conversationId Conversation ID
	 * @param topic Topic
	 */
	addTopicToCache(conversationId: string, topic: Topic): void {
		const topicList = this.getTopicCache(conversationId) || []
		this.setTopicCache(conversationId, [...topicList, topic])
	}

	/**
	 * Get topic by ID from cache
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 * @returns Topic
	 */
	getTopicById(conversationId: string, topicId: string): Topic | undefined {
		const topicList = this.getTopicCache(conversationId)
		if (!topicList) return undefined

		return topicList.find((topic) => topic.id === topicId)
	}
}

export default new TopicCacheServices()
