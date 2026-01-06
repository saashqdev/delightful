import { MessageReceiveType } from "@/types/chat"

/**
 * Whether it's an AI conversation
 * @param receive_type
 * @returns
 */
export const isAiConversation = (receive_type?: MessageReceiveType) => {
	if (receive_type === MessageReceiveType.Ai) {
		return true
	}
	return false
}
