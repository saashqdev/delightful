/* eslint-disable class-methods-use-this */
import { isAiConversation } from "@/opensource/stores/chatNew/helpers/conversation"
import type { CreateTopicMessage, DeleteTopicMessage, UpdateTopicMessage } from "@/types/chat/topic"
import type { SeqResponse } from "@/types/request"
import { nanoid } from "nanoid"
import Logger from "@/utils/log/Logger"
import type Conversation from "@/opensource/models/chat/conversation"
import Topic from "@/opensource/models/chat/topic"
import topicStore from "@/opensource/stores/chatNew/topic"
import conversationStore from "@/opensource/stores/chatNew/conversation"
import { ChatApi } from "@/apis"
import conversationService from "../conversation/ConversationService"
import TopicDBServices from "./TopicDBServices"
import TopicCacheServices from "./TopicCacheServices"
import MessageService from "../message/MessageService"

// Create dedicated logger
const logger = new Logger("ChatTopic", "blue")

class ChatTopicService {
	/**
	 * Delightful ID (account ID)
	 */
	delightfulId: string | undefined

	/**
	 * Organization code
	 */
	organizationCode: string | undefined

	/**
	 * Last initialized conversation ID for topic list
	 */
	lastConversationId: string | undefined

	/**
	 * Initialize
	 * @param delightfulId Account ID
	 * @param organizationCode Organization code
	 */
	init(delightfulId: string, organizationCode: string) {
		this.delightfulId = delightfulId
		this.organizationCode = organizationCode
	}

	/**
	 * Initialize topic list
	 * @param conversation Conversation
	 */
	async initTopicList(conversation: Conversation) {
		// If conversation ID is the same, skip initialization
		if (this.lastConversationId === conversation.id) {
			return
		}

		// Cache current conversation list
		if (this.lastConversationId) {
			TopicCacheServices.setTopicCache(this.lastConversationId, topicStore.topicList)
		}

		this.lastConversationId = conversation.id

		// If topic list exists in cache, use cache first
		if (TopicCacheServices.hasTopicCache(conversation.id)) {
			const cachedTopics = TopicCacheServices.getTopicCache(conversation.id) || []
			topicStore.setTopicList(cachedTopics)

			if (conversation.isAiConversation && !conversation.current_topic_id) {
				conversationService.switchTopic(conversation.id, cachedTopics[0]?.id)
			}
		} else {
			// Load topic list from database
			await TopicDBServices.loadTopicsFromDB(conversation.id)
				.then((topics = []) => {
					// If database has data, update topic list and cache
					topicStore.setTopicList(topics)
					TopicCacheServices.setTopicCache(conversation.id, topics)

					if (
						conversation.isAiConversation &&
						!conversation.current_topic_id &&
						topics.length
					) {
						conversationService.switchTopic(conversation.id, topics[0].id)
					}
				})
				.catch((error: any) => {
					logger.log(error)
				})
		}

		await this.fetchTopicList()

		// If no topics, create one
		logger.log("topicStore.topicList ====> ", topicStore.topicList)
		if (!topicStore.topicList.length) {
			await this.createTopic()
		}
	}

	/**
	 * Apply create topic message
	 * @param message Message
	 */
	applyCreateTopicMessage(message: SeqResponse<CreateTopicMessage>) {
		logger.log(
			`[applyCreateTopicMessage] Begin applying create topic message, conversation ID: ${message.conversation_id}`,
		)
		try {
			const newTopic = new Topic(message.message.create_topic)
			logger.log(`[applyCreateTopicMessage] New topic info:`, newTopic)

			if (conversationStore.currentConversation?.id === message.conversation_id) {
				logger.log(`[applyCreateTopicMessage] Update current conversation UI state`)
				// Update UI state
				topicStore.unshiftTopic(newTopic)
				logger.log("topicStore.topicList ====> ", topicStore.topicList)
				// Update database
				TopicDBServices.addTopicToDB(newTopic)
			} else if (TopicCacheServices.hasTopicCache(message.conversation_id)) {
				logger.log(`[applyCreateTopicMessage] Update topic list in cache`)
				const cachedTopics = TopicCacheServices.getTopicCache(message.conversation_id)!
				const newTopics = [newTopic, ...cachedTopics]
				TopicCacheServices.setTopicCache(message.conversation_id, newTopics)

				// Update database
				TopicDBServices.addTopicToDB(newTopic)
			}
			logger.log(`[applyCreateTopicMessage] Apply create topic message completed`)
		} catch (err) {
			logger.log("applyCreateTopicMessage error", err)
		}
	}

	/**
	 * Apply update-topic message
	 * @param message Message
	 */
	applyUpdateTopicMessage(message: SeqResponse<UpdateTopicMessage>) {
		logger.log(
			`[applyUpdateTopicMessage] Start applying update topic message, conversation ID: ${message.conversation_id}`,
		)
		const updatedTopic = message.message.update_topic
		logger.log(`[applyUpdateTopicMessage] Update topic info:`, updatedTopic)

		if (conversationStore.currentConversation?.id === message.conversation_id) {
			logger.log(`[applyUpdateTopicMessage] Update current conversation UI state`)
			// Update UI state
			topicStore.updateTopic(updatedTopic.id, { name: updatedTopic.name })

			// Update database
			TopicDBServices.updateTopic(updatedTopic.id, message.conversation_id, {
				name: updatedTopic.name,
				updated_at: Date.now(),
			})
		} else if (TopicCacheServices.hasTopicCache(message.conversation_id)) {
			logger.log(`[applyUpdateTopicMessage] Update topic info in cache`)
			TopicCacheServices.updateTopicInCache(message.conversation_id, updatedTopic.id, {
				name: updatedTopic.name,
				updated_at: Date.now(),
			})

			// Update database
			TopicDBServices.updateTopic(updatedTopic.id, message.conversation_id, {
				name: updatedTopic.name,
				updated_at: Date.now(),
			})
		}
		logger.log(`[applyUpdateTopicMessage] Apply update topic message completed`)
	}

	/**
	 * Apply delete-topic message
	 * @param message Message
	 */
	applyDeleteTopicMessage(message: SeqResponse<DeleteTopicMessage>) {
		logger.log(
			`[applyDeleteTopicMessage] Start applying delete topic message, conversation ID: ${message.conversation_id}`,
		)
		const conversationId = conversationStore.currentConversation?.id
		const deletedTopicId = message.message.delete_topic.id
		logger.log(`[applyDeleteTopicMessage] Topic ID to delete: ${deletedTopicId}`)

		if (conversationId === message.conversation_id) {
			logger.log(`[applyDeleteTopicMessage] Delete topic from current conversation`)
			// Get deletion index position
			const index = topicStore.topicList.findIndex((i) => i.id === deletedTopicId)
			const topicsList = [...topicStore.topicList]
			logger.log(
				`[applyDeleteTopicMessage] Current topic list length: ${topicsList.length}, delete topic index: ${index}`,
			)

			// Delete from UI state
			topicStore.removeTopic(deletedTopicId)

			// If deleting current topic, need to switch topic
			if (
				deletedTopicId ===
				conversationStore.currentConversation?.last_receive_message?.topic_id
			) {
				if (topicsList.length > 1) {
					const target = topicsList[(index + topicsList.length - 1) % topicsList.length]
					logger.log(`[applyDeleteTopicMessage] Switch to new topic: ${target.id}`)
					conversationService.switchTopic(message.conversation_id, target.id)
				} else {
					logger.log(
						`[applyDeleteTopicMessage] No other topics to switch to, clear current topic ID`,
					)
					conversationService.switchTopic(message.conversation_id, undefined)
				}
			}

			// Update database
			TopicDBServices.deleteTopic(deletedTopicId, message.conversation_id)
		} else if (TopicCacheServices.hasTopicCache(message.conversation_id)) {
			logger.log(`[applyDeleteTopicMessage] Delete topic from cache`)
			TopicCacheServices.deleteTopicFromCache(message.conversation_id, deletedTopicId)

			// Update database
			TopicDBServices.deleteTopic(deletedTopicId, message.conversation_id)

			// If deleting current topic, need to switch topic
			if (
				deletedTopicId ===
				conversationStore.currentConversation?.last_receive_message?.topic_id
			) {
				const topicsList = TopicCacheServices.getTopicCache(message.conversation_id) || []
				logger.log(
					`[applyDeleteTopicMessage] Topic list length in cache: ${topicsList.length}`,
				)
				if (topicsList.length) {
					const index = topicsList.findIndex((topic) => topic.id === deletedTopicId)
					const target = topicsList[(index + topicsList.length - 1) % topicsList.length]
					logger.log(`[applyDeleteTopicMessage] Switch to new topic: ${target.id}`)
					conversationService.switchTopic(message.conversation_id, target.id)
				} else {
					logger.log(
						`[applyDeleteTopicMessage] No other topics in cache to switch to, clear current topic ID`,
					)
					conversationService.switchTopic(message.conversation_id, undefined)
				}
			}
		}
		logger.log(`[applyDeleteTopicMessage] Apply delete topic message completed`)
	}

	/**
	 * Fetch topic list
	 * @returns Topic list
	 */
	fetchTopicList() {
		const conversationId = this.lastConversationId

		// Set loading state
		topicStore.setLoading(true)

		logger.log(`[fetchTopicList] Start fetching topic list, conversation ID=${conversationId}`)

		if (!conversationId) {
			logger.warn("[fetchTopicList] Conversation ID does not exist, cannot fetch topic list")
			topicStore.setLoading(false)
			return Promise.reject()
		}

		if (!isAiConversation(conversationStore.currentConversation?.receive_type)) {
			topicStore.setLoading(false)
			return Promise.resolve([])
		}

		return ChatApi.getTopicList(conversationId)
			.then((res) => {
				// Convert API returned topic list to Topic instance array
				const topicsData = res?.reverse() ?? []
				const topics = topicsData.map((topicData) => new Topic(topicData))

				// Update UI state
				topicStore.setTopicList(topics)

				// Cache topic list
				TopicCacheServices.setTopicCache(conversationId, topics)

				// Persist topic list to database
				TopicDBServices.saveTopicsToDB(conversationId, topics)

				// If current topic ID doesn't exist, or current topic ID is not in topic list, update current topic ID
				const conversation = conversationStore.getConversation(conversationId)
				if (
					conversation.isAiConversation &&
					(!conversation?.current_topic_id ||
						!topics.find((i) => i.id === conversation?.current_topic_id))
				) {
					if (topics.length && conversationStore.currentConversation) {
						logger.log(
							`[fetchTopicList] Current conversation has no topic ID set, setting to first topic: ${topics[0].id}`,
						)
						conversationService.switchTopic(conversationId, topics[0].id)
					}
				}

				logger.log(
					`[fetchTopicList] Fetch topic list successful, topic count=${topics.length}`,
				)
				return topics
			})
			.finally(() => {
				topicStore.setLoading(false)
			})
	}

	/**
	 * Update topic
	 * @param topicId Topic ID
	 * @param topicName Topic name
	 * @returns Topic list
	 */
	updateTopic(topicId: string, topicName: string) {
		const conversationId = conversationStore.currentConversation?.id

		if (!conversationId) {
			logger.error("conversationId does not exist")
			return Promise.reject()
		}

		return ChatApi.updateTopic(conversationId, topicId, topicName).then(() => {
			// Update UI state
			topicStore.updateTopic(topicId, { name: topicName })

			// Update database
			TopicDBServices.updateTopic(topicId, conversationId, { name: topicName })

			return this.fetchTopicList()
		})
	}

	/**
	 * Fetch and set delightful topic name
	 * @param topicId Topic ID
	 * @param force Force update
	 * @returns Delightful topic name
	 */
	getAndSetDelightfulTopicName(topicId: string, force = false) {
		const conversationId = conversationStore.currentConversation?.id
		if (!conversationId) {
			return Promise.reject(new Error("conversationId does not exist"))
		}

		const topicName = topicStore.topicList.find((i) => i.id === topicId)?.name

		// If topic name exists and not forcing update, don't auto call
		if (topicName && !force) {
			return Promise.resolve()
		}

		// If current conversation is not AI conversation, don't call
		if (!isAiConversation(conversationStore.currentConversation?.receive_type))
			return Promise.resolve()

		return ChatApi.getDelightfulTopicName(conversationId, topicId)
			.then((res) => {
				return this.updateTopic(topicId, res.name)
			})
			.catch((error: any) => {
				logger.log(error)
			})
	}

	/**
	 * Set current conversation topic
	 * @param topicId Topic ID
	 */
	setCurrentConversationTopic(topicId: string | undefined) {
		const conversationId = conversationStore.currentConversation?.id
		logger.log(
			`[setCurrentConversationTopic] Start setting current topic, conversation ID=${conversationId}, topic ID=${
				topicId || "empty"
			}`,
		)

		if (!conversationId) {
			logger.warn(
				"[setCurrentConversationTopic] Conversation ID does not exist, cannot set topic",
			)
			return
		}

		logger.log(`[setCurrentConversationTopic] Update conversation current topic ID`)
		conversationService.switchTopic(conversationId, topicId)

		// After switching topic, re-initialize rendered message list and scroll to bottom immediately
		logger.log(`[setCurrentConversationTopic] Start initializing rendered message list`)
		MessageService.initMessages(conversationId, topicId ?? "")
	}

	/**
	 * Generate new topic data
	 * @param conversationId Conversation ID
	 * @returns New topic data
	 */
	static genNewTopicData(conversationId: string, id: string): Topic {
		const now = Date.now()
		return new Topic({
			id,
			name: "New topic",
			description: "New topic",
			conversation_id: conversationId,
			created_at: now,
			updated_at: now,
		})
	}

	/**
	 * Create topic
	 * @param topicName Topic name
	 */
	createTopic(topicName?: string) {
		const conversationId = conversationStore.currentConversation?.id
		logger.log(
			`[createTopic] Start creating topic, conversation ID=${conversationId}, topic name=${
				topicName || "Untitled"
			}`,
		)

		if (!conversationId) {
			logger.warn("[createTopic] Conversation ID does not exist, cannot create topic")
			return Promise.reject()
		}

		const tempId = nanoid()
		const tempTopic = ChatTopicService.genNewTopicData(conversationId, tempId)

		// Update UI state
		topicStore.unshiftTopic(tempTopic)

		// Update cache
		TopicCacheServices.addTopicToCache(conversationId, tempTopic)

		return ChatApi.createTopic(topicName, conversationId).then((res) => {
			if (res.data.seq.message.create_topic) {
				const newTopicId = res.data.seq.message.create_topic.id
				logger.log(`[createTopic] Topic created successfully, new topic ID=${newTopicId}`)

				// Clear message render list first
				logger.log(`[createTopic] Clear message render list`)
				// this.chatBusiness.messageRenderBusiness.clearRenderMessages()

				// Update current topic ID
				logger.log(`[createTopic] Update conversation current topic ID`)
				conversationService.switchTopic(conversationId, newTopicId)

				// Update topic list
				logger.log(`[createTopic] Update topic list`)
				const newTopic = new Topic(res.data.seq.message.create_topic)

				// Replace temporary topic
				topicStore.replaceTopic(tempId, newTopic)

				// Update database
				TopicDBServices.addTopicToDB(newTopic)

				// Re-initialize message list
				logger.log(`[createTopic] Start initializing rendered message list`)
				// this.chatBusiness.messageRenderBusiness.initRenderMessages(10, () => {
				// 	// Initialization completed, scroll to bottom immediately, use non-smooth scroll to improve speed
				// 	logger.log(`[createTopic] Initialization completed, trigger scroll to bottom event`)
				// 	const scrollEvent = new CustomEvent("conversation-scroll-to-bottom", {
				// 		detail: { smooth: false },
				// 	})
				// 	document.dispatchEvent(scrollEvent)
				// })

				return this.fetchTopicList()
			}
			return this.fetchTopicList()
		})
	}

	/**
	 * Delete topic
	 * @param deleteTopicId Topic ID to delete
	 */
	async removeTopic(deleteTopicId: string) {
		const conversationId = conversationStore.currentConversation?.id

		if (!conversationId) {
			logger.error("conversationId does not exist")
			return
		}

		const promise = ChatApi.deleteTopic(conversationId, deleteTopicId)

		const deleteId = deleteTopicId

		if (!deleteId) return

		// If last message is current topic, clear last message
		if (conversationStore.currentConversation?.last_receive_message?.topic_id === deleteId) {
			conversationService.clearLastReceiveMessage(conversationId)
		}

		// Get deletion index position and topic list copy
		const index = topicStore.topicList.findIndex((i) => i.id === deleteTopicId)
		const nextTopicId = topicStore.topicList[(index + 1) % topicStore.topicList.length]?.id

		if (conversationStore.currentConversation?.current_topic_id === deleteTopicId) {
			// If there's a next topic, switch to next topic
			if (nextTopicId && nextTopicId !== deleteTopicId) {
				conversationService.switchTopic(conversationId, nextTopicId)
			} else {
				// No next topic, clear current topic ID
				conversationService.clearCurrentTopic(conversationId)
			}
		}

		// Delete from UI state
		topicStore.removeTopic(deleteTopicId)

		// Update database
		TopicDBServices.deleteTopic(deleteTopicId, conversationId)

		// Delete topic messages
		MessageService.removeTopicMessages(conversationId, deleteTopicId)

		// After deleting topic, re-fetch topic list
		promise.finally(() => {
			this.fetchTopicList()
		})
	}
}

export default new ChatTopicService()
