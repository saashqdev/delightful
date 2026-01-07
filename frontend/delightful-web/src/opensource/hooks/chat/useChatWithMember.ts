import { RoutePath } from "@/const/routes"
import { MessageReceiveType } from "@/types/chat"
import { useMemoizedFn } from "ahooks"
import { useNavigate } from "@/opensource/hooks/useNavigate"
import conversationService from "@/opensource/services/chat/conversation/ConversationService"

/**
 * Chat with member
 * @param uid Member ID
 * @returns Send message
 */
export const useChatWithMember = () => {
	const navigate = useNavigate()
	const chatWith = useMemoizedFn(
		(
			uid?: string,
			receiveType: MessageReceiveType = MessageReceiveType.User,
			navigateToChat = true,
		) => {
			if (!uid) return Promise.reject(new Error("uid is required"))

			return conversationService.createConversation(receiveType, uid).then((conversation) => {
				if (conversation) {
					conversationService.switchConversation(conversation)
					if (navigateToChat) {
						navigate(RoutePath.Chat)
					}
				}
			})
		},
	)

	return chatWith
}
