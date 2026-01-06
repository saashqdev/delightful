import { makeAutoObservable, observable } from "mobx"
import type { FullMessage } from "@/types/chat/message"
import { ConversationMessageStatus, SendStatus } from "@/types/chat/conversation_message"
import Logger from "@/utils/log/Logger"

const console = new Logger("MessageStore", "green")

class MessageStore {
	conversationId: string = ""

	topicId: string = ""

	// 消息队列
	messages: FullMessage[] = []

	messageIdMap: Map<string, string> = new Map() // 临时消息id -> 消息id

	hasMoreHistoryMessage: boolean = true

	seenStatusMap: Map<string, ConversationMessageStatus> = observable.map()

	sendStatusMap: Map<string, SendStatus> = observable.map()

	// 最新一条消息的seq_id
	lastSeqId: string = ""

	// 最早一条消息的seq_id
	firstSeqId: string = ""

	page: number = 1

	pageSize: number = 10

	totalPages: number = 1

	focusMessageId: string | null = null

	constructor() {
		makeAutoObservable(this, {}, { autoBind: true })
	}

	reset() {
		this.conversationId = ""
		this.topicId = ""
		this.messages = []
		this.hasMoreHistoryMessage = true
		this.lastSeqId = ""
		this.firstSeqId = ""
		this.sendStatusMap.clear()
		this.seenStatusMap.clear()
		this.messageIdMap.clear()
	}

	setMessages(conversationId: string, topicId: string, messages: FullMessage[]) {
		this.conversationId = conversationId
		this.topicId = topicId
		this.messages = []
		this.hasMoreHistoryMessage = true
		this.lastSeqId = ""
		this.firstSeqId = ""
		this.sendStatusMap.clear()
		this.seenStatusMap.clear()
		this.messageIdMap.clear()
		console.log("setMessages ====> ", messages)
		if (messages.length) {
			messages.forEach((message: any) => {
				this.updateMessageSendStatus(
					message.message_id || message.temp_id,
					message.send_status,
				)
				this.updateMessageSeenStatus(
					message.message_id || message.temp_id,
					message.seen_status,
				)
			})

			// 确保消息按时间顺序排列，老消息在前
			this.messages = messages.slice().reverse()
			console.log("setMessages messages ====> ", this.messages)
			this.lastSeqId = this.messages[messages.length - 1]?.seq_id || ""
			this.firstSeqId = this.messages[0]?.seq_id || ""
		}
	}

	/**
	 * 添加发送消息
	 * @param message 消息
	 * @param send_status 发送状态
	 * @param seen_status 已读状态
	 */
	addSendMessage(
		message: FullMessage,
		send_status: SendStatus = SendStatus.Pending,
		seen_status: ConversationMessageStatus = ConversationMessageStatus.Unread,
	) {
		// 新消息添加到数组末尾
		this.messages.push(message)
		console.log("addMessage ====> ", this.messages)
		this.updateMessageSendStatus(message.message_id || message?.temp_id || "", send_status)
		this.updateMessageSeenStatus(message.message_id || message?.temp_id || "", seen_status)
		this.updateMessageId(message.message_id || message?.temp_id || "", message.message_id)
		this.lastSeqId = message.seq_id || this.lastSeqId

		if (this.messages.length === 1) {
			this.firstSeqId = message.seq_id || ""
		}
	}

	/**
	 * Add received message
	 * @param message Message
	 */
	addReceivedMessage(message: FullMessage) {
		// If message already exists, do not add
		if (this.messages.find((m) => m.message_id === message.message_id)) {
			console.log("message already exists ====> ", message)
			return
		}
		// If message exists, replace it
		const messageIndex = this.messages.findIndex((m) => m.temp_id === message.temp_id)
		// Empty temp_id might indicate a control message
		if (message.temp_id && messageIndex !== -1) {
			console.log("replace local send message ====> ", message)
			this.messages.splice(messageIndex, 1, { ...message })
			this.updateMessageSeenStatus(message?.temp_id || "", message.seen_status)
			this.updateMessageSeenStatus(message?.message_id || "", message.seen_status)
		} else {
			// 新消息添加到数组末尾
			console.log("add new message ====> ", message)
			this.messages.push(message)
		}
		this.lastSeqId = message.seq_id || this.lastSeqId

		if (this.messages.length === 1) {
			this.firstSeqId = message.seq_id || ""
		}
	}

	updateMessageSendStatus(id: string, status: SendStatus) {
		this.sendStatusMap.set(id, status)
	}

	updateMessageId(tempId: string, messageId: string) {
		this.messageIdMap.set(tempId, messageId)
		const message = this.messages.find((m) => m.temp_id === tempId)
		if (message) {
			message.message_id = messageId
		}
	}

	/**
	 * Get message send status
	 * @param id Message ID
	 * @returns Send status
	 */
	getMessageSendStatus(id: string) {
		return this.sendStatusMap.get(id)
	}

	/**
	 * Get the last message of current conversation
	 * @returns Last message
	 */
	getCurrentConversationLastMessage() {
		if (this.messages.length) {
			return this.messages[this.messages.length - 1]
		}
		return null
	}

	/**
	 * Update message seen status
	 * @param messageIdOrTempId Message ID or temp ID
	 * @param status Seen status
	 */
	updateMessageSeenStatus(messageIdOrTempId: string, status: ConversationMessageStatus) {
		const message = this.messages.find(
			(m) => m.message_id === messageIdOrTempId || m.temp_id === messageIdOrTempId,
		)

		if (message) {
			message.seen_status = status
		}
	}

	/**
	 * Set whether there are more history messages
	 * @param hasMoreHistoryMessage Whether there are more history messages
	 */
	setHasMoreHistoryMessage(hasMoreHistoryMessage: boolean) {
		this.hasMoreHistoryMessage = hasMoreHistoryMessage
	}

	/**
	 * Set pagination config
	 * @param page Current page
	 * @param totalPages Total pages
	 */
	setPageConfig(page: number, totalPages: number) {
		this.page = page
		// this.pageSize = pageSize
		this.totalPages = totalPages
	}

	/**
	 * Add messages
	 * @param messages Messages
	 */
	addMessages(messages: FullMessage[]) {
		// Add history messages to the beginning of array
		this.messages.unshift(...messages.slice().reverse())
		messages.forEach((message) => {
			// History messages have no temp_id, no need to set id mapping
			this.sendStatusMap.set(message.message_id || "", message.send_status)
			this.seenStatusMap.set(message.message_id || "", message.seen_status)
		})
		this.firstSeqId = this.messages[0]?.seq_id || ""
	}

	// Flag message as revoked
	flagMessageRevoked(message_id: string) {
		const message = this.messages.find((m) => m.message_id === message_id)
		if (message) {
			message.revoked = true
			message.message.revoked = true
		}
	}

	/**
	 * Remove message
	 * @param conversationId Conversation ID
	 * @param messageId Message ID
	 * @param topicId Topic ID
	 */
	removeMessage(messageId: string) {
		this.messages = this.messages.filter((message) => message.message_id !== messageId)
	}

	/**
	 * Update message
	 * @param message Message
	 */
	updateMessage(
		messageId: string,
		replace: FullMessage | ((message: FullMessage) => FullMessage),
	): FullMessage | undefined {
		const messageIndex = this.messages.findIndex((m) => m.message_id === messageId)
		if (messageIndex !== -1) {
			this.messages[messageIndex] =
				typeof replace === "function" ? replace(this.messages[messageIndex]) : replace
			return this.messages[messageIndex]
		}
		return undefined
	}

	/**
	 * Update message unread count
	 * @param targetMessageId Target message ID
	 * @param unreadCount Unread count
	 */
	updateMessageUnreadCount(targetMessageId: string, unreadCount: number) {
		const messageIndex = this.messages.findIndex((m) => m.message_id === targetMessageId)
		console.log(
			"updateMessageUnreadCount messageIndex =======> ",
			targetMessageId,
			messageIndex,
		)
		if (messageIndex !== -1) {
			const message = this.messages[messageIndex]
			message.unread_count = unreadCount
			message.message.unread_count = unreadCount
			if (unreadCount === 0) {
				message.seen_status = ConversationMessageStatus.Read
				message.message.status = ConversationMessageStatus.Read
			}
		}
	}

	getMessage(messageId: string): FullMessage | undefined {
		return this.messages.find((m) => m.message_id === messageId)
	}

	setFocusMessageId(messageId: string) {
		this.focusMessageId = messageId
	}

	resetFocusMessageId() {
		this.focusMessageId = null
	}
}

export default new MessageStore()
