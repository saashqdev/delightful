import { MessageReceiveType } from "."
import { SeqMessageBase } from "./base"
import type { OpenConversationMessage } from "./conversation"
import { ConversationMessageBase, ConversationMessageStatus } from "./conversation_message"
import type { SeenMessage } from "./seen_message"
import type { CreateTopicMessage, UpdateTopicMessage, DeleteTopicMessage } from "./topic"

/**
 * Control event type
 */

export const enum ControlEventMessageType {
	/** Open (create) conversation */
	OpenConversation = "open_conversation",
	/** Create conversation */
	CreateConversation = "create_conversation",
	/** Read receipt */
	SeenMessages = "seen_messages",
	/** Create topic */
	CreateTopic = "create_topic",
	/** Update topic */
	UpdateTopic = "update_topic",
	/** Delete topic */
	DeleteTopic = "delete_topic",
	/** Set conversation topic */
	SetConversationTopic = "set_conversation_topic",
	/** Revoke message */
	RevokeMessage = "revoke_message",
	/** Mute conversation */
	MuteConversation = "mute_conversation",
	/** Pin group chat */
	TopConversation = "top_conversation",
	/** Hide conversation */
	HideConversation = "hide_conversation",
	/** Group created */
	GroupCreate = "group_create",
	/** Group member added */
	GroupAddMember = "group_users_add",
	/** Group disbanded */
	GroupDisband = "group_disband",
	/** Group member removed */
	GroupUsersRemove = "group_users_remove",
	/** Group updated */
	GroupUpdate = "group_update",
	/** Friend request accepted */
	AddFriendSuccess = "add_friend_success",
}

export interface AddFriendSuccessMessage extends SeqMessageBase {
	type: ControlEventMessageType.AddFriendSuccess
	/** Friend request accepted */
	add_friend_success: {
		/** User ID */
		user_id: string
		/** Receiver ID */
		receive_id: string
		/** Receiver type */
		receive_type: MessageReceiveType
	}
}
/**
 * Revoke message
 */

export interface RevokeMessage extends ConversationMessageBase {
	type: ControlEventMessageType.RevokeMessage
	revoke_message: {
		/** Revoked message ID */
		refer_message_id: string
	}
}
/**
 * Group created message
 */

export interface GroupCreateMessage extends ConversationMessageBase {
	type: ControlEventMessageType.GroupCreate
	/** Unread count */
	unread_count: number
	/** Send time */
	send_time: number
	/** Status */
	status: ConversationMessageStatus
	group_create: {
		/** Operator user ID */
		operate_user_id: string
		/** Group ID */
		group_id: string
		/** User ID list */
		user_ids: string[]
		/** Conversation ID */
		conversation_id: string
		/** Group name */
		group_name: string
		/** Group owner ID */
		group_owner_id: string
	}
}
/**
 * Group disbanded message
 */

export interface GroupDisbandMessage extends ConversationMessageBase {
	type: ControlEventMessageType.GroupDisband
	/** Unread count */
	unread_count: number
	/** Send time */
	send_time: number
	/** Status */
	status: ConversationMessageStatus
}
/**
 * Group member added
 */

export interface GroupAddMemberMessage extends ConversationMessageBase {
	type: ControlEventMessageType.GroupAddMember
	/** Unread count */
	unread_count: number
	/** Send time */
	send_time: number
	/** Status */
	status: ConversationMessageStatus
	/** Group members added */
	group_users_add: {
		/** Operator user ID */
		operate_user_id: string
		/** Group ID */
		group_id: string
		/** User ID list */
		user_ids: string[]
		/** Conversation ID */
		conversation_id: string
	}
}
/**
 * Group member removed
 */

export interface GroupUsersRemoveMessage extends ConversationMessageBase {
	type: ControlEventMessageType.GroupUsersRemove
	/** Unread count */
	unread_count: number
	/** Send time */
	send_time: number
	/** Status */
	status: ConversationMessageStatus
	group_users_remove: {
		/** Operator user ID */
		operate_user_id: string
		/** Group ID */
		group_id: string
		/** User ID list */
		user_ids: string[]
		/** Conversation ID */
		conversation_id: string
	}
}
/**
 * Group updated
 */

export interface GroupUpdateMessage extends ConversationMessageBase {
	type: ControlEventMessageType.GroupUpdate
	/** Unread count */
	unread_count: number
	/** Send time */
	send_time: number
	/** Status */
	status: ConversationMessageStatus
	group_update: {
		/** Operator user ID */
		operate_user_id: string
		/** Group ID */
		group_id: string
		/** Conversation ID */
		conversation_id: string
		/** Group name */
		group_name: string
		/** Group avatar */
		group_avatar: string
	}
}
/**
 * Pin conversation
 */

export interface TopConversationMessage extends ConversationMessageBase {
	type: ControlEventMessageType.TopConversation
	[ControlEventMessageType.TopConversation]: {
		conversation_id: string
		is_top: 0 | 1
	}
}
/**
 * Mute conversation
 */

export interface MuteConversationMessage extends ConversationMessageBase {
	type: ControlEventMessageType.MuteConversation
	[ControlEventMessageType.MuteConversation]: {
		conversation_id: string
		is_not_disturb: 0 | 1
	}
}
/**
 * Hide conversation
 */

export interface HideConversationMessage extends ConversationMessageBase {
	type: ControlEventMessageType.HideConversation
	[ControlEventMessageType.HideConversation]: {
		conversation_id: string
	}
}
/**
 * Control message type
 */

export type ControlMessage =
	| OpenConversationMessage
	| CreateTopicMessage
	| UpdateTopicMessage
	| DeleteTopicMessage
	| SeenMessage
	| TopConversationMessage
	| MuteConversationMessage
	| HideConversationMessage
	| AddFriendSuccessMessage
