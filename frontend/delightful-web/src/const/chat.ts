import { ControlEventMessageType } from "@/types/chat/control_message"
import { ConversationMessageType } from "@/types/chat/conversation_message"

/**
 * @deprecated Use ChatMessageApplyServices.isChatMessage and ControlMessageApplyService.isControlMessageShouldRender instead
 */
export const CONVERSATION_MESSAGE_TYPES: (ConversationMessageType | ControlEventMessageType)[] = [
	ConversationMessageType.Text,
	ConversationMessageType.RichText,
	ConversationMessageType.Markdown,
	ConversationMessageType.MagicSearchCard,
	ConversationMessageType.AggregateAISearchCard,
	ConversationMessageType.Image,
	ConversationMessageType.Files,
	ConversationMessageType.Video,
	ConversationMessageType.Voice,
	ControlEventMessageType.GroupAddMember,
	ControlEventMessageType.GroupDisband,
	ControlEventMessageType.GroupCreate,
	ControlEventMessageType.GroupUsersRemove,
	ControlEventMessageType.GroupUpdate,
	ConversationMessageType.RecordingSummary,
	ConversationMessageType.AiImage,
	ControlEventMessageType.RevokeMessage,
	ConversationMessageType.HDImage,
]

/** Confirmation message for magic forms */
export const DELIGHTFUL_FORM_CONFIRM_MESSAGE = "[DELIGHTFUL_FORM_CONFIRM]"

/** Conversation message types that can be revoked */
export const CONVERSATION_MESSAGE_CAN_REVOKE_TYPES = [
	ConversationMessageType.Text,
	ConversationMessageType.RichText,
	ConversationMessageType.Markdown,
	ConversationMessageType.MagicSearchCard,
	ConversationMessageType.AggregateAISearchCard,
	ConversationMessageType.Files,
	ConversationMessageType.RecordingSummary,
]

/** Conversation message types that can render to text */
export const CONVERSATION_MESSAGE_CAN_RENDER_TO_TEXT_TYPES = [
	...CONVERSATION_MESSAGE_CAN_REVOKE_TYPES,
	ControlEventMessageType.RevokeMessage,
]

export const enum ConversationGroupKey {
	Top = "top",
	Group = "group",
	AI = "ai",
	Single = "single",
	User = "user",
	Other = "Other",
}

// Heartbeat time offset
export const HEARTBEAT_TIME_OFFSET = 2000
