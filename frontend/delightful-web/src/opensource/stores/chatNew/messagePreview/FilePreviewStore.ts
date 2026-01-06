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
	 * 设置预览信息
	 * @param info 预览信息
	 */
	openPreview(info?: FilePreviewInfo) {
		if (info) {
			this.previewInfo = info
		}
		this.open = true
	}

	/**
	 * 清除预览信息
	 */
	clearPreviewInfo() {
		this.previewInfo = undefined
		this.open = false
	}
}

export default new MessageFilePreviewStore()
