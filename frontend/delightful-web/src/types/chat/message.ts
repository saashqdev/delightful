import type {
	ConversationMessage,
	ConversationMessageStatus,
	ConversationMessageType,
	SendStatus,
} from "@/types/chat/conversation_message"
import type { ControlEventMessageType } from "./control_message"

export interface Message<M extends ConversationMessage = ConversationMessage> {
	/** Local temporary ID */
	temp_id?: string
	/** Unique user ID */
	delightful_id: string
	/** Message sequence ID */
	seq_id?: string
	/** Message ID */
	message_id: string
	/** Referenced message ID */
	refer_message_id?: string
	/** Sender message ID */
	sender_message_id?: string
	/** Conversation ID */
	conversation_id: string
	/** Message type */
	type: ConversationMessageType | ControlEventMessageType
	/** Message content */
	message: M
	/** Whether the message is revoked */
	revoked?: boolean
	/** Sent time */
	send_time: string
}

export interface FullMessage<M extends ConversationMessage = ConversationMessage>
	extends Message<M> {
	unread_count: number
	sender_id: string
	name: string
	avatar: string
	is_self?: boolean
	seen_status: ConversationMessageStatus
	send_status: SendStatus
	is_unreceived?: boolean
}

export interface MessagePage {
	page?: number
	pageSize?: number
	totalPages?: number
	messages: FullMessage[]
}

export interface UserInfo {
	id: string
	name: string
	avatar: string
}

export type ApplyMessageOptions = {
	isHistoryMessage?: boolean
	isFromOtherTab?: boolean
	sortCheck?: boolean
	updateLastSeqId?: boolean
}
