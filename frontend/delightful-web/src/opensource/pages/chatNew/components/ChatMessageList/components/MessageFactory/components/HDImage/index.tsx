import { ConversationMessageType } from "@/types/chat/conversation_message"
import type { DelightfulAiImagesProps } from "../AiImageBase"
import AiImageBase from "../AiImageBase"

export default function HDImage(props: Omit<DelightfulAiImagesProps, "type">) {
	return <AiImageBase type={ConversationMessageType.HDImage} {...props} />
}
