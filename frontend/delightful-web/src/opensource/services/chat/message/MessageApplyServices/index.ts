import type { SeqResponse } from "@/types/request"
import { MessageReceiveType, type CMessage } from "@/types/chat"
import { bigNumCompare } from "@/utils/string"
import ConversationStore from "@/opensource/stores/chatNew/conversation"
import { ConversationStatus } from "@/types/chat/conversation"
import { ChatApi } from "@/apis"
import ChatMessageApplyService from "./ChatMessageApplyServices"
import ControlMessageApplyService from "./ControlMessageApplyService"
import messageSeqIdService from "../MessageSeqIdService"
import ConversationService from "../../conversation/ConversationService"
import pubsub from "@/utils/pubsub"
import { BroadcastChannelSender } from "@/opensource/broadcastChannel"
import { ApplyMessageOptions } from "@/types/chat/message"
import userInfoService from "@/opensource/services/userInfo"
import groupInfoService from "@/opensource/services/groupInfo"
import { ConversationMessageType } from "@/types/chat/conversation_message"

/**
 * Custom message handler interface.
 */
export interface ICustomMessageHandler {
	// Whether this handler matches the message
	isMatch: (message: SeqResponse<CMessage>) => boolean
	// Apply handling logic for the message
	apply: (message: SeqResponse<CMessage>, options: ApplyMessageOptions) => void
}

/**
 * Message application service.
 * Responsible for handling and applying various message types (control, chat, streaming).
 */
class MessageApplyService {
	// Conversations currently being fetched
	fetchingPromiseMap: Record<string, Promise<void>> = {}

	// Registry of custom message handlers
	private customHandlers: Map<string, ICustomMessageHandler> = new Map()

	/**
	 * Register a custom message handler.
	 * @param id Unique handler identifier.
	 * @param handler Custom message handler.
	 * @param overwrite Overwrite existing handler if present; default true.
	 * @returns Whether registration succeeded.
	 */
	registerCustomHandler(
		id: string,
		handler: ICustomMessageHandler,
		overwrite: boolean = true,
	): boolean {
		if (this.customHandlers.has(id) && !overwrite) {
			console.warn(`处理器 ${id} 已存在，无法重复注册`)
			return false
		}
		this.customHandlers.set(id, handler)
		return true
	}

	/**
	 * Unregister a custom message handler.
	 * @param id Handler ID to remove.
	 * @returns Whether removal succeeded.
	 */
	unregisterCustomHandler(id: string): boolean {
		return this.customHandlers.delete(id)
	}

	/**
	 * Get all registered custom handlers.
	 * @returns All currently registered handlers.
	 */
	getCustomHandlers(): Record<string, ICustomMessageHandler> {
		return Object.fromEntries(this.customHandlers.entries())
	}

	/**
	 * Execute message application.
	 * @param message Message to apply.
	 * @param options Apply options.
	 */
	async doApplyMessage(message: SeqResponse<CMessage>, options: ApplyMessageOptions) {
		const conversation = ConversationStore.getConversation(message.conversation_id)

		console.log("applyMessage =====> conversation ====> ", conversation)
		if (!conversation) {
			// If conversation is missing, fetch the conversation list
			if (!this.fetchingPromiseMap[message.conversation_id]) {
				this.fetchingPromiseMap[message.conversation_id] = ChatApi.getConversationList([
					message.conversation_id,
				]).then(({ items }) => {
					const conversation = items[0]

					delete this.fetchingPromiseMap[message.conversation_id]
					if (items.length === 0) return
					if (conversation.status === ConversationStatus.Normal) {
						ConversationService.addNewConversation(conversation)
						switch (conversation.receive_type) {
							case MessageReceiveType.User:
							case MessageReceiveType.Ai:
								userInfoService.fetchUserInfos([conversation.receive_id], 2)
								break
							case MessageReceiveType.Group:
								groupInfoService.fetchGroupInfos([conversation.receive_id])
								break
							default:
								break
						}
					}
				})
			}

			await this.fetchingPromiseMap[message.conversation_id]
		}

		switch (true) {
			case ControlMessageApplyService.isControlMessage(message):
				ControlMessageApplyService.apply(message, { ...options, isFromOtherTab: true })
				break
			case ChatMessageApplyService.isChatMessage(message):
				ChatMessageApplyService.apply(message, { ...options, isFromOtherTab: true })
				break
			case message?.message?.type === ConversationMessageType.BeDelightful:
				pubsub.publish("be_delightful_new_message", message)
				break
			default:
				// Check custom handlers for a match
				for (const handler of this.customHandlers.values()) {
					if (handler.isMatch(message)) {
						handler.apply(message, { ...options, isFromOtherTab: true })
						return
					}
				}
				break
		}
	}

	/**
	 * Apply a single message.
	 * @param message Message to apply.
	 * @param options Apply options.
	 */
	async applyMessage(
		message: SeqResponse<CMessage>,
		options: ApplyMessageOptions = {
			isHistoryMessage: false,
			sortCheck: true,
			updateLastSeqId: true,
		},
	) {
		const { sortCheck = true } = options

		// Check if this message has already been applied
		if (
			sortCheck &&
			bigNumCompare(
				message.seq_id,
				messageSeqIdService.getOrganizationRenderSeqId(message.organization_code) ?? "",
			) <= 0
		) {
			console.warn("此消息已应用", message.seq_id)
			return
		}

		BroadcastChannelSender.applyMessage(message, options)
		this.doApplyMessage(message, options)
	}
}

export default new MessageApplyService()
