import { makeAutoObservable } from "mobx"
import MessageStore from "@/opensource/stores/chatNew/message"
import type { FullMessage } from "@/types/chat/message"
import type { ConversationMessage } from "@/types/chat/conversation_message"
import type { ImagePreviewInfo } from "@/types/chat/preview"
import { SeqResponse } from "@/types/request"

class MessageImagePreviewStore {
	/**
	 * Preview information
	 */
	previewInfo: ImagePreviewInfo | undefined = undefined

	/**
	 * Whether preview window is open
	 */
	open: boolean = false

	/**
	 * Message
	 */
	message: FullMessage<ConversationMessage> | SeqResponse<ConversationMessage> | undefined

	constructor() {
		makeAutoObservable(this, {}, { autoBind: true })
	}

	/**
	 * Set preview information
	 * @param info Preview information
	 */
	setPreviewInfo(info: ImagePreviewInfo) {
		this.previewInfo = { ...info }
		if (info.messageId) {
			this.message = MessageStore.getMessage(info.messageId)
		} else {
			this.message = undefined
		}
		this.setOpen(true)
	}

	/**
	 * Clear preview information
	 */
	clearPreviewInfo() {
		this.previewInfo = undefined
		this.setOpen(false)
	}

	/**
	 * Set preview window open state
	 * @param open Whether to open
	 */
	setOpen(open: boolean) {
		this.open = open
	}
}

export default new MessageImagePreviewStore()
