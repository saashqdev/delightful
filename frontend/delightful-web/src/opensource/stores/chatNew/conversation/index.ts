import { ConversationGroupKey } from "@/const/chat"
import Conversation from "@/opensource/models/chat/conversation"
import { groupBy, keyBy } from "lodash-es"
import { makeAutoObservable } from "mobx"
import { getConversationGroupKey } from "@/opensource/services/chat/conversation/utils"
import { ConversationStatus, type ConversationFromService } from "@/types/chat/conversation"
import Logger from "@/utils/log/Logger"
import conversationSiderbarStore from "@/opensource/stores/chatNew/conversationSidebar"
import type { UserTask } from "@/types/chat/task"
import type { ConversationMessageType } from "@/types/chat/conversation_message"
import type { ControlEventMessageType } from "@/types/chat/control_message"

const console = new Logger("ConversationStore")

/**
 * Conversation UI state management
 */
class ConversationStore {
	/**
	 * Current conversation
	 */
	currentConversation: Conversation | undefined

	/**
	 * Conversation task list
	 */
	conversationTaskList: UserTask[] = []

	/**
	 * Topic panel open state
	 */
	topicOpen: boolean = false

	/**
	 * Settings panel open state
	 */
	settingOpen: boolean = false

	/**
	 * Conversation input state
	 */
	conversationReceiveInputing: boolean = false

	/**
	 * Conversation list
	 */
	conversations: Record<string, Conversation> = {}

	/**
	 * Conversation input state
	 */
	receive_inputing: boolean = false

	selectText: string = ""

	setSelectText(text: string) {
		this.selectText = text
	}

	constructor() {
		makeAutoObservable(this, {}, { autoBind: true })
	}

	/**
	 * Add new conversation
	 * @param conversation Conversation
	 * @returns Conversation
	 */
	addNewConversation(conversation: ConversationFromService) {
		const c = new Conversation(conversation)
		this.conversations[conversation.id] = c

		let menuKey = getConversationGroupKey(this.conversations[conversation.id])

		if (c.is_top) {
			menuKey = ConversationGroupKey.Top
		}

		if (conversation.status === ConversationStatus.Normal) {
			conversationSiderbarStore.addConversationToGroup(menuKey, conversation.id)
		}

		return c
	}

	/**
	 * Set current conversation
	 * @param conversation Conversation
	 */
	setCurrentConversation(conversation: Conversation | undefined) {
		this.currentConversation = conversation
		this.topicOpen = conversation?.topic_default_open ?? false
		this.settingOpen = false
	}

	getCurrentConversation() {
		return this.currentConversation
	}

	getConversationLastMessage(conversationId: string) {
		const conversation = this.conversations[conversationId]
		if (!conversation) return null
		return conversation.last_receive_message
	}

	/**
	 * Toggle settings panel open state
	 */
	toggleSettingOpen() {
		this.settingOpen = !this.settingOpen
	}

	/**
	 * Set conversation list
	 * @param conversations Conversations
	 */
	setConversations(conversations: Conversation[]) {
		this.conversations = keyBy(conversations, "id")
	}

	/**
	 * Update default topic open state
	 * @param open Whether open
	 */
	setTopicOpen(open: boolean) {
		this.topicOpen = open
	}

	/**
	 * Update conversation pin status
	 * @param conversationId Conversation ID
	 * @param isTop Pinned (1) or not (0)
	 */
	updateTopStatus(conversationId: string, isTop: 0 | 1) {
		const conversation = this.conversations[conversationId]
		if (!conversation) return

		const conversationGroupKey = conversation.isGroupConversation
			? ConversationGroupKey.Group
			: ConversationGroupKey.Single

		// Update group
		const oldGroupKey = isTop ? conversationGroupKey : ConversationGroupKey.Top
		const newGroupKey = isTop ? ConversationGroupKey.Top : conversationGroupKey

		// Remove from old group
		conversationSiderbarStore.removeConversationFromGroup(oldGroupKey, conversationId)

		// Add to new group
		conversationSiderbarStore.addConversationToGroup(newGroupKey, conversationId)

		// Actual GroupKey
		const actualGroupKey = getConversationGroupKey(conversation)

		if (isTop) {
			// Move conversation to top of the group
			conversationSiderbarStore.moveConversationFirst(conversationId, actualGroupKey)
		} else {
			// Move conversation below all pinned conversations
			conversationSiderbarStore.moveAfterTopConversations(conversationId, actualGroupKey)
		}

		// Update pinned status
		conversation.is_top = isTop
	}

	/**
	 * Update conversation do-not-disturb status
	 * @param conversationId Conversation ID
	 * @param isNotDisturb Do-not-disturb (1) or not (0)
	 */
	updateConversationDisturbStatus(conversationId: string, isNotDisturb: 0 | 1) {
		const conversation = this.conversations[conversationId]
		if (!conversation) return
		conversation.setNotDisturb(isNotDisturb)
	}

	/**
	 * Update conversation status
	 * @param conversationId Conversation ID
	 * @param status Status value
	 */
	updateConversationStatus(conversationId: string, status: number) {
		const conversation = this.conversations[conversationId]
		if (!conversation) return

		conversation.setStatus(status)
	}

	/**
	 * Update conversation default topic open state
	 * @param conversationId Conversation ID
	 * @param open Whether open by default
	 */
	updateConversationTopicDefaultOpen(conversationId: string, open: boolean) {
		const conversation = this.conversations[conversationId]
		if (!conversation) return

		conversation.setTopicDefaultOpen(open)
	}

	/**
	 * Update conversation current topic ID
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 */
	updateConversationCurrentTopicId(conversationId: string, topicId: string) {
		const conversation = this.conversations[conversationId]
		if (!conversation) return

		if (this.currentConversation?.id === conversationId) {
			this.currentConversation.setCurrentTopicId(topicId)
		}
		conversation.setCurrentTopicId(topicId)
	}

	/**
	 * Update conversation last message
	 * @param conversationId Conversation ID
	 * @param message Last message
	 */
	updateConversationLastMessage(
		conversationId: string,
		message:
			| {
					time: number
					seq_id: string
					text: string
					topic_id: string
					type: ConversationMessageType | ControlEventMessageType
			  }
			| undefined,
	) {
		const conversation = this.conversations[conversationId]
		if (!conversation) return

		conversation.setLastReceiveMessageAndLastReceiveTime(message)
	}

	/**
	 * Initialize conversation list
	 * @param conversations Conversations
	 */
	initConversations(conversations: Conversation[]) {
		conversations.forEach((conversation) => {
			this.conversations[conversation.id] = new Conversation(conversation)
		})

		const result = this.calcSidebarConversations(
			Object.keys(this.conversations),
			this.conversations,
		)

		// Update sidebar groups
		Object.entries(result).forEach(([key, value]) => {
			this.updateSidebarGroup(key as ConversationGroupKey, value)
		})
	}

	/**
	 * Sort conversations
	 * @param convA Conversation A
	 * @param convB Conversation B
	 * @returns Sort result
	 */
	sortConversations(convA: Conversation, convB: Conversation) {
		// Pinned conversations go first
		if (convA.is_top && !convB.is_top) {
			return -1
		}

		if (!convA.is_top && convB.is_top) {
			return 1
		}

		// Conversations without messages go last
		if (!convA.last_receive_message && !convB.last_receive_message) {
			return 0
		}

		// Conversations without messages go last
		if (!convA.last_receive_message) {
			return 1
		}

		if (!convB.last_receive_message) {
			return -1
		}

		// Sort by the time of the last message
		return convB.last_receive_message.time - convA.last_receive_message.time
	}

	/**
	 * Calculate sidebar conversations
	 * @param conversations Conversation list
	 * @returns Conversation groups
	 */
	calcSidebarConversations(ids: string[], conversations: Record<string, Conversation>) {
		const top = new Set<string>()

		const {
			[ConversationGroupKey.User]: userConversations = [],
			[ConversationGroupKey.AI]: aiConversations = [],
			[ConversationGroupKey.Group]: groupConversations = [],
			[ConversationGroupKey.Other]: otherConversations = [],
		} = groupBy(ids, (id) => {
			if (conversations[id].is_top) {
				top.add(id)
			}

			return getConversationGroupKey(conversations[id])
		})

		const singleConversations = [...userConversations, ...aiConversations].filter(
			(id) => !top.has(id),
		)

		const singleConversationsSorted = singleConversations.sort((a, b) =>
			this.sortConversations(conversations[a], conversations[b]),
		)

		return {
			[ConversationGroupKey.Top]: Array.from(top).sort((a, b) =>
				this.sortConversations(conversations[a], conversations[b]),
			),
			[ConversationGroupKey.Single]: singleConversationsSorted,
			[ConversationGroupKey.User]: userConversations.sort((a, b) =>
				this.sortConversations(conversations[a], conversations[b]),
			),
			[ConversationGroupKey.AI]: aiConversations.sort((a, b) =>
				this.sortConversations(conversations[a], conversations[b]),
			),
			[ConversationGroupKey.Group]: groupConversations.sort((a, b) =>
				this.sortConversations(conversations[a], conversations[b]),
			),
			[ConversationGroupKey.Other]: otherConversations.sort((a, b) =>
				this.sortConversations(conversations[a], conversations[b]),
			),
		}
	}

	/**
	 * Update conversation group
	 * @param menuKey Menu group
	 * @param ids Conversation IDs
	 */
	updateSidebarGroup(menuKey: ConversationGroupKey, ids: string[]) {
		conversationSiderbarStore.updateConversationSiderbarGroups(menuKey, ids)
	}

	/**
	 * Remove a conversation from a group
	 * @param menuKey Menu group
	 * @param id Conversation ID
	 */
	removeConversation(id: string): string | undefined {
		if (!id) return undefined

		const conversation = this.getConversation(id)
		if (!conversation) return undefined

		let menuKey = getConversationGroupKey(conversation)

		if (conversation.is_top) {
			menuKey = ConversationGroupKey.Top
		}

		const nextConversationId = conversationSiderbarStore.removeConversationFromGroup(
			menuKey,
			id,
		)

		this.removeConversationRecord(id)

		return nextConversationId
	}

	/**
	 * Remove conversations from list
	 * @param ids Conversation IDs
	 */
	removeConversations(ids: string[]) {
		ids.forEach((id) => this.removeConversation(id))
	}

	/**
	 * Move a conversation to the top of a target group
	 * @param targetGroupKey Target group key
	 * @param conversationId Conversation ID
	 */
	moveConversationToGroupTop(targetGroupKey: ConversationGroupKey, conversationId: string) {
		const currentGroup = conversationSiderbarStore
			.getConversationSiderbarGroup(targetGroupKey)
			.filter((id) => id !== conversationId)

		conversationSiderbarStore.updateConversationSiderbarGroups(targetGroupKey, [
			conversationId,
			...currentGroup,
		])
	}

	/**
	 * Move a conversation from one group to another group's top
	 * @param fromGroupKey Source group key
	 * @param toGroupKey Target group key
	 * @param conversationId Conversation ID
	 */
	moveConversationBetweenGroups(
		fromGroupKey: ConversationGroupKey,
		toGroupKey: ConversationGroupKey,
		conversationId: string,
	) {
		// Remove from source group
		const sourceGroup = conversationSiderbarStore
			.getConversationSiderbarGroup(fromGroupKey)
			.filter((id) => id !== conversationId)

		// Prepare target group
		const targetGroup = conversationSiderbarStore
			.getConversationSiderbarGroup(toGroupKey)
			.filter((id) => id !== conversationId)

		// Update all changes at once
		conversationSiderbarStore.updateConversationSiderbarGroups(fromGroupKey, sourceGroup)
		conversationSiderbarStore.updateConversationSiderbarGroups(toGroupKey, [
			conversationId,
			...targetGroup,
		])
	}

	/**
	 * Remove a conversation from list
	 * @param id Conversation ID
	 */
	removeConversationRecord(id: string) {
		if (this.conversations[id]) {
			// If deleting the current conversation, clear current selection
			if (this.currentConversation?.id === id) {
				this.currentConversation = undefined
				this.receive_inputing = false
			}

			// Delete from conversation list
			const { [id]: removed, ...rest } = this.conversations
			this.conversations = rest
		}
	}

	/**
	 * Update conversation typing state
	 * @param arg1 Typing state
	 */
	updateConversationReceiveInputing(conversationId: string, arg1: boolean) {
		const conversation = this.conversations[conversationId]
		if (!conversation) return

		conversation.setReceiveInputing(arg1)
	}

	/**
	 * Increase conversation unread dots
	 * @param conversationId Conversation ID
	 * @param dots Dot count
	 */
	addConversationDots(conversationId: string, dots: number) {
		const conversation = this.conversations[conversationId]
		if (!conversation) return

		conversation.addUnreadDots(dots)

		console.log("addConversationDots ====> ", conversation, dots)
	}

	/**
	 * Decrease conversation unread dots
	 * @param conversationId Conversation ID
	 * @param dots Dot count
	 */
	reduceConversationDots(conversationId: string, dots: number) {
		const conversation = this.conversations[conversationId]
		if (!conversation) return

		conversation.reduceUnreadDots(dots)
	}

	/**
	 * Reset conversation unread dots
	 * @param conversationId Conversation ID
	 */
	resetConversationDots(conversationId: string) {
		const conversation = this.conversations[conversationId]
		if (!conversation) return

		conversation.resetUnreadDots()
	}

	/**
	 * Get conversation unread dots
	 * @param conversationId Conversation ID
	 * @returns Dot count
	 */
	getConversationDots(conversationId: string) {
		const conversation = this.conversations[conversationId]
		if (!conversation) return 0

		return conversation.unread_dots
	}

	/**
	 * Get total unread dots for all topics in a conversation
	 * @param conversationId Conversation ID
	 * @returns Total unread per topics
	 */
	getAllTopicUnreadDots(conversationId: string) {
		const conversation = this.conversations[conversationId]
		if (!conversation) return new Map()
		return conversation.topic_unread_dots
	}

	/**
	 * Get unread dots for a conversation topic
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 * @returns Unread count
	 */
	getTopicUnreadDots(conversationId: string, topicId: string) {
		const conversation = this.conversations[conversationId]
		if (!conversation) return 0

		return conversation.topic_unread_dots.get(topicId) || 0
	}

	/**
	 * Increase unread dots for a conversation topic
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 * @param dots Unread count
	 */
	addTopicUnreadDots(conversationId: string, topicId: string, dots: number) {
		const conversation = this.conversations[conversationId]
		if (!conversation) return
		conversation.addTopicUnreadDots(topicId, dots)
	}

	/**
	 * Decrease unread dots for a conversation topic
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 * @param dots Unread count
	 */
	reduceTopicUnreadDots(conversationId: string, topicId: string, dots: number) {
		const conversation = this.conversations[conversationId]
		if (!conversation) return
		conversation.reduceTopicUnreadDots(topicId, dots)
	}

	/**
	 * Reset all topic unread dots for a conversation
	 * @param conversationId Conversation ID
	 */
	resetTopicUnreadDots(conversationId: string) {
		const conversation = this.conversations[conversationId]
		if (!conversation) return

		conversation.resetAllTopicUnreadDots()
	}

	/**
	 * Check whether a conversation exists
	 * @param id Conversation ID
	 * @returns Whether exists
	 */
	hasConversation(id: string) {
		return this.conversations[id]
	}

	getConversation(id: string) {
		return this.conversations[id]
	}

	setConversationTaskList(items: UserTask[]) {
		this.conversationTaskList = items
	}

	updateConversations(filteredConversationList: ConversationFromService[]) {
		filteredConversationList.forEach((conversation) => {
			if (this.conversations[conversation.id]) {
				this.conversations[conversation.id]?.updateFromRemote(conversation)
			} else {
				this.conversations[conversation.id] = new Conversation(conversation)
			}
		})
	}

	replaceConversations(filteredConversationList: ConversationFromService[]) {
		const object: Record<string, Conversation> = {}
		filteredConversationList.forEach((conversation) => {
			object[conversation.id] = new Conversation({
				...this.getConversation(conversation.id)?.toObject(),
				...conversation,
			})
		})

		this.conversations = object
	}

	reset() {
		this.conversations = {}
		this.currentConversation = undefined
		this.conversationTaskList = []
		this.topicOpen = false
		this.settingOpen = false
		this.conversationReceiveInputing = false
		this.selectText = ""
	}
}

export default new ConversationStore()
