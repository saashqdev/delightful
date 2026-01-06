import { makeAutoObservable } from "mobx"

class EditorStore {
	/** Conversation ID */
	conversationId: string | undefined
	/** Topic ID */
	topicId: string | undefined
	/** Value */
	value: string = ""

	constructor() {
		makeAutoObservable(this, {}, { autoBind: true })
	}

	/** Whether has valid content */
	get isValidContent() {
		if (this.value === undefined || this.value === null) return false
		// Simple check for type property
		if (typeof this.value === "object" && "type" in this.value) return true
		return false
	}

	/** Initialize */
	switch(conversationId: string, topicId: string = "") {
		this.conversationId = conversationId
		this.topicId = topicId
	}

	/**
	 * Set value
	 * @param value Value
	 */
	setValue = (value: string) => {
		this.value = value
	}
}

export default new EditorStore()
