import { SeqResponse } from "@/types/request"
import { DelightfulBroadcastChannel } from "./broadcastChannel"
import { EVENTS } from "./eventFactory/events"
import {
	ConversationMessageSend,
	ConversationMessageStatus,
	ConversationMessage,
	SendStatus,
} from "@/types/chat/conversation_message"
import { FullMessage, ApplyMessageOptions } from "@/types/chat/message"
import type { CMessage } from "@/types/chat"
import { User } from "@/types/user"

const magicBroadcastChannel = new DelightfulBroadcastChannel("magic-chat-broadcast-channel")

export const BroadcastChannelSender = {
	addSendMessage: (renderMessage: FullMessage, message: ConversationMessageSend) => {
		magicBroadcastChannel.send({
			type: EVENTS.ADD_SEND_MESSAGE,
			payload: {
				renderMessage,
				message,
			},
		})
	},

	updateSendMessage: (response: SeqResponse<ConversationMessage>, sendStatus: SendStatus) => {
		magicBroadcastChannel.send({
			type: EVENTS.UPDATE_SEND_MESSAGE,
			payload: {
				response,
				sendStatus,
			},
		})
	},

	updateMessageStatus: (
		messageId: string,
		sendStatus?: SendStatus | undefined,
		seenStatus?: ConversationMessageStatus | undefined,
	) => {
		magicBroadcastChannel.send({
			type: EVENTS.UPDATE_MESSAGE_STATUS,
			payload: {
				messageId,
				sendStatus,
				seenStatus,
			},
		})
	},

	updateMessageId: (tempId: string, messageId: string) => {
		magicBroadcastChannel.send({
			type: EVENTS.UPDATE_MESSAGE_ID,
			payload: {
				tempId,
				messageId,
			},
		})
	},

	applyMessage: (message: SeqResponse<CMessage>, options: ApplyMessageOptions) => {
		console.log("applyMessage ========== ", message, options)
		magicBroadcastChannel.send({
			type: EVENTS.APPLY_MESSAGE,
			payload: {
				message,
				options,
			},
		})
	},

	/**
	 * Switch account
	 * @param targetUserId
	 * @param fallbackUserInfo
	 */
	switchAccount: ({
		magicId,
		magicUserId,
		magicOrganizationCode,
	}: {
		magicId: string
		magicUserId: string
		magicOrganizationCode: string
	}) => {
		magicBroadcastChannel.send({
			type: EVENTS.SWITCH_ACCOUNT,
			payload: {
				magicId,
				magicUserId,
				magicOrganizationCode,
			},
		})
	},

	/**
	 * Switch organization
	 * @param targetUserId
	 * @param targetOrganizationCode
	 * @param fallbackUserInfo
	 */
	switchOrganization: ({
		userInfo,
		magicOrganizationCode,
	}: {
		userInfo: User.UserInfo
		magicOrganizationCode: string
	}) => {
		magicBroadcastChannel.send({
			type: EVENTS.SWITCH_ORGANIZATION,
			payload: {
				userInfo,
				magicOrganizationCode,
			},
		})
	},

	/**
	 * Add account
	 * @param userAccount
	 */
	addAccount: (userAccount: User.UserAccount) => {
		magicBroadcastChannel.send({
			type: EVENTS.ADD_ACCOUNT,
			payload: { userAccount },
		})
	},

	/**
	 * Delete account
	 * @param magicId
	 */
	deleteAccount: (
		magicId?: string,
		{ navigateToLogin = true }: { navigateToLogin?: boolean } = {},
	) => {
		magicBroadcastChannel.send({
			type: EVENTS.DELETE_ACCOUNT,
			payload: { magicId, navigateToLogin },
		})
	},

	/**
	 * Update organization badge count
	 * @param data
	 * @param data.organizationCode Organization code
	 * @param data.count Count
	 * @param data.seqId Sequence id
	 */
	updateOrganizationDot: (data: {
		magicId: string
		organizationCode: string
		count: number
		seqId?: string
	}) => {
		magicBroadcastChannel.send({
			type: EVENTS.UPDATE_ORGANIZATION_DOT,
			payload: data,
		})
	},
}
