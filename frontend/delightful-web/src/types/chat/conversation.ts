import type { MessageReceiveType } from "."
import type { ControlEventMessageType } from "./control_message"
import type { SeqMessageBase } from "./base"

export const enum ConversationStatus {
	/** Normal */
	Normal = 0,
	/** Hidden */
	Hidden = 1,
	/** Deleted */
	Deleted = 2,
}

/**
 * Conversation (from service)
 */
export interface ConversationFromService {
	/** Conversation ID */
	id: string
	/** User ID */
	user_id: string
	/** User organization code */
	user_organization_code: string
	/** Receiver type */
	receive_type: MessageReceiveType
	/** Receiver ID */
	receive_id: string
	/** Receiver organization code */
	receive_organization_code: string
	/** Mute conversation */
	is_not_disturb: 0 | 1
	/** Pin conversation */
	is_top: 0 | 1
	/** Mark conversation */
	is_mark: 0 | 1
	/** Extra info */
	extra: string
	/** Creation time */
	created_at: string
	/** Update time */
	updated_at: string
	/** Delete time */
	deleted_at: string | null
	/** Current topic ID (deprecated) */
	// current_topic_id: string
	/** Status */
	status: ConversationStatus
	/** Translation config */
	translate_config: unknown | null
	/** Quick command config */
	instructs?: Record<string, unknown>
	// instruct?: string
}

/**
 * Conversation
 */
export interface Conversation extends ConversationFromService {
	/** Message ID list */
	messageIds: string[]
	/** Receiver is typing */
	receive_inputing: boolean
	/** Current topic ID */
	currentTopicId?: string
	/** Last received conversation message ID (for list display) */
	lastReceiveMessageId?: string
	/** Last received conversation message seqId */
	lastReceiveSeqId?: string
	/** Message IDs by topic */
	topicMessageIds?: Map<string, string[]>
	/** Last received conversation message ID by topic */
	topicLastReceiveMessageId?: Map<string, string>
	/** Last received conversation message seqId by topic */
	topicLastReceiveSeqId?: Map<string, string>
}

/**
 * Create/open conversation message
 */

export interface OpenConversationMessage extends SeqMessageBase {
	type: ControlEventMessageType.OpenConversation | ControlEventMessageType.CreateConversation
	/** Sent time */
	send_time: number
	/** Open conversation */
	open_conversation: {
		/** Receiver ID */
		receive_id: string
		/** Receiver type */
		receive_type: MessageReceiveType
		/** Conversation ID */
		id: string
	}
}

/**
 * Group chat type
 */
export enum GroupConversationType {
	/** Internal group */
	Internal = 1,
	/** Internal training group */
	InternalTraining = 2,
	/** Internal meeting group */
	InternalMeeting = 3,
	/** Internal project group */
	InternalProject = 4,
	/** Internal work order group */
	InternalWorkOrder = 5,
	/** External group */
	External = 6,
}

export enum GroupStatus {
	/** Normal */
	Normal = 1,
	/** Dissolved */
	Dismiss = 2,
}

/**
 * Group chat detail
 */
export interface GroupConversationDetail {
	/** Group chat ID */
	id: string
	/** Group owner */
	group_owner: string
	/** Organization code */
	organization_code: string
	/** Group chat name */
	group_name: string
	/** Group chat avatar */
	group_avatar: string
	/** Group chat announcement */
	group_tag: string
	/** Group chat type */
	group_type: GroupConversationType
	/** Group chat status */
	group_status: GroupStatus
	/** Group notice */
	group_notice: string
}

export interface GroupConversationDetailWithConversationId extends GroupConversationDetail {
	conversation_id: string
}

export interface GroupConversationMember {
	id: string
	group_id: string
	user_id: string
	user_role: number
	user_type: number
	status: number
	organization_code: string
	deleted_at: string | null
	created_at: number
	updated_at: number
}
