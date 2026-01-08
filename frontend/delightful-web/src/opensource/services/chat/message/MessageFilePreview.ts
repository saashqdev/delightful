import { PREVIEW_EXTENSIONS } from "@/const/file"
import MessageFilePreviewStore from "@/opensource/stores/chatNew/messagePreview/FilePreviewStore"
import { ConversationMessageAttachment } from "@/types/chat/conversation_message"
import { FilePreviewInfo } from "@/types/chat/preview"

/**
 * File preview service
 */
class MessageFilePreviewService {
	/**
	 * Set preview info
	 * @param info Preview info
	 */
	openPreview(info?: FilePreviewInfo) {
		MessageFilePreviewStore.openPreview(info)
	}

	/**
	 * Clear preview info
	 */
	clearPreviewInfo() {
		MessageFilePreviewStore.clearPreviewInfo()
	}

	/**
	 * Check if can preview
	 * @param info Preview info
	 * @returns Whether can preview
	 */
	canPreview(info?: FilePreviewInfo | ConversationMessageAttachment) {
		if (!info) return false
		const ext = info.file_extension?.toLocaleLowerCase() ?? ""
		return PREVIEW_EXTENSIONS.includes(ext)
	}
}

export default new MessageFilePreviewService()
