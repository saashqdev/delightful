import type { SeqRecord } from "@/opensource/apis/modules/chat/types"
import type { User } from "./user"
import type { CMessage, EventType } from "./chat"
import type { IntermediateMessage } from "./chat/intermediate_message"

/**
 * Stream message status
 */

export const enum StreamStatus {
	/** Stream message start */
	Start = 0,
	/** Streaming */
	Streaming = 1,
	/** Stream message end */
	End = 2,
}

/**
 * Common response
 */
export interface CommonResponse<D> {
	code: number
	message: string
	data: D
}

/**
 * Server push message
 */
export type WebSocketPayload =
	| {
			type: Omit<EventType, EventType.Stream>
			payload: SeqRecord<CMessage>
	  }
	| {
			type: EventType.Stream
			payload: StreamResponseV2
	  }

/**
 * Stream message response
 * @deprecated Please use StreamResponseV2 instead
 */
export type StreamResponse = {
	target_seq_id: string
	reasoning_content: string
	status: StreamStatus
	content: string
	llm_response: string
}

/**
 * Stream message response V2
 */
export type StreamResponseV2 = {
	streams: {
		stream_options: {
			status: StreamStatus
		}
	} & Record<string, unknown>
	target_seq_id: string
}

/**
 * Intermediate message response
 */
export type IntermediateResponse = SeqRecord<IntermediateMessage>

/**
 * Pagination response
 */
export interface PaginationResponse<D> {
	/** Data */
	items: D[]
	/** Whether there is more data */
	has_more: boolean
	/** Pagination token */
	page_token: string
}

export type SeqResponse<S = object> = {
	/** User unique ID */
	delightful_id: string
	/** Message sequence ID */
	seq_id: string
	/** Message ID */
	message_id: string
	/** Reference message ID */
	refer_message_id: string
	/** Sender message ID */
	sender_message_id: string
	/** Conversation ID */
	conversation_id: string
	/** Organization code */
	organization_code: string
	/** Message content */
	message: S
}

/**
 * Login response
 */
export type LoginResponse = {
	type: "user"
	user: User.UserInfo
}

/**
 * WebSocket connection success response
 */
export type WebsocketOpenResponse = {
	sid: string
	upgrades: ["websocket"]
	pingInterval: number
	pingTimeout: number
}
