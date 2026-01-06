import { FilePreviewInfo } from "@/types/chat/preview"
import { makeAutoObservable } from "mobx"

/**
 * File preview store
 */
class MessageFilePreviewStore {
	/**
	 * Preview information
	 */
	previewInfo: FilePreviewInfo | undefined = undefined

	/**
	 * Whether preview window is open
	 */
	open: boolean = false

	constructor() {
		makeAutoObservable(this, {}, { autoBind: true })
	}

	/**
	 * Set preview information
	 * @param info Preview information
	 */
	openPreview(info?: FilePreviewInfo) {
		if (info) {
			this.previewInfo = info
		}
		this.open = true
	}

	/**
	 * Clear preview information
	 */
	clearPreviewInfo() {
		this.previewInfo = undefined
		this.open = false
	}
}

export default new MessageFilePreviewStore()
