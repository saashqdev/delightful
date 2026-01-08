import type { ControlEventMessageType } from "@/types/chat/control_message"
import { ConversationMessageType } from "@/types/chat/conversation_message"

/**
 * Check if message can be copied
 * @param messageType Message type
 * @returns Whether message can be copied
 */
export function canCopy(messageType: ConversationMessageType | ControlEventMessageType) {
	return [
		ConversationMessageType.Text,
		ConversationMessageType.RichText,
		ConversationMessageType.Markdown,
		ConversationMessageType.AggregateAISearchCard,
		ConversationMessageType.AggregateAISearchCardV2,
		ConversationMessageType.AiImage,
		ConversationMessageType.HDImage,
	].includes(messageType as ConversationMessageType)
}

/**
 * Check if message can be replied
 * @param messageType Message type
 * @returns Whether message can be replied
 */
export function canReply(messageType: ConversationMessageType | ControlEventMessageType) {
	return [
		ConversationMessageType.Text,
		ConversationMessageType.RichText,
		ConversationMessageType.Markdown,
		ConversationMessageType.AggregateAISearchCard,
		ConversationMessageType.AggregateAISearchCardV2,
	].includes(messageType as ConversationMessageType)
}

/**
 * Check if message can be edited
 * @param messageType Message type
 * @returns Whether message can be edited
 */
export function canEdit(messageType: ConversationMessageType | ControlEventMessageType) {
	return [
		ConversationMessageType.Text,
		ConversationMessageType.RichText,
		ConversationMessageType.Markdown,
	].includes(messageType as ConversationMessageType)
}
