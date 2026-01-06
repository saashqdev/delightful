import type { CMessage } from "@/types/chat"
import { MessageReceiveType } from "@/types/chat"
import { ControlEventMessageType } from "@/types/chat/control_message"
import type { SeqResponse } from "@/types/request"

// Import conversation/topic/user services
import ConversationService from "@/opensource/services/chat/conversation/ConversationService"
import chatTopicService from "@/opensource/services/chat/topic"
import userInfoService from "@/opensource/services/userInfo"

// Import state stores
import conversationStore from "@/opensource/stores/chatNew/conversation"
import groupInfoStore from "@/opensource/stores/groupInfo"
import type { ConversationMessage } from "@/types/chat/conversation_message"
import type {
	AddFriendSuccessMessage,
	GroupAddMemberMessage,
	GroupCreateMessage,
	GroupDisbandMessage,
	GroupUpdateMessage,
	GroupUsersRemoveMessage,
	MuteConversationMessage,
	RevokeMessage,
	TopConversationMessage,
} from "@/types/chat/control_message"
import type { SeenMessage } from "@/types/chat/seen_message"
import type { CreateTopicMessage, UpdateTopicMessage, DeleteTopicMessage } from "@/types/chat/topic"
import { ConversationStatus } from "@/types/chat/conversation"
import groupInfoService from "@/opensource/services/groupInfo"
import MessageService from "../MessageService"
import { userStore } from "@/opensource/models/user"
import { ChatApi } from "@/apis"
import { ApplyMessageOptions } from "@/types/chat/message"

/**
 * Control message apply service
 * Handles control-type messages and applies related business logic
 */
class ControlMessageApplyService {
	/**
	 * Determine whether this is a control message
	 * @param message Message object
	 * @returns Whether it is a control message
	 */
	isControlMessage(message: SeqResponse<CMessage>) {
		return [
			ControlEventMessageType.OpenConversation,
			ControlEventMessageType.CreateConversation,
			ControlEventMessageType.SeenMessages,
			ControlEventMessageType.MuteConversation,
			ControlEventMessageType.TopConversation,
			ControlEventMessageType.HideConversation,
			ControlEventMessageType.CreateTopic,
			ControlEventMessageType.UpdateTopic,
			ControlEventMessageType.DeleteTopic,
			ControlEventMessageType.RevokeMessage,
			ControlEventMessageType.SetConversationTopic,
			ControlEventMessageType.GroupCreate,
			ControlEventMessageType.GroupAddMember,
			ControlEventMessageType.GroupUpdate,
			ControlEventMessageType.GroupUsersRemove,
			ControlEventMessageType.GroupDisband,
			ControlEventMessageType.AddFriendSuccess,
		].includes(message.message.type as ControlEventMessageType)
	}

	/**
	 * Determine whether a control message needs rendering
	 * @param message Message object
	 * @returns Whether it should render
	 */
	isControlMessageShouldRender(message: SeqResponse<CMessage>) {
		return [
			ControlEventMessageType.GroupCreate,
			ControlEventMessageType.GroupAddMember,
			ControlEventMessageType.GroupUpdate,
			ControlEventMessageType.GroupUsersRemove,
			ControlEventMessageType.GroupDisband,
		].includes(message.message.type as ControlEventMessageType)
	}

	/**
	 * Apply control-type message
	 * @param message Message to apply
	 * @param options Apply options
	 */
	apply(message: SeqResponse<CMessage>, options: ApplyMessageOptions = {}) {
		const { isHistoryMessage = false } = options
		console.log("message type", message.message.type)

		switch (message.message.type) {
			case ControlEventMessageType.OpenConversation:
			case ControlEventMessageType.CreateConversation:
				this.applyOpenConversationMessage(message)
				break
			case ControlEventMessageType.SeenMessages:
				this.applySeenMessage(message as SeqResponse<SeenMessage>)
				break
			case ControlEventMessageType.MuteConversation:
				this.applyMuteConversationMessage(message as SeqResponse<MuteConversationMessage>)
				break
			case ControlEventMessageType.TopConversation:
				this.applyTopConversationMessage(message as SeqResponse<TopConversationMessage>)
				break
			case ControlEventMessageType.HideConversation:
				this.applyHideConversationMessage(message, isHistoryMessage)
				break
			case ControlEventMessageType.CreateTopic:
				this.applyCreateTopicMessage(message as SeqResponse<CreateTopicMessage>)
				break
			case ControlEventMessageType.UpdateTopic:
				this.applyUpdateTopicMessage(message as SeqResponse<UpdateTopicMessage>)
				break
			case ControlEventMessageType.DeleteTopic:
				this.applyDeleteTopicMessage(message as SeqResponse<DeleteTopicMessage>)
				break
			case ControlEventMessageType.GroupCreate:
			case ControlEventMessageType.GroupAddMember:
			case ControlEventMessageType.GroupUpdate:
				this.applyGroupBasicMessage(
					message as SeqResponse<
						GroupAddMemberMessage | GroupCreateMessage | GroupUpdateMessage
					>,
				)
				break
			case ControlEventMessageType.GroupUsersRemove:
				this.applyGroupUsersRemoveMessage(message as SeqResponse<GroupUsersRemoveMessage>)
				break
			case ControlEventMessageType.GroupDisband:
				this.applyGroupDisbandMessage(message as SeqResponse<GroupDisbandMessage>)
				break
			case ControlEventMessageType.RevokeMessage:
				this.applyRevokeMessage(message as SeqResponse<RevokeMessage>)
				break
			case ControlEventMessageType.AddFriendSuccess:
				this.applyAddFriendSuccessMessage(message as SeqResponse<AddFriendSuccessMessage>)
				break
			default:
				break
		}
	}

	/**
	 * Apply add-friend-success message
	 * @param message Message payload
	 */
	applyAddFriendSuccessMessage(message: SeqResponse<AddFriendSuccessMessage>) {
		const {
			add_friend_success: { receive_id, receive_type },
		} = message.message
		switch (receive_type) {
			case MessageReceiveType.Ai:
			case MessageReceiveType.User:
				userInfoService.fetchUserInfos([receive_id], 2)
				break
			case MessageReceiveType.Group:
				groupInfoService.fetchGroupInfos([receive_id])
				break
			default:
				break
		}
	}

	/**
	 * Apply basic group messages
	 * @param message Group message
	 */
	applyGroupBasicMessage(message: SeqResponse<ConversationMessage>) {
		MessageService.addReceivedMessage(message)

		switch (message.message.type) {
			case ControlEventMessageType.GroupUpdate:
				const groupAddMessage = message as SeqResponse<GroupUpdateMessage>
				groupInfoService.updateGroupInfo(
					groupAddMessage.message.group_update.group_id,
					groupAddMessage.message.group_update,
				)
				break
			case ControlEventMessageType.GroupAddMember:
				groupInfoService.fetchGroupMembers(message.message.group_users_add.group_id)
				break
			default:
				break
		}
	}

	/**
	 * Check whether the current user left the group
	 * @param message Group remove-users message
	 * @param userId User ID
	 * @returns Whether self left
	 */
	isSelfLeaveGroup(message: GroupUsersRemoveMessage, userId?: string) {
		return (
			userId &&
			message.group_users_remove.user_ids.length &&
			message.group_users_remove.user_ids.includes(userId ?? "")
		)
	}

	/**
	 * Apply group remove-users message
	 * @param message Group remove-users message
	 */
	async applyGroupUsersRemoveMessage(message: SeqResponse<GroupUsersRemoveMessage>) {
		const userId = userStore.user.userInfo?.user_id
		if (this.isSelfLeaveGroup(message.message, userId)) {
			// Self left (handling may be improved)
			// Remove the conversation directly
			ConversationService.deleteConversation(message.conversation_id)
		}
		// Fetch user info first to ensure it is loaded
		await userInfoService.fetchUserInfos(message.message.group_users_remove.user_ids ?? [], 2)
		MessageService.addReceivedMessage(message)

		// If current conversation is the target group, remove members locally
		const currentConversation = conversationStore.currentConversation
		if (
			currentConversation?.receive_type === MessageReceiveType.Group &&
			currentConversation?.receive_id === message.message.group_users_remove.group_id
		) {
			groupInfoStore.removeGroupMembers(message.message.group_users_remove.user_ids ?? [])
		}
	}

	/**
	 * Apply group disband message
	 * @param message Disband message
	 */
	applyGroupDisbandMessage(message: SeqResponse<ConversationMessage>) {
		MessageService.addReceivedMessage(message)

		ConversationService.updateConversationStatus(
			message.conversation_id,
			ConversationStatus.Deleted,
		)

		// If current conversation is this group, disband it locally
		if (conversationStore.currentConversation?.id === message.conversation_id) {
			ConversationService.groupConversationDisband(message.conversation_id)
		}
	}

	/**
	 * Apply revoke message
	 * @param message Revoke message
	 */
	applyRevokeMessage(message: SeqResponse<RevokeMessage>) {
		// Mark the referenced message as revoked
		MessageService.flagMessageRevoked(
			message.conversation_id,
			message.message.topic_id || "",
			message.message.revoke_message.refer_message_id,
		)
	}

	/**
	 * Apply open/create conversation message
	 * @param message Message
	 */
	applyOpenConversationMessage(message: SeqResponse<CMessage>) {
		console.log("applyOpenConversationMessage =====> ", message)
		if (
			message.message.type === ControlEventMessageType.OpenConversation ||
			message.message.type === ControlEventMessageType.CreateConversation
		) {
			const conversation = conversationStore.conversations[message.conversation_id]
			// If the conversation already exists, skip
			if (conversation) return

			// Fetch conversation detail
			ChatApi.getConversationList([message.conversation_id]).then(({ items }) => {
				if (items.length === 0) return
				console.log("applyOpenConversationMessage items", items)
				// If 1:1 chat, fetch user info; if group, fetch group info
				if (items[0].receive_type === MessageReceiveType.User) {
					userInfoService.fetchUserInfos([items[0].receive_id], 2)
				} else if (items[0].receive_type === MessageReceiveType.Group) {
					groupInfoService.fetchGroupInfos([items[0].receive_id])
				}
				ConversationService.addNewConversation(items[0])
			})
		}
	}

	/**
	 * Apply seen/read message
	 * @param seenMessage Seen message payload
	 */
	applySeenMessage(seenMessage: SeqResponse<SeenMessage>) {
		const targetMessageId = seenMessage.message.seen_messages.refer_message_ids[0]
		const conversationId = seenMessage.conversation_id
		const unreadCount = seenMessage.message.unread_count
		const topicId = seenMessage.message.topic_id ?? ""
		const tempId = seenMessage.message.app_message_id ?? ""

		if (tempId) {
			MessageService.updateMessageId(tempId, targetMessageId)
		}

		// 1) First, update view state
		if (unreadCount === 0) {
			MessageService.updateMessageStatus(
				conversationId,
				topicId,
				targetMessageId,
				tempId,
				seenMessage,
			)
		}
		// 2) Update unread count
		MessageService.updateMessageUnreadCount(
			conversationId,
			topicId,
			targetMessageId,
			tempId,
			unreadCount,
		)
	}

	/**
	 * Apply mute-conversation message
	 * @param message Mute message payload
	 */
	applyMuteConversationMessage(message: SeqResponse<MuteConversationMessage>) {
		ConversationService.notDisturbConversation(
			message.conversation_id,
			message.message.mute_conversation.is_not_disturb,
		)
	}

	/**
	 * Apply pin/top-conversation message
	 * @param message Top message payload
	 */
	applyTopConversationMessage(message: SeqResponse<TopConversationMessage>) {
		ConversationService.updateTopStatus(
			message.conversation_id,
			message.message.top_conversation.is_top,
		)
	}

	/**
	 * Apply hide-conversation message
	 * @param message Message payload
	 * @param isHistoryMessage Whether it is a historical message
	 */
	applyHideConversationMessage(message: SeqResponse<CMessage>, isHistoryMessage: boolean) {
		// Ignore hide operation if this is a historical message
		if (isHistoryMessage) {
			return
		}
		ConversationService.deleteConversation(message.conversation_id)
	}

	/**
	 * Apply create-topic message
	 * @param message Create-topic payload
	 */
	applyCreateTopicMessage(message: SeqResponse<CreateTopicMessage>) {
		chatTopicService.applyCreateTopicMessage(message)
	}

	/**
	 * Apply update-topic message
	 * @param message Update-topic payload
	 */
	applyUpdateTopicMessage(message: SeqResponse<UpdateTopicMessage>) {
		chatTopicService.applyUpdateTopicMessage(message)
	}

	/**
	 * Apply delete-topic message
	 * @param message Delete-topic payload
	 */
	applyDeleteTopicMessage(message: SeqResponse<DeleteTopicMessage>) {
		chatTopicService.applyDeleteTopicMessage(message)
	}
}

export default new ControlMessageApplyService()
