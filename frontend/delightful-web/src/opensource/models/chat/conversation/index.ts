import { isAiConversation } from "@/opensource/stores/chatNew/helpers/conversation"
import { ConversationStatus, type ConversationFromService } from "@/types/chat/conversation"
import { makeAutoObservable } from "mobx"
import { MessageReceiveType } from "@/types/chat"
import type { ConversationObject, LastReceiveMessage } from "./types"
import { cloneDeep } from "lodash-es"

class Conversation {
	/**
	 * Conversation ID
	 */
	id: string

	/**
	 * User ID
	 */
	user_id: string

	/**
	 * Receive type
	 */
	receive_type: number

	/**
	 * Receive ID
	 */
	receive_id: string

	/**
	 * Receive organization code
	 */
	receive_organization_code: string

	/**
	 * Do not disturb
	 */
	is_not_disturb: 0 | 1

	/**
	 * Is pinned
	 */
	is_top: 0 | 1

	/**
	 * Is marked
	 */
	is_mark: number

	/**
	 * Extra information
	 */
	extra: any

	/**
	 * Status
	 */
	status: number

	/**
	 * Last message
	 */
	last_receive_message: LastReceiveMessage | undefined

	/**
	 * Is topic panel open by default
	 */
	topic_default_open: boolean

	/**
	 * Organization code
	 */
	user_organization_code: string

	/**
	 * Current topic ID
	 */
	current_topic_id: string

	/**
	 * Unread message count
	 */
	unread_dots: number

	/**
	 * Topic unread count
	 */
	topic_unread_dots: Map<string, number>

	/**
	 * Conversation input state
	 */
	receive_inputing: boolean

	/**
	 * Last message time
	 */
	last_receive_message_time: number

	constructor(data: ConversationFromService | Conversation | ConversationObject) {
		this.id = data.id
		this.user_id = data.user_id
		this.receive_type = data.receive_type
		this.receive_id = data.receive_id
		this.receive_organization_code = data.receive_organization_code
		this.is_not_disturb = data.is_not_disturb
		this.is_top = data.is_top
		this.is_mark = data.is_mark
		this.extra = data.extra
		this.status = data.status
		this.topic_default_open = isAiConversation(data.receive_type)
		this.user_organization_code = data.user_organization_code
		this.current_topic_id = (data as ConversationObject).current_topic_id
		this.unread_dots = (data as ConversationObject)?.unread_dots ?? 0
		this.topic_unread_dots = (data as ConversationObject)?.topic_unread_dots ?? new Map()
		this.receive_inputing = false
		this.last_receive_message = (
			data as Conversation | ConversationObject
		)?.last_receive_message
		this.last_receive_message_time =
			(data as ConversationObject)?.last_receive_message_time ?? 0
		makeAutoObservable(this, {}, { autoBind: true })
	}

	toObject(): ConversationObject {
		const res = {
			id: this.id,
			user_id: this.user_id,
			receive_type: this.receive_type,
			receive_id: this.receive_id,
			receive_organization_code: this.receive_organization_code,
			is_not_disturb: this.is_not_disturb,
			is_top: this.is_top,
			is_mark: this.is_mark,
			extra: this.extra,
			status: this.status,
			last_receive_message: this.last_receive_message,
			topic_default_open: this.topic_default_open,
			user_organization_code: this.user_organization_code,
			current_topic_id: this.current_topic_id,
			unread_dots: this.unread_dots,
			topic_unread_dots: new Map(this.topic_unread_dots),
			receive_inputing: this.receive_inputing,
			last_receive_message_time: this.last_receive_message_time,
		}

		return cloneDeep(res)
	}

	/**
	 * Is AI conversation
	 */
	get isAiConversation() {
		return isAiConversation(this.receive_type)
	}

	/**
	 * Is group conversation
	 */
	get isGroupConversation() {
		return this.receive_type === MessageReceiveType.Group
	}

	/**
	 * Unpin
	 */
	unTop() {
		this.is_top = 0
	}

	/**
	 * Pin
	 */
	top() {
		this.is_top = 1
	}

	/**
	 * Set topic panel default open state
	 * @param open Whether to open
	 */
	setTopicDefaultOpen(open: boolean) {
		this.topic_default_open = open
	}

	/**
	 * Compare message time
	 * @param messageTime Message time
	 * @returns Maximum time
	 */
	getLastMessageTime(messageTime: number) {
		return Math.max(messageTime, this.last_receive_message_time)
	}

	/**
	 * Set last received message
	 * @param message Last received message
	 */
	setLastReceiveMessageAndLastReceiveTime(message: LastReceiveMessage | undefined) {
		this.last_receive_message = message

		console.trace("setLastReceiveMessageAndLastReceiveTime", this.id, message)

		this.last_receive_message_time = this.getLastMessageTime(message?.time ?? 0)
	}

	/**
	 * Set current topic ID
	 * @param topicId Topic ID
	 */
	setCurrentTopicId(topicId: string) {
		this.current_topic_id = topicId
	}

	/**
	 * Set conversation do not disturb
	 * @param isNotDisturb Whether do not disturb
	 */
	setNotDisturb(isNotDisturb: 0 | 1) {
		this.is_not_disturb = isNotDisturb
	}

	/**
	 * Hide conversation
	 */
	hidden() {
		this.status = ConversationStatus.Hidden
	}

	/**
	 * Add unread message count
	 * @param dots Unread message count
	 */
	addUnreadDots(dots: number = 1) {
		this.unread_dots += dots
	}

	/**
	 * Reduce unread message count
	 * @param dots Unread message count
	 */
	reduceUnreadDots(dots: number = 1) {
		this.unread_dots = Math.max(this.unread_dots - dots, 0)
	}

	/**
	 * Reset unread message count
	 */
	resetUnreadDots() {
		this.unread_dots = 0
	}

	/**
	 * Set conversation status
	 * @param status Status
	 */
	setStatus(status: number) {
		this.status = status
	}

	/**
	 * Add topic unread count
	 * @param topicId Topic ID
	 * @param dots Unread count
	 */
	addTopicUnreadDots(topicId: string, dots: number = 1) {
		this.topic_unread_dots.set(topicId, (this.topic_unread_dots.get(topicId) || 0) + dots)
	}

	/**
	 * Reduce topic unread count
	 * @param topicId Topic ID
	 * @param dots Unread count
	 */
	reduceTopicUnreadDots(topicId: string, dots: number = 1) {
		this.topic_unread_dots.set(
			topicId,
			Math.max((this.topic_unread_dots.get(topicId) || 0) - dots, 0),
		)
	}

	/**
	 * Reset topic unread count
	 */
	resetTopicUnreadDots(topicId: string, num: number = 0) {
		this.topic_unread_dots.set(topicId, num)
	}

	/**
	 * Reset all unread counts
	 */
	resetAllTopicUnreadDots() {
		this.topic_unread_dots.clear()
	}

	/**
	 * Set conversation input state
	 * @param inputing Input state
	 */
	setReceiveInputing(inputing: boolean) {
		this.receive_inputing = inputing
	}

	/**
	 * Update conversation data
	 * @param data Conversation data
	 */
	updateFromRemote(data: ConversationFromService) {
		this.id = data.id
		this.user_id = data.user_id
		this.receive_type = data.receive_type
		this.receive_id = data.receive_id
		this.receive_organization_code = data.receive_organization_code
		this.is_not_disturb = data.is_not_disturb
		this.is_top = data.is_top
		this.is_mark = data.is_mark
		this.extra = data.extra
		this.status = data.status
		this.user_organization_code = data.user_organization_code
	}
}

export default Conversation
