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

const delightfulBroadcastChannel = new DelightfulBroadcastChannel(
	"delightful-chat-broadcast-channel",
)

export const BroadcastChannelSender = {
	addSendMessage: (renderMessage: FullMessage, message: ConversationMessageSend) => {
		delightfulBroadcastChannel.send({
			type: EVENTS.ADD_SEND_MESSAGE,
			payload: {
				renderMessage,
				message,
			},
		})
	},

	updateSendMessage: (response: SeqResponse<ConversationMessage>, sendStatus: SendStatus) => {
		delightfulBroadcastChannel.send({
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
		delightfulBroadcastChannel.send({
			type: EVENTS.UPDATE_MESSAGE_STATUS,
			payload: {
				messageId,
				sendStatus,
				seenStatus,
			},
		})
	},

	updateMessageId: (tempId: string, messageId: string) => {
		delightfulBroadcastChannel.send({
			type: EVENTS.UPDATE_MESSAGE_ID,
			payload: {
				tempId,
				messageId,
			},
		})
	},

	applyMessage: (message: SeqResponse<CMessage>, options: ApplyMessageOptions) => {
		console.log("applyMessage ========== ", message, options)
		delightfulBroadcastChannel.send({
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
		delightfulId,
		delightfulUserId,
		delightfulOrganizationCode,
	}: {
		delightfulId: string
		delightfulUserId: string
		delightfulOrganizationCode: string
	}) => {
		delightfulBroadcastChannel.send({
			type: EVENTS.SWITCH_ACCOUNT,
			payload: {
				delightfulId,
				delightfulUserId,
				delightfulOrganizationCode,
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
		delightfulOrganizationCode,
	}: {
		userInfo: User.UserInfo
		delightfulOrganizationCode: string
	}) => {
		delightfulBroadcastChannel.send({
			type: EVENTS.SWITCH_ORGANIZATION,
			payload: {
				userInfo,
				delightfulOrganizationCode,
			},
		})
	},

	/**
	 * Add account
	 * @param userAccount
	 */
	addAccount: (userAccount: User.UserAccount) => {
		delightfulBroadcastChannel.send({
			type: EVENTS.ADD_ACCOUNT,
			payload: { userAccount },
		})
	},

	/**
	 * Delete account
	 * @param delightfulId
	 */
	deleteAccount: (
		delightfulId?: string,
		{ navigateToLogin = true }: { navigateToLogin?: boolean } = {},
	) => {
		delightfulBroadcastChannel.send({
			type: EVENTS.DELETE_ACCOUNT,
			payload: { delightfulId, navigateToLogin },
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
		delightfulId: string
		organizationCode: string
		count: number
		seqId?: string
	}) => {
		delightfulBroadcastChannel.send({
			type: EVENTS.UPDATE_ORGANIZATION_DOT,
			payload: data,
		})
	},
}
