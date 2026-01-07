import type Topic from "."

/**
 * Topic list classification
 */
export enum TopicGroup {
	/**
	 * All topics
	 */
	All = "all",
	/**
	 * Pinned topics
	 */
	Pinned = "pinned",
	/**
	 * Recently used topics
	 */
	Recent = "recent",
}

/**
 * Topic list
 */
export interface TopicList {
	/**
	 * Conversation ID
	 */
	conversation_id: string

	/**
	 * Topic list
	 */
	topic_list: Topic[]
}
