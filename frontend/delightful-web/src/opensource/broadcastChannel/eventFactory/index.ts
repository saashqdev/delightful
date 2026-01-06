import { ApplyMessageOptions, FullMessage } from "@/types/chat/message"
import { EventFactory } from "./eventFactory"
import { EVENTS } from "./events"
import {
	ConversationMessageSend,
	ConversationMessageStatus,
	SendStatus,
	ConversationMessage,
} from "@/types/chat/conversation_message"
import MessageDispatchService from "@/opensource/services/chat/message/MessageDispatchService"
import { SeqResponse } from "@/types/request"
import { CMessage } from "@/types/chat"
import ConversationDsipatchService from "@/opensource/services/chat/conversation/ConversationDispatchService"
import { CONVERSATION_TOP_STATUS, CONVERSATION_NO_DISTURB_STATUS } from "./constant"
import { User } from "@/types/user"
import UserDispatchService from "@/opensource/services/user/UserDispatchService"
import OrganizationDispatchService from "@/opensource/services/chat/dots/OrganizationDispatchService"
import { userStore } from "@/opensource/models/user"
import DelightfulModal from "@/opensource/components/base/DelightfulModal"
import { BroadcastChannelSender } from ".."
import { toJS } from "mobx"
import { t } from "i18next"

const eventFactory = new EventFactory()

// Register event handlers

/****** Message related ******/

// Add sent message
eventFactory.on(
	EVENTS.ADD_SEND_MESSAGE,
	(data: { renderMessage: FullMessage; message: ConversationMessageSend }) => {
		console.log("ADD_SEND_MESSAGE", data)
		MessageDispatchService.addSendMessage(data.renderMessage, data.message)
	},
)

// Update sent message
eventFactory.on(
	EVENTS.UPDATE_SEND_MESSAGE,
	(data: { response: SeqResponse<ConversationMessage>; sendStatus: SendStatus }) => {
		console.log("UPDATE_SEND_MESSAGE", data)
		MessageDispatchService.updateSendMessage(data.response, data.sendStatus)
	},
)

// Apply message
eventFactory.on(
	EVENTS.APPLY_MESSAGE,
	(data: { message: SeqResponse<CMessage>; options: ApplyMessageOptions }) => {
		console.log("APPLY_MESSAGE ==========", data)
		MessageDispatchService.applyMessage(data.message, data.options)
	},
)

// Update message status
eventFactory.on(
	EVENTS.UPDATE_MESSAGE_STATUS,
	(data: {
		messageId: string
		sendStatus?: SendStatus
		seenStatus?: ConversationMessageStatus
	}) => {
		console.log("UPDATE_MESSAGE_STATUS", data)
		MessageDispatchService.updateMessageStatus(data.messageId, data.sendStatus, data.seenStatus)
	},
)

// Update message ID
eventFactory.on(EVENTS.UPDATE_MESSAGE_ID, (data: { tempId: string; messageId: string }) => {
	console.log("UPDATE_MESSAGE_ID", data)
	MessageDispatchService.updateMessageId(data.tempId, data.messageId)
})

// Delete message
eventFactory.on(EVENTS.DELETE_MESSAGE, (data: { conversationId: string; messageId: string }) => {
	console.log("DELETE_MESSAGE", data)
	// Handle delete message event
})

// Update message
eventFactory.on(EVENTS.UPDATE_MESSAGE, (data) => {
	console.log("UPDATE_MESSAGE", data)
})

// Apply multiple messages
eventFactory.on(EVENTS.APPLY_MESSAGES, (data) => {
	console.log("APPLY_MESSAGES", data)
})

/****** Conversation related ******/

// Set top conversation
eventFactory.on(EVENTS.SET_TOP_CONVERSATION, (data: { conversationId: string }) => {
	console.log("SET_TOP_CONVERSATION", data)
	ConversationDsipatchService.setTopConversationStatus(
		data.conversationId,
		CONVERSATION_TOP_STATUS.TOP,
	)
})

// Cancel top conversation
eventFactory.on(EVENTS.CANCEL_TOP_CONVERSATION, (data: { conversationId: string }) => {
	console.log("CANCEL_TOP_CONVERSATION", data)
	ConversationDsipatchService.setTopConversationStatus(
		data.conversationId,
		CONVERSATION_TOP_STATUS.NOT_TOP,
	)
})

// Message do-not-disturb
eventFactory.on(EVENTS._NO_DISTURB_STATUS, (data: { conversationId: string }) => {
	console.log("_NO_DISTURB_STATUS", data)
	ConversationDsipatchService.setNotDisturbStatus(
		data.conversationId,
		CONVERSATION_NO_DISTURB_STATUS.NO_DISTURB,
	)
})

// Cancel do-not-disturb status
eventFactory.on(EVENTS.CANCEL_SET_NO_DISTURB_STATUS, (data: { conversationId: string }) => {
	console.log("CANCEL_SET_NO_DISTURB_STATUS", data)
	ConversationDsipatchService.setNotDisturbStatus(
		data.conversationId,
		CONVERSATION_NO_DISTURB_STATUS.NORMAL,
	)
})

// Update conversation red dot
eventFactory.on(
	EVENTS.UPDATE_CONVERSATION_DOT,
	(data: { conversationId: string; count: number }) => {
		console.log("UPDATE_CONVERSATION_DOT", data)
		// Handle update conversation red dot event
	},
)

// Update topic red dot
eventFactory.on(
	EVENTS.UPDATE_TOPIC_DOT,
	(data: { conversationId: string; topicId: string; count: number }) => {
		console.log("UPDATE_TOPIC_DOT", data)
		// Handle update topic red dot event
	},
)

/****** User related ******/

// Logout
eventFactory.on(EVENTS.LOGOUT, (data) => {
	console.log("LOGOUT", data)
})

// Execute logout
eventFactory.on(EVENTS.DO_LOGOUT, (data) => {
	console.log("DO_LOGOUT", data)
})

// Login
eventFactory.on(EVENTS.LOGIN, (data) => {
	console.log("LOGIN", data)
})

// Execute login
eventFactory.on(EVENTS.DO_LOGIN, (data) => {
	console.log("DO_LOGIN", data)
})

// // Switch user
// eventFactory.on(EVENTS.SWITCH_USER, (data) => {
// 	console.log("SWITCH_USER", data)
// })

// // Execute switch user
// eventFactory.on(EVENTS.DO_SWITCH_USER, (data) => {
// 	console.log("DO_SWITCH_USER", data)
// })

let switchOrganizationModal: ReturnType<typeof DelightfulModal.confirm> | null = null

// Switch organization
eventFactory.on(
	EVENTS.SWITCH_ORGANIZATION,
	(data: { userInfo: User.UserInfo; delightfulOrganizationCode: string }) => {
		const currentUserInfo = userStore.user.userInfo
		const currentOrganizationCode = userStore.user.organizationCode

		if (
			currentUserInfo &&
			(currentUserInfo?.user_id !== data.userInfo.user_id ||
				currentOrganizationCode !== data.delightfulOrganizationCode)
		) {
			switchOrganizationModal?.destroy()
			switchOrganizationModal = DelightfulModal.confirm({
				title: t("broadcastChannel.organization.title", { ns: "common" }),
				content: t("broadcastChannel.organization.content", { ns: "common" }),
				okText: t("broadcastChannel.organization.confirm", { ns: "common" }),
				cancelText: t("broadcastChannel.organization.cancel", { ns: "common" }),
				centered: true,
				onOk: () => {
					UserDispatchService.switchOrganization({
						userInfo: data.userInfo,
						delightfulOrganizationCode: data.delightfulOrganizationCode,
					})
					switchOrganizationModal?.destroy()
				},
				onCancel: () => {
					BroadcastChannelSender.switchOrganization({
						userInfo: toJS(currentUserInfo),
						delightfulOrganizationCode: toJS(currentOrganizationCode),
					})
					switchOrganizationModal?.destroy()
				},
			})
		} else {
			if (switchOrganizationModal) {
				switchOrganizationModal.destroy()
			}
		}
	},
)

// Execute switch organization
eventFactory.on(EVENTS.DO_SWITCH_ORGANIZATION, (data) => {
	console.log("DO_SWITCH_ORGANIZATION", data)
})

// Update organization red dot
eventFactory.on(
	EVENTS.UPDATE_ORGANIZATION_DOT,
	(data: { delightfulId: string; organizationCode: string; count: number; seqId?: string }) => {
		OrganizationDispatchService.updateOrganizationDot(data)
	},
)

// Execute update organization red dot
eventFactory.on(EVENTS.DO_UPDATE_ORGANIZATION_DOT, (data) => {
	console.log("DO_UPDATE_ORGANIZATION_DOT", data)
})

// Update organization
eventFactory.on(EVENTS.UPDATE_ORGANIZATION, (data) => {
	console.log("UPDATE_ORGANIZATION", data)
})

// Execute update organization
eventFactory.on(EVENTS.DO_UPDATE_ORGANIZATION, (data) => {
	console.log("DO_UPDATE_ORGANIZATION", data)
})

let switchAccountModal: ReturnType<typeof DelightfulModal.confirm> | null = null

// Switch account
eventFactory.on(
	EVENTS.SWITCH_ACCOUNT,
	(data: { delightfulId: string; delightfulUserId: string; delightfulOrganizationCode: string }) => {
		const currentUserInfo = userStore.user.userInfo

		if (currentUserInfo && currentUserInfo.delightful_id !== data.delightfulId) {
			switchAccountModal?.destroy()
			switchAccountModal = DelightfulModal.confirm({
				title: t("broadcastChannel.account.title", { ns: "common" }),
				content: t("broadcastChannel.account.content", { ns: "common" }),
				okText: t("broadcastChannel.account.confirm", { ns: "common" }),
				cancelText: t("broadcastChannel.account.cancel", { ns: "common" }),
				centered: true,
				onOk: () => {
					UserDispatchService.switchAccount({
						delightfulId: data.delightfulId,
						delightfulUserId: data.delightfulUserId,
						delightfulOrganizationCode: data.delightfulOrganizationCode,
					})
					switchAccountModal?.destroy()
				},
				onCancel: () => {
					BroadcastChannelSender.switchAccount({
						delightfulId: currentUserInfo.delightful_id,
						delightfulUserId: currentUserInfo.user_id,
						delightfulOrganizationCode: currentUserInfo.organization_code,
					})
					switchAccountModal?.destroy()
				},
			})
		} else {
			if (switchAccountModal) {
				switchAccountModal.destroy()
			}
		}
	},
)

// Execute switch account
eventFactory.on(EVENTS.DO_SWITCH_ACCOUNT, (data) => {
	console.log("DO_SWITCH_ACCOUNT", data)
})

// Add account
eventFactory.on(EVENTS.ADD_ACCOUNT, (data: { userAccount: User.UserAccount }) => {
	UserDispatchService.addAccount(data)
})

// Execute add account
eventFactory.on(EVENTS.DO_ADD_ACCOUNT, (data) => {
	console.log("DO_ADD_ACCOUNT", data)
})

// Delete account
eventFactory.on(EVENTS.DELETE_ACCOUNT, (data: { delightfulId?: string; navigateToLogin?: boolean }) => {
	UserDispatchService.deleteAccount(data)
})

// Execute delete account
eventFactory.on(EVENTS.DO_DELETE_ACCOUNT, (data) => {
	console.log("DO_DELETE_ACCOUNT", data)
})

export default eventFactory
