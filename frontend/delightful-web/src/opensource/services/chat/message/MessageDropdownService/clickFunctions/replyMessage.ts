import MessageReplyService from "@/opensource/services/chat/message/MessageReplyService"

/**
 * Reply message
 * @param messageId Message ID
 */
function replyMessage(messageId: string) {
	// console.log("replyMessage", messageId)
	MessageReplyService.setReplyMessageId(messageId)
}

export default replyMessage
