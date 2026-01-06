import { ReportFileUploadsResponse } from "@/opensource/apis/modules/file"
import { JSONContent } from "@tiptap/core"
import { message } from "antd"

/** APIs */
import { ChatApi } from "@/apis"

/** Services */
import MessageService from "@/opensource/services/chat/message/MessageService"
import ConversationBotDataService from "@/opensource/services/chat/conversation/ConversationBotDataService"

/** Stores */
import ConversationStore from "@/opensource/stores/chatNew/conversation"
import MessageReplyStore from "@/opensource/stores/chatNew/messageUI/Reply"
import { interfaceStore as InterfaceStore } from "@/opensource/stores/interface"
import EditorStore from "@/opensource/stores/chatNew/messageUI/editor"
export interface SendData {
	jsonValue: JSONContent | undefined
	normalValue: string
	files: ReportFileUploadsResponse[]
	onlyTextContent: boolean
	isLongMessage?: boolean
}

/**
 * Editor service
 */
class EditorService {
	/**
	 * Get AI autocompletion
	 * @param text Text
	 * @returns Autocomplete result
	 */
	fetchAiAutoCompletion = async (text: string) => {
		try {
			const res = await ChatApi.getConversationAiAutoCompletion({
				conversation_id: EditorStore.conversationId ?? "",
				topic_id: EditorStore.topicId ?? "",
				message: text,
			})
			const { conversation_id } = res.request_info
			// If the conversation ID differs, return empty string
			if (conversation_id !== EditorStore.conversationId) return ""
			// If the input box is empty, return empty string
			if (!EditorStore.value) return ""
			return res.choices[0].message.content
		} catch (error) {
			console.error(error)
			return Promise.resolve("")
		}
	}

	/**
	 * Send message
	 * @param data Payload
	 */
	send = ({
		jsonValue,
		normalValue,
		onlyTextContent = true,
		files,
		isLongMessage = false,
	}: SendData) => {
		if (!ConversationStore.currentConversation?.id) {
			message.error("Please select a conversation first")
			return
		}
		// Long message
		if (isLongMessage) {
			MessageService.sendLongMessage(
				ConversationStore.currentConversation?.id ?? "",
				{
					jsonValue,
					normalValue,
					onlyTextContent,
					files,
				},
				MessageReplyStore.replyMessageId,
			)
		} else {
			// Short message
			MessageService.sendMessage(
				ConversationStore.currentConversation?.id ?? "",
				{
					jsonValue,
					normalValue,
					onlyTextContent,
					files,
				},
				MessageReplyStore.replyMessageId,
			)
		}

		// Close the text-to-image start page
		if (ConversationBotDataService.startPage && InterfaceStore.isShowStartPage) {
			InterfaceStore.closeStartPage()
		}
	}
}

export default new EditorService()
