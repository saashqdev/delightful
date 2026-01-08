import MessageService from "@/opensource/services/chat/message/MessageService"
import MessageDropdownStore from "@/opensource/stores/chatNew/messageUI/Dropdown"
import { ChatApi } from "@/apis"

/**
 * Revoke message
 * @param messageId Message ID
 */
function revokeMessage(messageId: string) {
	console.log("revokeMessage", messageId)
	const message = MessageDropdownStore.currentMessage
	MessageService.flagMessageRevoked(
		message?.conversation_id ?? "",
		message?.message?.topic_id ?? "",
		messageId,
	)
	ChatApi.revokeMessage(messageId).then((res) => {
		console.log("Revoke message success", res)
		// Update database
	})
}

export default revokeMessage
