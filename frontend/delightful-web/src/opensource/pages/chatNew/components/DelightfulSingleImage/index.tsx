import ImageWrapper from "@/opensource/components/base/DelightfulImagePreview/components/ImageWrapper"
import { useConversationMessage } from "@/opensource/pages/chatNew/components/ChatMessageList/components/MessageItem/components/ConversationMessageProvider/hooks"
import type { ConversationMessageAttachment } from "@/types/chat/conversation_message"
import { createStyles } from "antd-style"
import { memo } from "react"

interface DelightfulSingleImageProps {
	data: ConversationMessageAttachment
}

const useStyles = createStyles(({ css }) => ({
	container: css`
		border: 1px solid #d9d9d9;
		border-radius: 6px;
		overflow: hidden;
		width: fit-content;
		user-select: none;
	`,
}))

const DelightfulSingleImage = memo(({ data }: DelightfulSingleImageProps) => {
	const { styles } = useStyles()
	const { messageId } = useConversationMessage()

	return (
		<ImageWrapper
			containerClassName={styles.container}
			fileId={data.file_id}
			alt={data.file_name}
			messageId={messageId}
			imgExtension={data.file_extension}
			fileSize={data.file_size}
		/>
	)
})

export default DelightfulSingleImage
