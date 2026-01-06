import chatDb from "@/opensource/database/chat"
import type ChatDatabase from "@/opensource/database/chat/class-new"
import Topic from "@/opensource/models/chat/topic"
import type { ConversationTopic } from "@/types/chat/topic"
import { uniqBy } from "lodash-es"

class TopicDBServices {
	db: ChatDatabase

	constructor(db: ChatDatabase) {
		this.db = db
	}

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
	static getTopicCache(conversationId: string) {
		return chatDb.getTopicListTable()?.where("conversation_id").equals(conversationId).first()
	}

	/**
	 * Set topic cache
	 * @param conversationId Conversation ID
	 * @param topicList Topic list
	 */
	static setTopicCache(conversationId: string, topicList: ConversationTopic[]) {
		chatDb.getTopicListTable()?.put({ conversation_id: conversationId, topic_list: topicList })
	}

	/**
	 * Load topic list for a conversation
	 * @param conversationId Conversation ID
	 * @returns Topic list
	 */
	async loadTopicsFromDB(conversationId: string): Promise<Topic[]> {
		const result = await this.db
			.getTopicListTable()
			?.where("conversation_id")
			.equals(conversationId)
			.first()

		// Convert ConversationTopic array to Topic array
		const topicList = result?.topic_list || []
		return topicList.map((topic) => new Topic(topic))
	}

	/**
	 * Save topic list to database
	 * @param conversationId Conversation ID
	 * @param topics Topic list
	 */
	saveTopicsToDB(conversationId: string, topics: Topic[]) {
		return this.db.getTopicListTable()?.put({
			conversation_id: conversationId,
			topic_list: topics,
		})
	}

	/**
	 * Add a single topic to the database
	 * @param topic Topic
	 */
	async addTopicToDB(topic: Topic) {
		const topics = await this.loadTopicsFromDB(topic.conversation_id)
		const newTopics = uniqBy([...topics, topic], "id")
		return this.saveTopicsToDB(topic.conversation_id, newTopics)
	}

	/**
	 * Update topic
	 * @param topicId Topic ID
	 * @param conversationId Conversation ID
	 * @param data Data to update
	 */
	async updateTopic(topicId: string, conversationId: string, data: Partial<ConversationTopic>) {
		const topics = await this.loadTopicsFromDB(conversationId)
		const updatedTopics = topics.map((topic) => {
			if (topic.id === topicId) {
				return new Topic({ ...topic, ...data, updated_at: Date.now() })
			}
			return topic
		})
		return this.saveTopicsToDB(conversationId, updatedTopics)
	}

	/**
	 * Delete topic
	 * @param topicId Topic ID
	 * @param conversationId Conversation ID
	 */
	async deleteTopic(topicId: string, conversationId: string) {
		const topics = await this.loadTopicsFromDB(conversationId)
		const filteredTopics = topics.filter((topic) => topic.id !== topicId)
		return this.saveTopicsToDB(conversationId, filteredTopics)
	}

	/**
	 * Get topic by ID
	 * @param topicId Topic ID
	 * @param conversationId Conversation ID
	 * @returns Topic
	 */
	async getTopicById(topicId: string, conversationId: string): Promise<Topic | undefined> {
		const topics = await this.loadTopicsFromDB(conversationId)
		return topics.find((topic) => topic.id === topicId)
	}

	/**
	 * 批量添加话题
	 * @param topics 话题列表
	 */
	async bulkAddTopics(topics: Topic[]) {
		if (topics.length === 0) return

		// Group by conversation ID
		const topicsByConversation = topics.reduce((acc, topic) => {
			if (!acc[topic.conversation_id]) {
				acc[topic.conversation_id] = []
			}
			acc[topic.conversation_id].push(topic)
			return acc
		}, {} as Record<string, Topic[]>)

		// Save topics for each conversation
		await Promise.all(
			Object.entries(topicsByConversation).map(async ([conversationId, topicsToAdd]) => {
				const existingTopics = await this.loadTopicsFromDB(conversationId)
				const newTopics = [...existingTopics, ...topicsToAdd]
				return this.saveTopicsToDB(conversationId, newTopics)
			}),
		)
	}
}

export default new TopicDBServices(chatDb)
