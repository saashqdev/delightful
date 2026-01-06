import type { SeqResponse } from "@/types/request"
import type { CMessage } from "@/types/chat"
import type { ConversationMessageSend } from "@/types/chat/conversation_message"
import type { Conversation } from "@/types/chat/conversation"
import { t } from "i18next"
import type { StructureUserItem } from "@/types/organization"

/**
 * Get user name
 * @param userDetail User detail
 * @returns User name
 */
export function getUserName(userDetail?: StructureUserItem) {
	if (!userDetail) return t("common.unknown", { ns: "interface" })
	if (userDetail.nickname && userDetail.nickname.length > 0) {
		return userDetail.nickname
	}
	if (userDetail.nickname && userDetail.nickname.length > 0) {
		return userDetail.nickname
	}
	return userDetail.user_id
}

/**
 * Get user job title
 * @param userDetail User detail
 * @returns User job title
 */
export function getUserJobTitle(userDetail?: StructureUserItem) {
	return userDetail?.job_title
}

/**
 * Get the user's first department path
 * @param userDetail User detail
 * @returns First department path
 */
export function getUserDepartmentFirstPath(userDetail?: StructureUserItem) {
	if (!userDetail) return t("common.unknown", { ns: "interface" })
	return userDetail.path_nodes?.[0]?.path || t("common.unknown", { ns: "interface" })
}

/**
 * Collect conversation messages
 * @param conversation Conversation detail
 * @param penddingMessages Pending messages
 * @returns
 */
export function collectConversationMessages(
	conversation: Conversation | undefined,
	messages: Record<string, SeqResponse<CMessage>>,
	penddingMessages: Record<string, ConversationMessageSend>,
): (CMessage | ConversationMessageSend["message"])[] {
	if (!conversation) return []
	return [
		...conversation.messageIds
			.map((id) => {
				const message = messages[id]
				if (!message) {
					console.warn(`message ${id} not found`)
				}
				return message.message
			})
			.filter(Boolean),
		...Object.values(penddingMessages)
			.filter((msg) => msg.conversation_id === conversation.id)
			.map((msg) => msg.message),
	]
}

/**
 * Title with count suffix
 * @param title Title
 * @param count Count
 * @returns
 */
export function titleWithCount(title: string, count: number) {
	if (!count) return title
	return `${title} (${count})`
}
