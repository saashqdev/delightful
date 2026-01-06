import { Content } from "@tiptap/core"
import { makeAutoObservable } from "mobx"

class EditorStore {
	lastConversationId = ""

	lastTopicId = ""

	/** Current conversation id */
	conversationId = ""

	/** Current topic id */
	topicId = ""

	/** Value */
	value: string | Content | undefined = undefined

	constructor() {
		makeAutoObservable(this, {}, { autoBind: true })
	}

	/** Whether content is valid */
	get isValidContent() {
		if (this.value === undefined || this.value === null) return false
		// Simple check if has type property
		if (typeof this.value === "object" && "type" in this.value) return true
		return false
	}

	/**
	 * Set last conversation id
	 * @param conversationId Conversation id
	 */
	setLastConversationId(conversationId: string) {
		this.lastConversationId = conversationId
	}

	/**
	 * Set last topic id
	 * @param topicId Topic id
	 */
	setLastTopicId(topicId: string) {
		this.lastTopicId = topicId
	}

	/**
	 * Set current conversation id
	 * @param conversationId Conversation id
	 */
	setConversationId(conversationId: string) {
		this.conversationId = conversationId
	}

	/**
	 * Set current topic id
	 * @param topicId Topic id
	 */
	setTopicId(topicId: string) {
		this.topicId = topicId
	}

	/**
	 * Set value
	 * @param value Value
	 */
	setValue = (value: string | Content | undefined) => {
		this.value = value
	}
}

export default new EditorStore()
