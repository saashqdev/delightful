/* eslint-disable class-methods-use-this */
import { ConversationGroupKey } from "@/const/chat"
import Conversation from "@/opensource/models/chat/conversation"
import { isAiConversation } from "@/opensource/stores/chatNew/helpers/conversation"
import type { ControlEventMessageType } from "@/types/chat/control_message"
import { MessageReceiveType } from "@/types/chat"
import type { ConversationFromService } from "@/types/chat/conversation"
import { ConversationStatus } from "@/types/chat/conversation"
import conversationStore from "@/opensource/stores/chatNew/conversation"
import MessageService from "@/opensource/services/chat/message/MessageService"
import MessageCacheService from "@/opensource/services/chat/message/MessageCacheService"
import conversationSidebarStore from "@/opensource/stores/chatNew/conversationSidebar"
import EditorStore from "@/opensource/stores/chatNew/messageUI/editor"
import MessageStore from "@/opensource/stores/chatNew/message"
import { groupBy, last } from "lodash-es"
import DelightfulModal from "@/opensource/components/base/DelightfulModal"
import { t } from "i18next"
import { ConversationMessageType } from "@/types/chat/conversation_message"
import groupInfoService from "@/opensource/services/groupInfo"
import userInfoService from "@/opensource/services/userInfo"
import chatTopicService from "../topic"
import ConversationDbServices from "./ConversationDbService"
import ConversationCacheServices from "./ConversationCacheService"
import ConversationBotDataService from "./ConversationBotDataService"
import ConversationTaskService from "./ConversationTaskService"
import { getConversationGroupKey, getSlicedText } from "./utils"
import DotsService from "../dots/DotsService"
import { ChatApi } from "@/apis"
import { User } from "@/types/user"
import { userStore } from "@/opensource/models/user"
import LastConversationService from "./LastConversationService"
import MessageReplyService from "../message/MessageReplyService"
import groupInfoStore from "@/opensource/stores/groupInfo"
import { fetchPaddingData } from "@/utils/request"
import { bigNumCompare } from "@/utils/string"

/**
 * Conversation service.
 */
class ConversationService {
	/**
	 * Delightful ID
	 */
	delightfulId: string | undefined

	/**
	 * Organization code
	 */
	organizationCode: string | undefined

	/**
	 * Whether a conversation switch is in progress
	 */
	switching: boolean = false

	/**
	 * Initialize.
	 * @param delightfulId Account ID
	 * @param organizationCode Organization code
	 * @param userInfo User info
	 */
	async init(delightfulId: string, organizationCode: string, userInfo?: User.UserInfo | null) {
		this.delightfulId = delightfulId
		this.organizationCode = organizationCode

		// Load cached sidebar conversation groups
		const cache = ConversationCacheServices.getCacheConversationSiderbarGroups(
			delightfulId,
			organizationCode,
		)

		if (cache) {
			conversationSidebarStore.setConversationSidebarGroups(cache)

			// Load conversations from DB
			await this.loadConversationsFromDB(userInfo?.user_id)
		} else {
			// If not fetched before, fetch conversations for current org
			await this.refreshConversationData()
		}

		// Start message cache prewarm in next macro task
		setTimeout(() => {
			MessageCacheService.initConversationsMessage(userInfo)
		})
	}

	/**
	 * Reset conversation state
	 */
	reset() {
		conversationSidebarStore.resetConversationSidebarGroups()
		conversationStore.reset()
		this.delightfulId = undefined
		this.organizationCode = undefined
	}

	/**
	 * Refresh conversation data.
	 * @param conversationList Conversation list
	 */
	refreshConversationData(conversationList?: Conversation[]) {
		const ids = conversationList ? conversationList.map((item) => item.id) : undefined
		return fetchPaddingData(({ page_token }) => {
			return ChatApi.getConversationList(ids, {
				page_token,
				status: ConversationStatus.Normal,
			})
		})
			.then((items) => {
				if (items.length) {
					// Update conversations
					this.replaceConversations(items)
				}
			})
			.then(() => {
				// Re-fetch user and group info to keep data fresh
				this.refreshConversationReceiveData()
			})
	}

	/**
	 * Replace multiple conversations.
	 * @param filteredConversationList Conversation list
	 */
	replaceConversations(filteredConversationList: ConversationFromService[]) {
		conversationStore.replaceConversations(filteredConversationList)

		// Recalculate sidebar conversations
		this.calcSidebarConversations()

		// Update DB
		ConversationDbServices.updateConversations(
			Object.values(conversationStore.conversations).map((item) => item.toObject()),
		)
	}

	/**
	 * Initial load setup.
	 * @param delightfulId Account ID
	 * @param organizationCode Organization code
	 * @param conversations Conversation list
	 */
	initOnFirstLoad(
		delightfulId: string,
		organizationCode: string,
		conversations: ConversationFromService[],
	) {
		this.delightfulId = delightfulId
		this.organizationCode = organizationCode

		this.initConversations(conversations)
	}

	/**
	 * Handle group conversation disband.
	 * @param conversationId Conversation ID
	 */
	groupConversationDisband(conversationId: string) {
		DelightfulModal.info({
			content: t("chat.groupDisbandTip.disbandGroup", { ns: "interface" }),
			centered: true,
			okText: t("common.confirm", { ns: "interface" }),
			closable: false,
		})

		this.deleteConversation(conversationId)
		conversationStore.setCurrentConversation(undefined)
	}

	/**
	 * Switch current conversation.
	 * @param conversation Conversation
	 */
	async switchConversation(conversation?: Conversation) {
		if (!conversation) {
			conversationStore.setCurrentConversation(undefined)
			return
		}

		if (conversationStore.currentConversation?.id === conversation.id) return

		if (this.switching) return
		this.switching = true

		try {
			EditorStore.setLastConversationId(conversationStore.currentConversation?.id ?? "")
			EditorStore.setLastTopicId(
				conversationStore.currentConversation?.current_topic_id ?? "",
			)

			conversationStore.setCurrentConversation(conversation)

			// If the conversation was deleted (e.g., group disbanded), inform and switch
			if (
				conversation.status === ConversationStatus.Deleted &&
				conversation.isGroupConversation
			) {
				this.groupConversationDisband(conversation.id)
				return
			}

			// Clear Bot info
			ConversationBotDataService.clearBotInfo()
			// Clear Agent info
			ConversationTaskService.clearAgentInfo()

			// Set editor state
			EditorStore.setConversationId(conversation.id)
			EditorStore.setTopicId(conversation.current_topic_id ?? "")

			// Fetch user/group info for the conversation
			if (!conversation.isGroupConversation) {
				userInfoService.fetchUserInfos([conversation.receive_id], 2)
			}

			// If AI conversation
			if (conversation.isAiConversation) {
				await this.initAiConversation(conversation)
			}

			// Reset reply state
			MessageReplyService.reset()

			// Initialize messages for the conversation
			this.initConversationMessages(conversation)

			// If group conversation
			if (conversation.isGroupConversation) {
				this.initGroupConversation(conversation)
			}

			// Save last conversation
			LastConversationService.setLastConversation(
				this.delightfulId,
				this.organizationCode,
				conversation.id,
			)
		} catch (error) {
			console.error(error)
		} finally {
			this.switching = false
		}
	}

	initConversationMessages(conversation: Conversation) {
		// Current topic
		const messageTopicId = conversation.isAiConversation ? conversation.current_topic_id : ""

		// If AI conversation without topic id, reset messages
		if (conversation.isAiConversation && !messageTopicId) {
			MessageService.reset()
		} else {
			// Initialize message list
			MessageService.initMessages(conversation.id, messageTopicId).then(() => {
				const lastMessage = last(MessageStore.messages)
				if (lastMessage) {
					// Update last message rendering
					this.updateLastReceiveMessage(conversation.id, {
						time: lastMessage.message.send_time,
						seq_id: lastMessage.message_id,
						...getSlicedText(lastMessage.message, lastMessage.revoked),
						topic_id: lastMessage.message.topic_id ?? "",
					})
				} else {
					this.clearLastReceiveMessage(conversation.id)
				}
			})
		}

		// Reduce unread count
		console.log(
			"conversation.topic_unread_dots ====> ",
			conversation.topic_unread_dots.get(messageTopicId),
		)
		DotsService.reduceTopicUnreadDots(
			conversation.user_organization_code,
			conversation.id,
			messageTopicId,
			conversation.topic_unread_dots.get(messageTopicId) ?? 0,
		)
	}

	/**
	 * Initialize group conversation.
	 * @param conversation Conversation
	 */
	initGroupConversation(conversation: Conversation) {
		// Fetch group info
		groupInfoService.fetchGroupInfos([conversation.receive_id]).then((res) => {
			if (res.length) {
				groupInfoStore.setCurrentGroup(res[0])
			}
		})
		// Fetch group members
		groupInfoService.fetchGroupMembers(conversation.receive_id)
	}

	/**
	 * Initialize AI conversation.
	 * @param conversation Conversation
	 */
	async initAiConversation(conversation: Conversation) {
		// Initialize topic list
		await chatTopicService.initTopicList(conversation)
		const userInfo = userStore.user.userInfo
		// Prewarm topic messages
		setTimeout(() => {
			MessageCacheService.initTopicsMessage(userInfo)
		})

		// Initialize conversation agent info
		this.initConversationBotInfo(conversation)
	}

	/**
	 * Initialize conversation agent info.
	 * @param conversation Conversation
	 * @returns Conversation
	 */
	initConversationBotInfo(conversation: Conversation) {
		// Fetch bot info
		return ChatApi.getAiAssistantBotInfo({ user_id: conversation.receive_id }).then(
			async (botInfo) => {
				if (!botInfo) {
					console.error("botInfo is null, receive_id:", conversation.receive_id)
					return
				}
				// Load scheduled tasks
				ConversationTaskService.switchAgent(botInfo.root_id)
				// Initialize quick commands
				return ChatApi.getConversationList([conversation.id]).then(({ items }) => {
					if (items.length) {
						ConversationBotDataService.switchConversation(
							conversation.id,
							items[0].receive_id,
							botInfo,
							items[0].instructs,
						)
					}
				})
			},
		)
	}
	/**
	 * Create a conversation.
	 * @param receiveType Receiver type
	 * @param uid User ID
	 * @returns Conversation
	 */
	async createConversation(receiveType: MessageReceiveType, uid: string) {
		const { data } = await ChatApi.createConversation(receiveType, uid)

		if (conversationStore.hasConversation(data.seq.message.open_conversation.id)) {
			return conversationStore.getConversation(data.seq.message.open_conversation.id)
		}

		const { items } = await ChatApi.getConversationList([data.seq.message.open_conversation.id])

		return this.addNewConversation(items[0])
	}

	/**
	 * Load conversations from DB.
	 * @param calcSidebarConversations Whether to recalc sidebar conversations
	 */
	loadConversationsFromDB(userId: string | undefined, calcSidebarConversations = true) {
		if (!this.organizationCode || !userId) return
		return ConversationDbServices.loadNormalConversationsFromDB(
			this.organizationCode,
			userId,
		).then((conversations) => {
			console.log("loadConversationsFromDB ====> ", conversations)

			const conversationList = conversations.map((item) => new Conversation(item))

			conversationStore.setConversations(conversationList)

			// If no current conversation or it belongs to another org, set it
			if (
				!conversationStore.currentConversation ||
				conversationStore.currentConversation.user_organization_code !==
					this.organizationCode
			) {
				const lastConversation = conversationStore.getConversation(
					LastConversationService.getLastConversation(
						this.delightfulId,
						this.organizationCode,
					) ?? conversationList?.[0]?.id,
				)
				this.switchConversation(lastConversation)
			}

			if (calcSidebarConversations) {
				this.calcSidebarConversations()
			}

			// Refresh conversation data
			this.refreshConversationData(conversationList)
		})
	}

	/**
	 * Calculate sidebar conversations
	 */
	calcSidebarConversations() {
		const sidebarGroups = conversationStore.calcSidebarConversations(
			Object.keys(conversationStore.conversations),
			conversationStore.conversations,
		)

		console.log("sidebarGroups ====> ", sidebarGroups)

		conversationSidebarStore.setConversationSidebarGroups(sidebarGroups)
		ConversationCacheServices.cacheConversationSiderbarGroups(
			this.delightfulId,
			this.organizationCode,
			sidebarGroups,
		)
	}

	/**
	 * Refresh receiver (user/group) data for conversations
	 */
	refreshConversationReceiveData() {
		const {
			[MessageReceiveType.Group]: groupIds = [],
			[MessageReceiveType.User]: userIds = [],
			[MessageReceiveType.Ai]: aiIds = [],
		} = groupBy(Object.values(conversationStore.conversations), (item) => item.receive_type)

		// Fetch group info
		if (groupIds.length) {
			groupInfoService.fetchGroupInfos(groupIds.map((item) => item.receive_id))
		}

		// Fetch user info
		const allUserIds = [...new Set([...aiIds, ...userIds])]
		if (allUserIds.length) {
			userInfoService.fetchUserInfos(
				allUserIds.map((item) => item.receive_id),
				2,
			)
		}
	}

	/**
	 * Update pin status.
	 * @param conversationId Conversation ID
	 * @param isTop Pin flag
	 */
	updateTopStatus(conversationId: string, isTop: 0 | 1) {
		// Update conversation status (including group move)
		conversationStore.updateTopStatus(conversationId, isTop)

		// Update DB
		ConversationDbServices.updateTopStatus(conversationId, isTop)

		// Update cache
		this.cacheConversationSiderbarGroups()
	}

	setTopStatus(conversationId: string, isTop: 0 | 1) {
		ChatApi.topConversation(conversationId, isTop)
		this.updateTopStatus(conversationId, isTop)
	}

	/**
	 * Update conversation mute (do-not-disturb).
	 * @param conversationId Conversation ID
	 * @param isNotDisturb DND flag
	 */
	notDisturbConversation(conversationId: string, isNotDisturb: 0 | 1) {
		conversationStore.updateConversationDisturbStatus(conversationId, isNotDisturb)
		// Update DB
		ConversationDbServices.updateNotDisturbStatus(conversationId, isNotDisturb)
	}

	/**
	 * Set conversation DND status (and sync to server).
	 * @param conversationId Conversation ID
	 * @param isNotDisturb DND flag
	 */
	setNotDisturbStatus(conversationId: string, isNotDisturb: 0 | 1) {
		ChatApi.muteConversation(conversationId, isNotDisturb)
		this.notDisturbConversation(conversationId, isNotDisturb)
	}

	/**
	 * Update conversation's default topic open state.
	 * @param conversation Conversation
	 * @param open Whether open by default
	 */
	updateTopicOpen(conversation: Conversation, open: boolean) {
		if (!conversation) return

		// For AI conversations, persist the setting
		if (isAiConversation(conversation.receive_type)) {
			conversation.setTopicDefaultOpen(open)
			conversationStore.updateConversationTopicDefaultOpen(conversation.id, open)

			// Update DB
			ConversationDbServices.updateTopicDefaultOpen(conversation.id, open)
		}

		conversationStore.setTopicOpen(open)
	}

	/**
	 * Unshift conversations into a sidebar group.
	 * @param menuKey Sidebar group key
	 * @param ids Conversation IDs
	 */
	unshiftConversations(menuKey: ConversationGroupKey, ...ids: string[]) {
		if (!this.organizationCode) return

		conversationStore.updateSidebarGroup(menuKey, ids)

		// Update cache
		this.cacheConversationSiderbarGroups()
	}

	/**
	 * Cache sidebar conversation groups
	 */
	private cacheConversationSiderbarGroups() {
		ConversationCacheServices.cacheConversationSiderbarGroups(
			this.delightfulId,
			this.organizationCode,
			conversationSidebarStore.getConversationSiderbarGroups(),
		)
	}

	/**
	 * Add a conversation from DB by ID.
	 * @param conversationId Conversation ID
	 */
	async addNewConversationFromDB(conversationId: string) {
		const conversation = await ConversationDbServices.getConversation(conversationId)
		if (conversation) {
			conversationStore.initConversations([conversation as Conversation])
		}
	}

	addNewConversation(conversation: ConversationFromService) {
		const newConversation = conversationStore.addNewConversation(conversation)

		// Update cache
		this.cacheConversationSiderbarGroups()

		ConversationDbServices.addConversationsToDB([newConversation])

		return newConversation
	}

	/**
	 * Initialize conversations from service payload.
	 * @param conversationShouldHandle Conversations
	 */
	initConversations(conversationShouldHandle: ConversationFromService[]) {
		if (!this.organizationCode) return

		const conversations = conversationShouldHandle.map((item) => new Conversation(item))

		// Add to store
		conversationStore.initConversations(conversations)
		// Update cache
		this.cacheConversationSiderbarGroups()

		// Persist to DB
		ConversationDbServices.addConversationsToDB(conversations)
	}

	/**
	 * Switch current topic ID for a conversation.
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 */
	switchTopic(conversationId: string, topicId: string | undefined) {
		console.log("switchTopic ====> ", conversationId, topicId)
		if (!conversationId) return
		EditorStore.setLastConversationId(conversationStore.currentConversation?.id ?? "")
		EditorStore.setLastTopicId(conversationStore.currentConversation?.current_topic_id ?? "")

		const conversation = conversationStore.conversations[conversationId]
		if (conversation) {
			const tId = topicId ?? ""
			conversation.setCurrentTopicId(tId)

			// Reduce unread count for current topic
			const topicUnreadDots = conversation.topic_unread_dots.get(tId) ?? 0
			if (topicUnreadDots > 0) {
				DotsService.reduceTopicUnreadDots(
					conversation.user_organization_code,
					conversationId,
					tId,
					topicUnreadDots,
				)
			}

			conversationStore.updateConversationCurrentTopicId(conversationId, topicId ?? "")

			// Reset reply state
			MessageReplyService.reset()

			this.initConversationMessages(conversation)

			// Update DB
			ConversationDbServices.updateCurrentTopicId(conversationId, topicId ?? "")
		}
	}

	clearCurrentTopic(conversationId: string) {
		if (!conversationId) return
		EditorStore.setLastConversationId(conversationStore.currentConversation?.id ?? "")
		EditorStore.setLastTopicId(conversationStore.currentConversation?.current_topic_id ?? "")

		const conversation = conversationStore.conversations[conversationId]
		if (conversation) {
			conversationStore.updateConversationCurrentTopicId(conversationId, "")

			// Reduce unread count for current topic
			const topicUnreadDots = conversation.topic_unread_dots.get("") ?? 0
			if (topicUnreadDots > 0) {
				DotsService.reduceTopicUnreadDots(
					conversation.user_organization_code,
					conversationId,
					"",
					topicUnreadDots,
				)
			}

			// Reset reply state
			MessageReplyService.reset()

			this.initConversationMessages(conversation)

			// Update DB
			ConversationDbServices.updateCurrentTopicId(conversationId, "")
		}
	}

	/**
	 * Delete a conversation by ID.
	 * @param conversationId Conversation ID
	 */
	deleteConversation(conversationId: string) {
		if (!conversationId) return
		const nextConversationId = conversationStore.removeConversation(conversationId)
		// If the deleted one is current, switch to next
		if (
			nextConversationId &&
			// If there's no current, or IDs match the deleted one
			(!conversationStore.currentConversation?.id ||
				conversationStore.currentConversation?.id === conversationId)
		) {
			this.switchConversation(conversationStore.getConversation(nextConversationId))
		}
		// Cache sidebar groups
		this.cacheConversationSiderbarGroups()
		// Remove from DB (currently disabled)
		// ConversationDbServices.deleteConversation(conversationId)
	}

	/**
	 * Delete conversations in batch.
	 * @param conversationIds Conversation ID list
	 */
	deleteConversations(conversationIds: string[]) {
		if (!conversationIds.length) return
		conversationStore.removeConversations(conversationIds)
		// Cache sidebar groups
		this.cacheConversationSiderbarGroups()
		// Remove from DB (currently disabled)
		// ConversationDbServices.deleteConversations(conversationIds)
	}

	/**
	 * Update the last received message for a conversation.
	 * @param conversationId Conversation ID
	 * @param message Last message
	 */
	updateLastReceiveMessage(
		conversationId: string,
		message: {
			time: number
			seq_id: string
			type: ConversationMessageType | ControlEventMessageType
			text: string
			topic_id: string
		} | null,
	) {
		if (!conversationId) return

		if (!message) {
			message = {
				time: 0,
				seq_id: "",
				type: ConversationMessageType.Text,
				text: "",
				topic_id: "",
			}
		}

		// If message has no text, skip update (likely initial streaming state)
		if (!message.text) return

		const time = conversationStore
			.getConversation(conversationId)
			?.getLastMessageTime(message.time)

		if (conversationStore.hasConversation(conversationId)) {
			const conversation = conversationStore.getConversation(conversationId)

			const messageTopicId = message.topic_id ?? ""
			const conversationTopicId = conversation.current_topic_id ?? ""

			// If the message seq_id is older than the last one, skip
			if (
				conversation.last_receive_message?.seq_id &&
				bigNumCompare(message.seq_id, conversation.last_receive_message?.seq_id ?? "") < 0
			)
				return

			// If not current conversation and topic differs, update topic and last message
			if (conversationId !== conversationStore.currentConversation?.id) {
				// If topic differs, update current topic ID
				if (messageTopicId !== conversationTopicId) {
					conversationStore.updateConversationCurrentTopicId(
						conversationId,
						messageTopicId,
					)
				}
				conversation.setLastReceiveMessageAndLastReceiveTime(message)
				// Update DB
				ConversationDbServices.updateConversation(conversationId, {
					last_receive_message_time: time,
					current_topic_id: messageTopicId,
					last_receive_message: message,
				})
			}
			// If current conversation and topic matches, update last message
			else if (
				conversationId === conversationStore.currentConversation?.id &&
				messageTopicId === conversationTopicId
			) {
				conversation.setLastReceiveMessageAndLastReceiveTime(message)
				// Update DB
				ConversationDbServices.updateConversation(conversationId, {
					last_receive_message_time: time,
					last_receive_message: message,
				})
			}
		}
	}

	/**
	 * Clear the last message.
	 * @param conversationId Conversation ID
	 */
	clearLastReceiveMessage(conversationId: string) {
		if (!conversationId) return
		conversationStore.updateConversationLastMessage(conversationId, undefined)
		// Update DB
		ConversationDbServices.updateConversation(conversationId, {
			last_receive_message: undefined,
		})
	}
	/**
	 * Start typing indicator for conversation.
	 * @param conversation_id Conversation ID
	 */
	startConversationInput(conversationId: string) {
		conversationStore.updateConversationReceiveInputing(conversationId, true)
	}

	/**
	 * End typing indicator for conversation.
	 */
	endConversationInput(conversationId: string) {
		conversationStore.updateConversationReceiveInputing(conversationId, false)
	}

	/**
	 * Whether there is an unconfirmed record of group disbanding.
	 * @param id Conversation ID
	 * @returns Whether such a record exists
	 */
	hasConversationDisbandGroupUnConfirmRecord(id: string) {
		console.log("id", id)
		// FIXME: Temporarily return true
		return true
	}

	/**
	 * Confirm group disband.
	 * @param id Conversation ID
	 */
	confirmDisbandGroupConversation(id: string) {
		if (!id) return

		// FIXME: Temporary stub
		console.log("confirmDisbandGroupConversation", id)
	}

	/**
	 * Update conversation status.
	 * @param conversation_id Conversation ID
	 * @param status Status
	 */
	updateConversationStatus(conversation_id: string, status: ConversationStatus) {
		if (!conversation_id) return

		const conversation = conversationStore.conversations[conversation_id]
		if (conversation) {
			conversation.status = status

			conversationStore.updateConversationStatus(conversation_id, status)

			// Update DB
			ConversationDbServices.updateStatus(conversation_id, status)
		}
	}

	/**
	 * Move a conversation to the top of its group.
	 * @param conversation_id Conversation ID
	 */
	moveConversationFirst(conversation_id: string) {
		if (!conversation_id) return

		const conversation = conversationStore.getConversation(conversation_id)
		if (!conversation) return

		let menuKey = getConversationGroupKey(conversation)

		if (conversation.is_top) {
			menuKey = ConversationGroupKey.Top
		}

		conversationSidebarStore.moveConversationFirst(conversation_id, menuKey)

		if ([ConversationGroupKey.User, ConversationGroupKey.AI].includes(menuKey)) {
			conversationSidebarStore.moveConversationFirst(
				conversation_id,
				ConversationGroupKey.Single,
			)
		}

		// Update cache
		this.cacheConversationSiderbarGroups()
	}
}

export default new ConversationService()
