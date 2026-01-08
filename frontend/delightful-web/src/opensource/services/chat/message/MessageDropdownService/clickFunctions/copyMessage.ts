import MessageDropdownStore from "@/opensource/stores/chatNew/messageUI/Dropdown"
import {
	ConversationMessageType,
	AggregateAISearchCardContent,
	AggregateAISearchCardContentV2,
	AggregateAISearchCardEvent,
} from "@/types/chat/conversation_message"
import type { FullMessage } from "@/types/chat/message"
import { getRichMessagePasteText } from "@/opensource/pages/chatNew/components/ChatSubSider/utils"
import MessageFilePreviewService from "@/opensource/services/chat/message/MessageImagePreview"
import { t } from "i18next"
import { CitationRegexes } from "@/opensource/pages/chatNew/components/ChatMessageList/components/MessageFactory/components/Markdown/EnhanceMarkdown/remarkPlugins/remarkCitation"

/**
 * Select element text
 * @param el Element
 */
function selectElementText(el: Node) {
	// @ts-ignore
	if (document.selection) {
		// Handle IE8 and below
		// @ts-ignore
		const oRange = document.body.createTextRange()
		oRange.moveToElementText(el)
		oRange.select()
	} else {
		const selection = window.getSelection() // get Selection object from currently user selected text
		selection?.removeAllRanges() // unselect any user selected text (if any)
		const range = document.createRange() // create new range object
		range.selectNodeContents(el) // set range to encompass desired element text
		selection?.addRange(range) // add range to Selection object to select it
	}
}

/**
 * Copy selection
 * @param el Element
 */
function copySelection(el: HTMLElement) {
	selectElementText(el)
	let copySuccess // var to check whether execCommand successfully executed
	try {
		// Copy selection
		window.navigator.clipboard.write([
			new ClipboardItem({
				"text/html": new Blob([el.innerHTML], { type: "text/html" }),
				"text/plain": new Blob([el.innerText], { type: "text/plain" }),
			}),
		])
		const selection = window.getSelection() // get Selection object from currently user selected text
		selection?.removeAllRanges() // unselect any user selected text (if any)
		copySuccess = true
	} catch (e) {
		copySuccess = false
	}
	return copySuccess
}

/**
 * Generate AI search card text
 * @param message Message
 * @returns Text
 */
function genAggregateAISearchCardText(message?: AggregateAISearchCardContent) {
	if (!message) return ""

	const result = `${message.llm_response}

## Events

${genMarkdownTableText(message.event)}

## Sources

${message.search["0"]?.map((item, index) => `${index + 1}. [${item.name}](${item.url})`).join("\n")}
`

	return CitationRegexes.reduce((acc, regex) => {
		return acc.replace(regex, (_, $1) => `[${$1}]`)
	}, result)
}

/**
 * Generate AI search card event text
 * @param message Message
 * @returns Text
 */
function genMarkdownTableText(message?: AggregateAISearchCardEvent[]) {
	return `
| ${t("chat.aggregate_ai_search_card.eventName", { ns: "interface" })} | ${t(
		"chat.aggregate_ai_search_card.eventTime",
		{ ns: "interface" },
	)} | ${t("chat.aggregate_ai_search_card.eventDescription", { ns: "interface" })} |
| --- | --- | --- |
${message?.map((item) => `| ${item.name} | ${item.time} | ${item.description} |`).join("\n")}
`
}

/**
 * Generate AI search card V2 text
 * @param message Message
 * @returns Text
 */
function genAggregateAISearchCardV2Text(message?: AggregateAISearchCardContentV2) {
	if (!message) return ""

	const result = `${message.summary?.content}

## Events

${genMarkdownTableText(message.events)}

## Sources
${message.no_repeat_search_details
	?.map((item, index) => `${index + 1}. [${item.name}](${item.url})`)
	.join("\n")}
`

	return CitationRegexes.reduce((acc, regex) => {
		return acc.replace(regex, (_, $1) => `[${$1}]`)
	}, result)
}

/**
 * Get message text
 * @param message Message
 * @returns Message text
 */
function getMessageText(message: FullMessage) {
	switch (message?.message.type) {
		case ConversationMessageType.Text:
			return message.message.text?.content ?? ""
		case ConversationMessageType.RichText:
			return getRichMessagePasteText(message.message.rich_text?.content) ?? ""
		case ConversationMessageType.Markdown:
			return message.message.markdown?.content ?? ""
		case ConversationMessageType.AggregateAISearchCard:
			return genAggregateAISearchCardText(message.message.aggregate_ai_search_card)
		case ConversationMessageType.AggregateAISearchCardV2:
			return genAggregateAISearchCardV2Text(message.message.aggregate_ai_search_card_v2)
		default:
			return ""
	}
}

/**
 * Copy message
 * @param messageId Message ID
 * @param e Event
 */
function copyMessage(messageId: string, e: EventTarget | null) {
	console.log("copyMessage", messageId)
	const message = MessageDropdownStore.currentMessage

	switch (message?.message.type) {
		case ConversationMessageType.RichText:
			const target = document.querySelector(
				`#message_copy_${message?.message_id} .ProseMirror`,
			) as HTMLElement
			if (target) {
				copySelection(target)
			}
			break
		case ConversationMessageType.AiImage:
		case ConversationMessageType.HDImage:
			if (e instanceof HTMLImageElement) {
				MessageFilePreviewService.copy(e)
			}
			break
		case ConversationMessageType.AggregateAISearchCard:
		case ConversationMessageType.AggregateAISearchCardV2:
		case ConversationMessageType.Text:
		case ConversationMessageType.Markdown:
		default:
			navigator.clipboard.writeText(getMessageText(message as FullMessage))
	}
}

export default copyMessage
export { selectElementText, copySelection, getMessageText }
