import type { FileTypeResult } from "file-type"
import { ConversationMessageAttachment } from "./conversation_message"

/**
 * File preview info
 */
export type FilePreviewInfo = {
	src?: string
	message_id?: string
	conversation_id?: string
} & Partial<ConversationMessageAttachment>

/**
 * Image preview info
 */
export type ImagePreviewInfo = {
	messageId: string | undefined
	conversationId: string | undefined
	fileId?: string
	// Original image file ID
	oldFileId?: string
	// Original image file URL
	oldUrl?: string
	fileName?: string
	fileSize?: number
	index?: number
	url?: string
	ext?:
		| Partial<FileTypeResult>
		| { ext?: "svg"; mime?: "image/svg+xml" }
		| { ext?: string; mime?: string }
	/** Show as a standalone view */
	standalone?: boolean
	/** Use the HD conversion feature */
	useHDImage?: boolean
}
