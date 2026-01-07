import type { ConversationTopic } from "@/types/chat/topic"

class Topic implements ConversationTopic {
	/**
	 * Topic ID
	 */
	id: string

	/**
	 * Topic name
	 */
	name: string

	/**
	 * Topic description
	 */
	description: string

	/**
	 * Conversation ID this topic belongs to
	 */
	conversation_id: string

	/**
	 * Creation time
	 */
	created_at?: number

	/**
	 * Update time
	 */
	updated_at?: number

	constructor(data: ConversationTopic) {
		this.id = data.id
		this.name = data.name
		this.description = data.description
		this.conversation_id = data.conversation_id
		this.created_at = data.created_at || Date.now()
		this.updated_at = data.updated_at || Date.now()
	}

	/**
	 * Update topic name
	 * @param name New name
	 */
	updateName(name: string) {
		this.name = name
		this.updated_at = Date.now()
	}

	/**
	 * Update topic description
	 * @param description New description
	 */
	updateDescription(description: string) {
		this.description = description
		this.updated_at = Date.now()
	}

	/**
	 * Clone topic
	 * @returns New topic instance
	 */
	clone(): Topic {
		return new Topic({
			id: this.id,
			name: this.name,
			description: this.description,
			conversation_id: this.conversation_id,
			created_at: this.created_at,
			updated_at: this.updated_at,
		})
	}
}

export default Topic
