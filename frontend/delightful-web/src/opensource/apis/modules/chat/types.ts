import type { SeqResponse } from "@/types/request"

export const enum SeqRecordType {
	seq = "seq",
	/** Stream type message */
	stream_seq = "stream_seq",
}

/**
 * seq message
 */
export type SeqRecord<M> = DefaultSeqRecord<M>

export interface DefaultSeqRecord<M> {
	type: SeqRecordType.seq
	seq: SeqResponse<M>
}

/**
 * Get conversation name - Response
 */
export type GetDelightfulTopicNameResponse = {
	conversation_id: string
	id: string
	name: string
}

/**
 * Create group chat - Parameters
 */
export enum CreateGroupConversationParamKey {
	group_name = "group_name",
	group_avatar = "group_avatar",
	group_type = "group_type",
	user_ids = "user_ids",
	department_ids = "department_ids",
}

/**
 * Message receiver list - Response
 */
export type MessageReceiveListResponse = {
	unseen_list: string[]
	seen_list: string[]
	read_list: string[]
}

/**
 * Get conversation AI auto-completion - Response
 */
export type GetConversationAiAutoCompletionResponse = {
	choices: [
		{
			message: {
				role: "assistant"
				content: string
			}
		},
	]
	request_info: {
		conversation_id: string
		message: string
	}
}

/**
 * Get conversation messages - Parameters
 */
export type GetConversationMessagesParams = {
	topic_id?: string
	time_start?: string
	time_end?: string
	page_token?: string
	limit?: number
	order?: string
}
