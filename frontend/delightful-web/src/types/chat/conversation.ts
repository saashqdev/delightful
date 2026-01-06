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
	/** 发送时间 */
	send_time: number
	/** 打开会话 */
	open_conversation: {
		/** 接收者 ID */
		receive_id: string
		/** 接收者类型 */
		receive_type: MessageReceiveType
		/** 会话 ID */
		id: string
	}
}

/**
 * 群聊类型
 */
export enum GroupConversationType {
	/** 内部群 */
	Internal = 1,
	/** 内部培训群 */
	InternalTraining = 2,
	/** 内部会议群 */
	InternalMeeting = 3,
	/** 内部项目群 */
	InternalProject = 4,
	/** 内部工单群 */
	InternalWorkOrder = 5,
	/** 外部群 */
	External = 6,
}

export enum GroupStatus {
	/** 正常 */
	Normal = 1,
	/** 解散 */
	Dismiss = 2,
}

/**
 * 群聊详情
 */
export interface GroupConversationDetail {
	/** 群聊ID */
	id: string
	/** 群主 */
	group_owner: string
	/** 组织编码 */
	organization_code: string
	/** 群聊名称 */
	group_name: string
	/** 群聊头像 */
	group_avatar: string
	/** 群聊公告 */
	group_tag: string
	/** 群聊类型 */
	group_type: GroupConversationType
	/** 群聊状态 */
	group_status: GroupStatus
	/** 群公告 */
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
