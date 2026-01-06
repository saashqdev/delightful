import type { ControlEventMessageType } from "./control_message"
import type { SeqMessageBase } from "./base"
import type { ConversationMessageBase } from "./conversation_message"

export interface UpdateTopicMessage extends SeqMessageBase {
	type: ControlEventMessageType.UpdateTopic
	[ControlEventMessageType.UpdateTopic]: ConversationTopic
}

/**
 * Create topic message
 */
export interface CreateTopicMessage extends ConversationMessageBase {
	type: ControlEventMessageType.CreateTopic
	[ControlEventMessageType.CreateTopic]: ConversationTopic
}

/**
 * Delete topic message
 */
export interface DeleteTopicMessage extends ConversationMessageBase {
	type: ControlEventMessageType.DeleteTopic
	[ControlEventMessageType.DeleteTopic]: ConversationTopic
}

/**
 * Topic
 */
export interface ConversationTopic {
	id: string
	name: string
	description: string
	conversation_id: string
	created_at?: number
	updated_at?: number
}

/**
 * Edit message
 */
export interface EditMessage extends ConversationMessageBase {
	type: ControlEventMessageType.EditMessage
	[ControlEventMessageType.EditMessage]: {
		refer_message_id: string
		markdown: string
		text: string
		rich_text: string
	}
}
