import { ControlEventMessageType } from "@/types/chat/control_message"
import type { CMessage } from "@/types/chat"
import i18next from "i18next"
import { isNumber } from "radash"
import { ConversationMessageType } from "@/types/chat/conversation_message"
import type {
	ConversationMessage,
	ConversationMessageSend,
} from "@/types/chat/conversation_message"
import type { SeqResponse } from "@/types/request"
import { generateText, generateHTML } from "@tiptap/core"
import StarterKit from "@tiptap/starter-kit"
import { memoize } from "lodash-es"
import DelightfulEmojiNode from "@/opensource/components/base/DelightfulRichEditor/extensions/delightfulEmoji"
import MentionExtension from "@/opensource/components/base/DelightfulRichEditor/extensions/mention"
import DelightfulEmoji from "@/opensource/components/base/DelightfulEmoji"
import { emojiFilePathCache } from "@/opensource/components/base/DelightfulEmojiPanel/cache"
import TextAlign from "@tiptap/extension-text-align"
import TextStyle from "@tiptap/extension-text-style"
import { ImageExtension } from "@/opensource/components/base/DelightfulRichEditor/extensions/image"
import { QuickInstructionNodeChatSubSiderExtension } from "../quick-instruction/extension"
import { extractSourcePlaceholders } from "../ChatMessageList/components/MessageFactory/components/AiSearch/utils"

/**
 * Get the last message
 * @param messages Ordered message list
 * @param targetIndex Target message index
 * @returns
 */
export function getLastMessage(
	messages: ConversationMessage[] | undefined,
	targetIndex?: number,
): ConversationMessage | undefined {
	// if no message, or the message to find doesn't exist, return undefined
	if (!messages || !messages.length || (isNumber(targetIndex) && targetIndex < 0))
		return undefined

	const index = targetIndex ?? messages.length - 1
	const lastMessage = messages[index]
	if (!lastMessage) return undefined
	// if (lastMessage.message.type === MessageType.Empty) {
	// 	// if still empty message, return previous message content
	// 	return getLastMessage(messages, index - 1)
	// }

	return lastMessage
}

export const generateRichText = memoize(
	(content: string, type: "html" | "text" = "text"): string => {
		try {
			switch (type) {
				case "html":
					return generateHTML(JSON.parse(content), [
						StarterKit,
						TextAlign,
						TextStyle,
						DelightfulEmojiNode,
						MentionExtension,
						QuickInstructionNodeChatSubSiderExtension,
						ImageExtension,
					])
				case "text":
					return generateText(JSON.parse(content), [
						StarterKit,
						TextAlign,
						TextStyle,
						DelightfulEmojiNode,
						MentionExtension,
						QuickInstructionNodeChatSubSiderExtension,
						ImageExtension,
					])
				default:
					return ""
			}
		} catch (error) {
			console.error(error)
			return ""
		}
	},
	(content, type) => JSON.stringify({ content, type }),
)

/**
 * Get rich text message HTML content
 * @param content
 * @returns
 */
export function getRichTextHtml(content?: string) {
	if (!content) return ""
	return generateRichText(content, "html")
}

// Cache emoji regular expression
const createDelightfulEmojiRegex = memoize(() => {
	// Create regex to match all delightful emojis
	// Example: match [smile], [laugh] etc. format
	return new RegExp(
		Array.from(emojiFilePathCache.keys())
			.map((item) => `\\[${item}\\]`)
			.join("|"),
		"g",
	)
})

// Use cached regular expression
export const delightfulEmojiRegex = createDelightfulEmojiRegex()

/**
 * Recursively find and replace emoji symbols
 * @param content Text content to process
 */
const findAndReplaceDelightfulEmoji = (content?: string) => {
	if (!content) return content

	const splitArray = extractSourcePlaceholders(content, delightfulEmojiRegex)
	return splitArray.map((item) => {
		// check if it's emoji format
		if (item.match(delightfulEmojiRegex)) {
			// Extract emoji code (remove surrounding brackets)
			const code = item.slice(1, -1)
			// If it's a valid emoji code, return corresponding component
			if (emojiFilePathCache.has(code)) {
				return <DelightfulEmoji key={code} code={code} width={16} />
			}
			return item
		}
		// Not an emoji, return as-is
		return item
	})
}

/**
 * Get rich text message paste text
 * @param content
 * @returns
 */
export const getRichMessagePasteText = (content?: string) => {
	try {
		if (!content) return ""
		return generateText(JSON.parse(content), [
			StarterKit,
			TextAlign,
			TextStyle,
			DelightfulEmojiNode.extend({
				renderText(props) {
					return `[${props.node.attrs.code}]`
				},
			}),
			MentionExtension,
			QuickInstructionNodeChatSubSiderExtension,
			ImageExtension,
		])
	} catch (error) {
		console.error(error)
		return ""
	}
}

/**
 * Get message text content
 * @param message message
 * @returns message text content
 */
export function getMessageText(
	message: SeqResponse<CMessage> | ConversationMessageSend | undefined,
	onlyText = true,
): string | undefined {
	if (!message) return ""
	switch (message.message.type) {
		case ControlEventMessageType.OpenConversation:
			return undefined
		case ConversationMessageType.Text:
			return onlyText
				? message.message.text?.content
				: (findAndReplaceDelightfulEmoji(message.message.text?.content) as string)
		case ConversationMessageType.RichText:
			if (!message.message.rich_text?.content) return ""
			return onlyText
				? generateRichText(message.message.rich_text?.content)
				: (findAndReplaceDelightfulEmoji(
						generateRichText(message.message.rich_text?.content),
				  ) as string)
		case ConversationMessageType.Markdown:
			return onlyText
				? message.message.markdown?.content
				: (findAndReplaceDelightfulEmoji(message.message.markdown?.content) as string)
		case ConversationMessageType.DelightfulSearchCard:
			return i18next.t("chat.subSider.specialCardMessage", { ns: "interface" })
		case ConversationMessageType.Files:
			return i18next.t("chat.subSider.files", { ns: "interface" })
		case ConversationMessageType.AggregateAISearchCard:
			return i18next.t("chat.subSider.aggregateAISearchCard", { ns: "interface" })
		default:
			return ""
	}
}
