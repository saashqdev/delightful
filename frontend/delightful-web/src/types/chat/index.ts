import type { SeqResponse } from "../request"
import type { User } from "../user"
import type {
	AggregateAISearchCardConversationMessage,
	AggregateAISearchCardConversationMessageV2,
	ConversationMessage,
	BeDelightfulContent,
} from "./conversation_message"
import type { ControlMessage } from "./control_message"
import type { ConversationFromService } from "./conversation"
import type { IntermediateMessage } from "./intermediate_message"

/** Message recipient type */
export const enum MessageReceiveType {
	Ai = 0,
	/** User */
	User = 1,
	/** Group */
	Group = 2,
	/** System message */
	System = 3,
	/** Cloud document */
	CloudDocument = 4,
	/** Multi-dimensional table */
	MultiTable = 5,
	/** Topic */
	Topic = 6,
	/** App message */
	App = 7,
}

/** Maximum sequence number */
export interface MessageMaxSeqInfo {
	user_local_seq_id?: string
}

/**
 * Server push event type
 */
export const enum EventType {
	/** Login */
	Login = "login",
	/** Chat */
	Chat = "chat",
	/** Streaming chat */
	Stream = "stream",
	/** Control */
	Control = "control",
	/** Intermediate message */
	Intermediate = "intermediate",
	/** Create conversation window */
	CreateConversationWindow = "create_conversation_window",
}

/**
 * Server push event response
 */
export interface EventResponseMap {
	[EventType.Login]: User.UserInfo
	[EventType.Chat]: { type: "seq"; seq: SeqResponse<CMessage> }
	[EventType.Control]: { type: "seq"; seq: SeqResponse<CMessage> }
	[EventType.Stream]: { type: "seq"; seq: SeqResponse<CMessage> }
	[EventType.CreateConversationWindow]: { conversation: ConversationFromService }
	[EventType.Intermediate]: { type: "seq"; seq: SeqResponse<CMessage> }
}

/**
 * Server push event response structure
 */
export type EventResponse<E extends EventType> = {
	type: E
	payload: EventResponseMap[E]
}

/**
 * Message
 */
export type CMessage =
	| ControlMessage
	| IntermediateMessage
	| ConversationMessage
	| AggregateAISearchCardConversationMessage<true>
	| AggregateAISearchCardConversationMessageV2
	| BeDelightfulContent
