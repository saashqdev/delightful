import type { CreateGroupConversationParamKey } from "@/opensource/apis/modules/chat/types"
import type { ControlEventMessageType } from "./control_message"
import type { SeqMessageBase } from "./base"
import type { GroupConversationType } from "./conversation"
import type { ConversationMessageStatus } from "./conversation_message"

/**
 * Read receipt
 */
export interface SeenMessage extends SeqMessageBase {
	type: ControlEventMessageType.SeenMessages
	seen_messages: {
		refer_message_ids: string[]
	}
	/** Sent time */
	send_time: number
	/** Message status */
	status: ConversationMessageStatus
	/** Unread message count */
	unread_count: number
	/** Topic ID */
	topic_id: string
}

/**
 * Create group conversation params
 */
export type CreateGroupConversationParams = {
	[CreateGroupConversationParamKey.group_name]: string
	[CreateGroupConversationParamKey.group_avatar]: string
	[CreateGroupConversationParamKey.group_type]: GroupConversationType
	[CreateGroupConversationParamKey.user_ids]: string[]
	[CreateGroupConversationParamKey.department_ids]: string[]
}
