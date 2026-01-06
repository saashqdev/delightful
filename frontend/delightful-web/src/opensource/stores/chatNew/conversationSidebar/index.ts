import { ConversationGroupKey } from "@/const/chat"
import { last } from "lodash-es"
import { makeAutoObservable } from "mobx"
import conversationStore from "../conversation"

class ConversationSiderbarStore {
	/**
	 * Sidebar conversation groups
	 */
	conversationSiderbarGroups: Record<ConversationGroupKey, string[]> = {
		[ConversationGroupKey.Top]: [],
		[ConversationGroupKey.Single]: [],
		[ConversationGroupKey.User]: [],
		[ConversationGroupKey.AI]: [],
		[ConversationGroupKey.Group]: [],
		[ConversationGroupKey.Other]: [],
	}

	constructor() {
		makeAutoObservable(this)
	}

	/**
	 * Add a conversation to a group
	 * @param groupKey Group key
	 * @param conversationId Conversation ID
	 */
	addConversationToGroup(groupKey: ConversationGroupKey, conversationId: string) {
		if (!this.conversationSiderbarGroups[groupKey]) {
			this.conversationSiderbarGroups[groupKey] = []
		}

		this.moveConversationFirst(conversationId, groupKey)

		// If in User or AI group, also add to Single group
		if ([ConversationGroupKey.User, ConversationGroupKey.AI].includes(groupKey)) {
			this.moveConversationFirst(conversationId, ConversationGroupKey.Single)
		}
	}

	/**
	 * Remove a conversation from a group
	 * @param groupKey Group key
	 * @param conversationId Conversation ID
	 */
	removeConversationFromGroup(
		groupKey: ConversationGroupKey,
		conversationId: string,
	): string | undefined {
		const index = this.conversationSiderbarGroups[groupKey].indexOf(conversationId)
		let nextConversationId

		if (index !== -1) {
			this.conversationSiderbarGroups[groupKey].splice(index, 1)
			nextConversationId =
				index > this.conversationSiderbarGroups[groupKey].length - 1
					? last(this.conversationSiderbarGroups[groupKey])
					: this.conversationSiderbarGroups[groupKey][index]
		} else {
			;[nextConversationId] = this.conversationSiderbarGroups[groupKey]
		}

		// If in User or AI group, also remove from Single group
		if ([ConversationGroupKey.User, ConversationGroupKey.AI].includes(groupKey)) {
			const singleIndex =
				this.conversationSiderbarGroups[ConversationGroupKey.Single].indexOf(conversationId)
			if (singleIndex !== -1) {
				this.conversationSiderbarGroups[ConversationGroupKey.Single].splice(singleIndex, 1)
				nextConversationId =
					singleIndex >
					this.conversationSiderbarGroups[ConversationGroupKey.Single].length - 1
						? last(this.conversationSiderbarGroups[ConversationGroupKey.Single])
						: this.conversationSiderbarGroups[ConversationGroupKey.Single][singleIndex]
			} else {
				;[nextConversationId] = this.conversationSiderbarGroups[ConversationGroupKey.Single]
			}
		}

		return nextConversationId
	}

	/**
	 * Reset conversation groups
	 */
	resetConversationSidebarGroups() {
		this.conversationSiderbarGroups = {
			[ConversationGroupKey.Top]: [],
			[ConversationGroupKey.Single]: [],
			[ConversationGroupKey.User]: [],
			[ConversationGroupKey.AI]: [],
			[ConversationGroupKey.Group]: [],
			[ConversationGroupKey.Other]: [],
		}
	}

	/**
	 * Update a conversation group
	 * @param groupKey Group key
	 * @param conversationIds List of conversation IDs
	 */
	updateConversationSiderbarGroups(groupKey: ConversationGroupKey, conversationIds: string[]) {
		this.conversationSiderbarGroups[groupKey] = Array.from(new Set(conversationIds))
	}

	/**
	 * Get all conversation groups
	 * @returns Map of group key to conversation ID list
	 */
	getConversationSiderbarGroups() {
		return this.conversationSiderbarGroups
	}

	/**
	 * Get a conversation group
	 * @param groupKey Group key
	 * @returns Conversation ID list
	 */
	getConversationSiderbarGroup(groupKey: ConversationGroupKey) {
		return this.conversationSiderbarGroups[groupKey]
	}

	/**
	 * Set conversation groups
	 * @param conversationSiderbarGroups Conversation groups
	 */
	setConversationSidebarGroups(
		conversationSiderbarGroups: Record<ConversationGroupKey, string[]>,
	) {
		this.conversationSiderbarGroups = conversationSiderbarGroups
	}

	/**
	 * Move a conversation to the top
	 * @param conversation_id Conversation ID
	 */
	moveConversationFirst(conversation_id: string, menuKey: ConversationGroupKey) {
		if (!conversation_id) return

		const index = this.conversationSiderbarGroups[menuKey].indexOf(conversation_id)

		// Remove if it exists
		if (index !== -1) {
			this.conversationSiderbarGroups[menuKey].splice(index, 1)
		}

		// Add to the top
		this.conversationSiderbarGroups[menuKey].unshift(conversation_id)
	}

	/**
	 * Move below all pinned conversations
	 * @param conversation_id Conversation ID
	 * @param menuKey Group key
	 */
	moveAfterTopConversations(conversation_id: string, menuKey: ConversationGroupKey) {
		if (!conversation_id) return

		// Remove conversation
		const index = this.conversationSiderbarGroups[menuKey].findIndex(
			(id) => id === conversation_id,
		)
		this.conversationSiderbarGroups[menuKey].splice(index, 1)

		// Move below all pinned conversations
		const firstIndexNotTop = this.conversationSiderbarGroups[menuKey].findIndex(
			(id) => !conversationStore.conversations[id].is_top,
		)
		if (firstIndexNotTop !== -1) {
			this.conversationSiderbarGroups[menuKey].splice(firstIndexNotTop, 0, conversation_id)
		} else {
			this.conversationSiderbarGroups[menuKey].push(conversation_id)
		}
	}
}

export default new ConversationSiderbarStore()
