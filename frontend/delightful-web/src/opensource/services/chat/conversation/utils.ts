import { ConversationGroupKey } from "@/const/chat"
import type Conversation from "@/opensource/models/chat/conversation"
import { MessageReceiveType } from "@/types/chat"
import type { ConversationFromService } from "@/types/chat/conversation"
import type { ConversationMessage } from "@/types/chat/conversation_message"
import { ConversationMessageType } from "@/types/chat/conversation_message"
import { t } from "i18next"

/**
 * Get conversation group
 * @param item Conversation
 * @returns Conversation group
 */
export const getConversationGroupKey = (item: Conversation | ConversationFromService) => {
	switch (item.receive_type) {
		case MessageReceiveType.User:
			return ConversationGroupKey.User
		case MessageReceiveType.Ai:
			return ConversationGroupKey.AI
		case MessageReceiveType.Group:
			return ConversationGroupKey.Group
		default:
			return ConversationGroupKey.Other
	}
}

/**
 * Get revoked message text
 * @returns Revoked message text
 */
export const getRevokedText = () => {
	return {
		type: ConversationMessageType.Text,
		text: t("chat.messageRevoked", { ns: "interface" }),
	}
}

/**
 * Get message text
 * @param message Message
 * @returns Message text
 */
export const getSlicedText = (message: ConversationMessage, revoked: boolean = false) => {
	if (revoked) {
		return getRevokedText()
	}

	switch (message.type) {
		case ConversationMessageType.Text:
			return {
				type: ConversationMessageType.Text,
				text: (message.text?.content ?? "").slice(0, 50),
			}
		case ConversationMessageType.RichText:
			return {
				type: ConversationMessageType.RichText,
				text: message.rich_text?.content ?? "",
			}
		case ConversationMessageType.Markdown:
			return {
				type: ConversationMessageType.Markdown,
				text: (message.markdown?.content ?? "").slice(0, 50),
			}
		case ConversationMessageType.AggregateAISearchCard:
			return {
				type: ConversationMessageType.AggregateAISearchCard,
				text: (message.aggregate_ai_search_card?.llm_response ?? "").slice(0, 50),
			}
		case ConversationMessageType.AggregateAISearchCardV2:
			return {
				type: ConversationMessageType.AggregateAISearchCardV2,
				text: (message.aggregate_ai_search_card_v2?.summary?.content ?? "").slice(0, 50),
			}
		case ConversationMessageType.DelightfulSearchCard:
			return {
				type: ConversationMessageType.DelightfulSearchCard,
				text: t("chat.messageTextRender.delightful_search_card", { ns: "interface" }),
			}
		case ConversationMessageType.Files:
			return {
				type: ConversationMessageType.Files,
				text: t("chat.messageTextRender.files", { ns: "interface" }),
			}
		case ConversationMessageType.AiImage:
		case ConversationMessageType.HDImage:
			return {
				type: ConversationMessageType.AiImage,
				text: t("chat.messageTextRender.ai_image", { ns: "interface" }),
			}
		default:
			return {
				type: ConversationMessageType.Text,
				text: "",
			}
	}
}
