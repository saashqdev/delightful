import type { SeqMessageBase } from "./base"
import type { ConversationMessageStatus } from "./conversation_message"

/**
 * Intermediate (real-time) message types
 */

export const enum IntermediateMessageType {
	/** Start conversation input */
	StartConversationInput = "start_conversation_input",
	/** End conversation input */
	EndConversationInput = "end_conversation_input",
}

/**
 * Start conversation input message
 */
export interface StartConversationInputMessage extends SeqMessageBase {
	type: IntermediateMessageType.StartConversationInput
	unread_count: number
	send_time: number
	status: ConversationMessageStatus
}

/**
 * End conversation input message
 */
export interface EndConversationInputMessage extends SeqMessageBase {
	type: IntermediateMessageType.EndConversationInput
	unread_count: number
	send_time: number
	status: ConversationMessageStatus
}
/**
 * Intermediate message union
 */

export type IntermediateMessage = StartConversationInputMessage | EndConversationInputMessage
