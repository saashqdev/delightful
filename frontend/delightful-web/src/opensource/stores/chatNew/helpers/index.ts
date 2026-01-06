import { type CMessage, type MessageMaxSeqInfo } from "@/types/chat"
import type { SeqResponse } from "@/types/request"
import { bigNumCompare } from "@/utils/string"
import { platformKey } from "@/utils/storage"
import type { MessageCanRevoke, ConversationMessageType } from "@/types/chat/conversation_message"
import type { GroupUsersRemoveMessage } from "@/types/chat/control_message"
import { CONVERSATION_MESSAGE_CAN_REVOKE_TYPES, ConversationGroupKey } from "@/const/chat"
import { unique } from "radash"
import { isAppMessageId } from "@/utils/random"

/**
 * Whether the message can be revoked
 * @param message Message
 * @returns Whether the message can be revoked
 */
export function isRevokableMessage(
	message: SeqResponse<CMessage>,
): message is SeqResponse<MessageCanRevoke> {
	return CONVERSATION_MESSAGE_CAN_REVOKE_TYPES.includes(
		message?.message?.type as ConversationMessageType,
	)
}

/**
 * Whether the user left the group themselves
 * @param message Message
 * @param userId User ID
 * @returns Whether the user left the group themselves
 */
export function isSelfLeaveGroup(message: GroupUsersRemoveMessage, userId?: string) {
	return (
		userId &&
		message.group_users_remove.operate_user_id === userId &&
		message.group_users_remove.user_ids.length === 1 &&
		message.group_users_remove.user_ids[0] === userId
	)
}

/**
 * Unique and sorted message IDs
 * @param messageIds Message ID list
 * @returns Unique and sorted message ID list
 */
export function uniqueAndSortMessageIds(messageIds: string[]) {
	return unique(messageIds).sort((a, b) => {
		const isAppMessageA = isAppMessageId(a)
		const isAppMessageB = isAppMessageId(b)

		if (isAppMessageA && !isAppMessageB) {
			return 1
		}

		if (!isAppMessageA && isAppMessageB) {
			return -1
		}

		return bigNumCompare(a, b)
	})
}

const genMaxSeqInfoKey = (delightful_id: string) => platformKey(`chat/max_seq_info/${delightful_id}`)

/**
 * Get maximum sequence number
 * @returns
 */
export const getChatMaxSeqInfo = (delightful_id: string) =>
	JSON.parse(
		localStorage.getItem(genMaxSeqInfoKey(delightful_id)) ??
			JSON.stringify({
				user_local_seq_id: "",
			}),
	) as MessageMaxSeqInfo

/**
 * Set maximum sequence number
 * @param max_seq_info Maximum sequence number
 * @returns
 */
export const setChatMaxSeqInfo = (delightful_id: string, max_seq_info: MessageMaxSeqInfo) =>
	localStorage.setItem(genMaxSeqInfoKey(delightful_id), JSON.stringify(max_seq_info))

/**
 * Add conversation to group
 * @param list Conversation group
 * @param conversationId Conversation ID
 */
export function addToConversationGroup(list: string[], conversationId: string) {
	if (!list.includes(conversationId)) {
		list.unshift(conversationId)
	}
}

/**
 * Create conversation groups
 * @returns Conversation groups
 */
export const createConversationGroup = (): Record<ConversationGroupKey, string[]> => ({
	[ConversationGroupKey.Top]: [],
	[ConversationGroupKey.Single]: [],
	[ConversationGroupKey.User]: [],
	[ConversationGroupKey.AI]: [],
	[ConversationGroupKey.Group]: [],
	[ConversationGroupKey.Other]: [],
})

/**
 * Remove conversation from group
 * @param list Conversation group
 * @param conversationId Conversation ID
 */
export const removeFromConversationGroup = (list: string[], conversationId: string) => {
	if (list.includes(conversationId)) {
		list.splice(
			list.findIndex((item) => item === conversationId),
			1,
		)
	}
}
