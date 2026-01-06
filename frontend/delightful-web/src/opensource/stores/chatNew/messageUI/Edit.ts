import { FullMessage } from "@/types/chat/message"
import { makeAutoObservable } from "mobx"

/**
 * Message editor store
 */
class MessageEditStore {
	/**
	 * Message being edited
	 */
	editMessage: FullMessage | undefined = undefined

	constructor() {
		makeAutoObservable(this, {}, { autoBind: true })
	}

	/**
	 * Set message to edit
	 * @param message Message
	 */
	setEditMessage(message: FullMessage) {
		this.editMessage = message
	}

	/**
	 * Reset message editor
	 */
	resetEditMessage() {
		this.editMessage = undefined
	}
}

export default new MessageEditStore()
