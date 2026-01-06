import { makeAutoObservable } from "mobx"
import type { FullMessage } from "@/types/chat/message"

class ReplyStore {
	/**
	 * Reply message ID
	 */
	replyMessageId: string | undefined = undefined

	/**
	 * Reply message
	 */
	replyMessage: FullMessage | undefined = undefined

	/**
	 * Reply message file
	 */
	replyFile:
		| {
				fileId: string | undefined
				referText: string | undefined
		  }
		| undefined = undefined

	constructor() {
		makeAutoObservable(this, {}, { autoBind: true })
	}

	/**
	 * Set reply message
	 * @param messageId Message ID
	 * @param message Message
	 */
	setReplyMessage(messageId: string, message: FullMessage) {
		this.replyMessageId = messageId
		this.replyMessage = message
		console.log("setReplyMessage", this.replyMessageId, this.replyMessage)
	}

	/**
	 * Reset reply message
	 */
	resetReplyMessage() {
		this.replyMessageId = undefined
		this.replyMessage = undefined
	}

	/**
	 * Set reply message file
	 * @param fileId File ID
	 * @param referText Referenced text
	 */
	setReplyFile(fileId: string, referText: string) {
		this.replyFile = {
			fileId,
			referText,
		}
	}

	/**
	 * Reset reply message file
	 */
	resetReplyFile() {
		this.replyFile = undefined
	}
}

export default new ReplyStore()
