/* eslint-disable class-methods-use-this */
import MessageCacheStore from "@/opensource/stores/chatNew/messageCache"
import MessageStore from "@/opensource/stores/chatNew/message"
import { isAppMessageId, genAppMessageId } from "@/utils/random"
import {
	SendStatus,
	ConversationMessageStatus,
	ConversationMessageType,
	AggregateAISearchCardV2Status,
} from "@/types/chat/conversation_message"
import dayjs from "dayjs"
import type {
	AIImagesMessage,
	FileConversationMessage,
	MarkdownConversationMessage,
	RecordSummaryConversationMessage,
	RichTextConversationMessage,
	TextConversationMessage,
	ConversationMessageSend,
	ConversationMessage,
	ImageConversationMessage,
	VideoConversationMessage,
	HDImageMessage,
	AggregateAISearchCardConversationMessageV2,
	AggregateAISearchCardConversationMessage,
} from "@/types/chat/conversation_message"
import { EventType } from "@/types/chat"
import { action, makeObservable, toJS } from "mobx"
import { isUndefined } from "lodash-es"
import { message as AntdMessage } from "antd"
import conversationStore from "@/opensource/stores/chatNew/conversation"
import type { User } from "@/types/user"
import userInfoStore from "@/opensource/stores/userInfo"
import { StreamStatus, type SeqResponse } from "@/types/request"
import type { SeenMessage } from "@/types/chat/seen_message"
import Logger from "@/utils/log/Logger"
import type { FullMessage, MessagePage } from "@/types/chat/message"
import MessageReferStore from "@/opensource/stores/chatNew/messageUI/Reply"
import { ChatApi } from "@/apis"
import MessageDbService from "./MessageDbService"
import MessagePullService from "./MessagePullService"
import MessageSeqIdService from "./MessageSeqIdService"
import MessageCacheService from "./MessageCacheService"
import ConversationBotDataService from "../conversation/ConversationBotDataService"
import ConversationService from "../conversation/ConversationService"
import { getRevokedText, getSlicedText } from "../conversation/utils"
import DotsService from "../dots/DotsService"
import { userStore } from "@/opensource/models/user"
import { UpdateSpec } from "dexie"
import MessageFileService from "./MessageFileService"
import { getStringSizeInBytes } from "@/opensource/utils/size"
import { JSONContent } from "@tiptap/core"
import ChatFileService from "../file/ChatFileService"
import { BroadcastChannelSender } from "@/opensource/broadcastChannel"

import AiSearchApplyService from "./MessageApplyServices/ChatMessageApplyServices/AiSearchApplyService"
import { SeqRecord } from "@/opensource/apis/modules/chat/types"
const console = new Logger("MessageService", "blue")

type SendData =
	| Pick<TextConversationMessage, "type" | "text">
	| Pick<RichTextConversationMessage, "type" | "rich_text">
	| Pick<FileConversationMessage, "type" | "files">
	| Pick<MarkdownConversationMessage, "type" | "markdown">
	| Pick<AIImagesMessage, "type" | "ai_image_card">
	| Pick<RecordSummaryConversationMessage, "type" | "recording_summary">

interface MessageData {
	jsonValue: any
	normalValue: string
	onlyTextContent: boolean
	files: any[]
}

class MessageService {
	private messageDbService: MessageDbService

	private messagePullService: MessagePullService

	private userInfoCache: Map<string, User.UserInfo> = new Map()

	private pendingMessages: Map<string, ConversationMessageSend> = new Map()

	constructor() {
		this.messageDbService = new MessageDbService()
		this.messagePullService = new MessagePullService()

		makeObservable(this, { updateMessage: action.bound })
	}

	/**
	 * Initialize
	 */
	init() {
		this.messagePullService.registerMessagePullLoop()
	}

	/**
	 * Destroy
	 */
	destroy() {
		this.messagePullService.unregisterMessagePullLoop()
		this.reset()
	}

	/**
	 * Reset data
	 */
	reset() {
		// Persist current view to warm cache
		this.changeConversationCache()
		// Reset message data
		MessageStore.reset()
	}

	/**
	 * Initialize messages
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 */
	public async initMessages(conversationId: string, topicId: string = "") {
		console.log("initMessages ====> ", conversationId, topicId)
		if (MessageStore.conversationId === conversationId && MessageStore.topicId === topicId) {
			return
		}

		// Switching conversation or topic
		if (
			MessageStore.conversationId &&
			(conversationId !== MessageStore.conversationId || topicId !== MessageStore.topicId)
		) {
			this.changeConversationCache()
		}

		// Reset to avoid UI inconsistency
		MessageStore.reset()

		console.log("getMessages ====> ", conversationId, topicId)
		let data: MessagePage = {
			page: 1,
			pageSize: 10,
			totalPages: 1,
			messages: [],
		}

		// Prefer loading from cache first
		if (MessageCacheStore.has(conversationId, topicId)) {
			data = MessageCacheStore.getPage(conversationId, topicId, 1, MessageStore.pageSize)
			console.log("messages cache ====> ", data)
		}

		// If no data, fetch from server
		if (!data.messages?.length) {
			// Not in cache, try database
			data = await this.getMessagesByPage(conversationId, topicId, 1, MessageStore.pageSize)
			console.log("messages db ====> ", data)

			// Not in DB, fall back to server
			if (!data.messages?.length) {
				const serverMessages = await this.messagePullService.pullMessagesFromServer({
					conversationId,
					topicId,
					pageSize: MessageStore.pageSize,
					withoutSeqId: true,
				})

				// If remote data exists, process it
				if (serverMessages) {
					const messages = serverMessages.map((item) =>
						this.formatMessage(item, userStore.user.userInfo),
					)

					data = {
						pageSize: MessageStore.pageSize,
						totalPages: Math.ceil(serverMessages.length / MessageStore.pageSize),
						messages,
					}

					console.log("messages server ====> ", data)
				}
			}
		}

		console.log("messages init ====> ", data)

		// This must be the first page
		MessageStore.setPageConfig(1, data.totalPages ?? 1)
		this.checkMessageAttachmentExpired(data.messages)
		MessageStore.setMessages(conversationId, topicId, data.messages ?? [])

		// Collect unread messages and send read receipts
		const unreadMessages = MessageStore.messages.filter(
			(message) =>
				!message.is_self &&
				(message.message.unread_count > 0 ||
					message.seen_status === ConversationMessageStatus.Unread),
		)
		if (unreadMessages.length) {
			this.sendReadReceipt(unreadMessages)
		}

		// Set the render sequence ID for this conversation
		MessageSeqIdService.updateConversationRenderSeqId(
			conversationId,
			data.messages[0]?.seq_id ?? "",
		)

		// Pull offline messages for the conversation
		this.messagePullService.pullMessagesFromServer({
			conversationId,
			topicId,
			pageSize: 100,
			withoutSeqId: true,
		})
	}

	/**
	 * Send read receipt
	 */
	sendReadReceipt(messages: FullMessage[]) {
		console.log("sendReadReceipt ====> ", messages)
		const messageIds = messages.map((message) => message.message_id)
		ChatApi.seenMessages(messageIds).then(({ data: response }) => {
			response?.forEach(({ seq: item }) => {
				// Mark as 0, consider this message as read, will not send again
				this.updateMessageUnreadCount(
					item.conversation_id,
					item.message.topic_id ?? "",
					item.message_id,
					item.seq_id,
					0,
				)
			})
		})
	}

	/**
	 * Put current conversation into cache
	 */
	private changeConversationCache() {
		if (!MessageStore.conversationId || !MessageStore.messages) {
			return
		}

		const messages = toJS(MessageStore.messages).reverse() // Reset order to avoid cache sequence issues

		// Merge in-memory status maps
		messages.forEach((message) => {
			const sendStatus = MessageStore.sendStatusMap.get(message.message_id)
			if (sendStatus) {
				message.send_status = sendStatus
			}
			const seenStatus = MessageStore.seenStatusMap.get(message.message_id)
			if (seenStatus) {
				message.seen_status = seenStatus
			}
		})

		console.log("changeConversationCache messages ======> ", messages)

		// Switched conversation cache uses highest priority
		MessageCacheStore.set(
			MessageStore.conversationId,
			MessageStore.topicId,
			{
				page: 1,
				pageSize: MessageStore.pageSize,
				totalPages: MessageStore.totalPages,
				messages,
			},
			1,
		)
	}

	/**
	 * Format message data
	 * @param message Raw message data
	 * @param currentUserInfo Current user info
	 * @returns Formatted message
	 */
	public formatMessage(
		message: ConversationMessageSend | SeqResponse<ConversationMessage>,
		currentUserInfo: User.UserInfo | null,
	): FullMessage {
		const isUnReceived = isAppMessageId(message.message_id)
		const isSelf = message.message.sender_id === currentUserInfo?.user_id

		let senderInfo = currentUserInfo
		if (!isSelf) {
			// Get sender information
			senderInfo = this.getUserInfo(message.message.sender_id)
		}

		const messageType = message.message.type

		return {
			temp_id: message.message.app_message_id,
			message_id: message.message_id,
			// @ts-ignore
			seq_id: message.seq_id,
			sender_id: message.message.sender_id,
			delightful_id: message.message.sender_id,
			conversation_id: message.conversation_id,
			type: messageType,
			send_time: message.message.send_time.toString(),
			is_self: isSelf,
			is_unreceived: isUnReceived,
			name: senderInfo?.nickname ?? "", // Username
			avatar: senderInfo?.avatar ?? "", // Avatar
			// @ts-ignore
			message: message.message,
			// @ts-ignore
			seen_status: message.message.status,
			send_status: SendStatus.Success,
			// @ts-ignore
			unread_count: message.message.unread_count,
			refer_message_id: message.refer_message_id,
			revoked:
				message.message?.revoked ||
				// @ts-ignore
				message.message?.status === ConversationMessageStatus.Revoked ||
				false,
		}
	}

	/**
	 * Pull more messages from the server
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 * @param loadHistory Whether loading history
	 * @returns Message page
	 */
	private async pullMoreMessages(
		conversationId: string,
		topicId: string = "",
		loadHistory: boolean = true,
	): Promise<MessagePage | null> {
		// Try to request the next page from server
		const messages = await this.messagePullService.pullMessagesFromServer({
			conversationId,
			topicId,
			pageSize: MessageStore.pageSize,
			loadHistory,
		})

		if (messages && messages.length > 0) {
			return {
				pageSize: MessageStore.pageSize,
				totalPages: Math.ceil(messages.length / MessageStore.pageSize),
				messages: messages.map((item) => this.formatMessage(item, userStore.user.userInfo)),
			}
		}

		return null
	}

	/**
	 * Get historical messages
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 * @returns void
	 */
	public async getHistoryMessages(conversationId: string, topicId: string = "") {
		if (!MessageStore.hasMoreHistoryMessage) return
		if (conversationId !== MessageStore.conversationId || topicId !== MessageStore.topicId) {
			return
		}

		console.log("getHistoryMessages start ====> ", conversationId, topicId)

		let data: MessagePage | null = null
		// Prefer cache data first
		if (MessageCacheStore.has(conversationId, topicId)) {
			data = MessageCacheStore.getPage(
				conversationId,
				topicId,
				MessageStore.page + 1,
				MessageStore.pageSize,
			)
		}

		// If cache is empty, try other sources
		if (!data?.messages?.length) {
			// Read from database
			data = await this.getMessagesByPage(
				conversationId,
				topicId,
				MessageStore.page + 1, // next page
				MessageStore.pageSize,
			)

			if (!data?.messages?.length) {
				data = await this.pullMoreMessages(conversationId, topicId)
				console.log("getHistoryMessages by server ====> ", data)
			}
		}

		// If view has changed, don't render; append into cache instead
		if (conversationId !== MessageStore.conversationId || topicId !== MessageStore.topicId) {
			MessageCacheStore.addMessages(conversationId, topicId, {
				page: MessageStore.page + 1,
				pageSize: MessageStore.pageSize,
				totalPages: data?.totalPages ?? 1,
				messages: data?.messages ?? [],
			})
			return
		}
		if (data?.messages?.length) {
			// Find unread messages and send read receipts
			const unreadMessages = data.messages.filter((message) => message.unread_count > 0)
			if (unreadMessages.length) {
				this.sendReadReceipt(unreadMessages)
			}
			this.checkMessageAttachmentExpired(data.messages)
			MessageStore.addMessages(data.messages)
			MessageStore.setPageConfig(MessageStore.page + 1, data.totalPages ?? 1)
		} else {
			MessageStore.setHasMoreHistoryMessage(false)
		}
	}

	/**
	 * Check whether message attachments have expired
	 */
	checkMessageAttachmentExpired(messages: FullMessage[]) {
		const expiredFiles = messages.reduce((prev, cur) => {
			switch (cur.type) {
				case ConversationMessageType.Files:
					;(cur.message as FileConversationMessage).files?.attachments.forEach((file) => {
						if (ChatFileService.checkFileExpired(file.file_id)) {
							prev.push({ message_id: cur.message_id, file_id: file.file_id })
						}
					})
					break
				case ConversationMessageType.RichText:
					;(cur.message as RichTextConversationMessage).rich_text?.attachments?.forEach(
						(file) => {
							if (ChatFileService.checkFileExpired(file.file_id)) {
								prev.push({ message_id: cur.message_id, file_id: file.file_id })
							}
						},
					)
					break
				case ConversationMessageType.Text:
					;(cur.message as TextConversationMessage).text?.attachments?.forEach((file) => {
						if (ChatFileService.checkFileExpired(file.file_id)) {
							prev.push({ message_id: cur.message_id, file_id: file.file_id })
						}
					})
					break
				case ConversationMessageType.Markdown:
					;(cur.message as MarkdownConversationMessage).markdown?.attachments?.forEach(
						(file) => {
							if (ChatFileService.checkFileExpired(file.file_id)) {
								prev.push({ message_id: cur.message_id, file_id: file.file_id })
							}
						},
					)
					break
				case ConversationMessageType.Image:
					const fileId = (cur.message as ImageConversationMessage).image?.file_id
					if (fileId && ChatFileService.checkFileExpired(fileId)) {
						prev.push({ message_id: cur.message_id, file_id: fileId })
					}
					break
				case ConversationMessageType.Video:
					const videoId = (cur.message as VideoConversationMessage).video?.file_id
					if (videoId && ChatFileService.checkFileExpired(videoId)) {
						prev.push({ message_id: cur.message_id, file_id: videoId })
					}
					break
				case ConversationMessageType.AiImage:
					;(cur.message as AIImagesMessage).ai_image_card?.items?.forEach((item) => {
						if (ChatFileService.checkFileExpired(item.file_id)) {
							prev.push({ message_id: cur.message_id, file_id: item.file_id })
						}
					})
					break
				case ConversationMessageType.HDImage:
					const hdImageId = (cur.message as HDImageMessage).image_convert_high_card
						?.new_file_id
					if (hdImageId && ChatFileService.checkFileExpired(hdImageId)) {
						prev.push({ message_id: cur.message_id, file_id: hdImageId })
					}
					break
				default:
					break
			}

			return prev
		}, [] as { message_id: string; file_id: string }[])

		// Fetch URLs for expired files
		if (expiredFiles.length) {
			ChatFileService.fetchFileUrl(expiredFiles)
		}
	}

	/**
	 * Add a pending message to memory and DB
	 * @param message Message
	 */
	public async addPendingMessage(message: ConversationMessageSend) {
		// Already exists, no need to add again
		if (this.pendingMessages.has(message.message_id)) {
			return
		}

		this.pendingMessages.set(message.message_id, message)
		// Persist into database
		setTimeout(() => {
			this.messageDbService.addPendingMessage(message)
		})
	}

	/**
	 * Format and send a message
	 * @param conversationId Conversation ID
	 * @param sendData Payload to send
	 * @param referMessageId Referenced message ID
	 */
	public async formatAndSendMessage(
		conversationId: string,
		sendData: SendData,
		referMessageId = "",
	) {
		const { userInfo } = userStore.user
		const sendId = genAppMessageId()

		const message: ConversationMessageSend = {
			message_id: sendId,
			conversation_id: conversationId,
			status: SendStatus.Pending,
			refer_message_id: referMessageId,
			message: {
				...sendData,
				app_message_id: sendId,
				topic_id: conversationStore.currentConversation?.current_topic_id ?? "",
				sender_id: userInfo?.user_id ?? "",
				send_time: dayjs().unix(),
			},
		}

		const renderMessage: FullMessage = {
			temp_id: sendId,
			message_id: sendId,
			delightful_id: "",
			seq_id: "",
			refer_message_id: "",
			sender_message_id: sendId,
			conversation_id: conversationId,
			type: message.message.type,
			send_time: message.message.send_time.toString(),
			sender_id: userInfo?.user_id ?? "",
			is_self: true,
			is_unreceived: isAppMessageId(sendId),
			name: userInfo?.nickname ?? "",
			avatar: userInfo?.avatar ?? "", // Avatar URL
			message: {
				...message.message,
				unread_count: 1,
				status: ConversationMessageStatus.Unread,
				delightful_message_id: sendId,
			},
			send_status: SendStatus.Pending, // Sending
			seen_status: ConversationMessageStatus.Unread, // Unread
			unread_count: 1,
		}

		// If current conversation matches, append to in-memory list
		const { currentConversation } = conversationStore

		if (currentConversation?.id === conversationId) {
			MessageStore.addSendMessage(renderMessage)
			console.log(
				"Sending message to current conversation",
				conversationId,
				"message ID",
				sendId,
			)
		} else {
			console.log(
				"Sending message to non-current conversation",
				conversationId,
				"current conversation",
				currentConversation?.id,
				"message ID",
				sendId,
			)
		}
		// Send
		console.log("sending message ========> ", message)
		this.send(conversationId, referMessageId, message)
		// Broadcast
		BroadcastChannelSender.addSendMessage(renderMessage, message)
		// Add message into DB
		this.addPendingMessage(message)
	}

	/**
	 * Execute sending
	 */
	private async send(
		conversationId: string,
		referMessageId: string,
		message: ConversationMessageSend,
	) {
		const promise = ChatApi.chat(
			EventType.Chat,
			{
				message: message.message,
				conversation_id: conversationId,
				refer_message_id: referMessageId,
			},
			this.pendingMessages.size + 1,
		)
			.then(({ data: response }) => {
				if (!isUndefined(response)) {
					console.log(
						"Message sent successfully, updating status",
						"response:",
						response,
						"message:",
						message,
					)
					const tempId = message.message_id
					MessageStore.updateMessageSendStatus(tempId, SendStatus.Success)
					// Update pending status in data
					this.updatePendingMessageStatus(tempId, SendStatus.Success)
					this.addReceivedMessage(response.seq as SeqResponse<ConversationMessage>)
					console.log("Sent successfully, update message status", response.seq)
					// Update database
					// this.messageDbService.addMessage(message.conversation_id, response.seq)
					// Broadcast, update status and message
					BroadcastChannelSender.updateSendMessage(
						response.seq as SeqResponse<ConversationMessage>,
						SendStatus.Success,
					)
					// Pull offline messages
					this.messagePullService.pullOfflineMessages(response.seq.seq_id)
				}
			})
			.catch((err) => {
				// Sending failed
				console.log("Send failed ======> ", message)
				MessageStore.updateMessageSendStatus(message.message_id, SendStatus.Failed)
				// Broadcast
				BroadcastChannelSender.updateMessageStatus(message.message_id, SendStatus.Failed)
				this.updatePendingMessageStatus(message.message_id, SendStatus.Failed)
				if (err?.message) AntdMessage.error(err.message)
			})

		console.log("promise =====> ", promise)
	}

	/**
	 * Send recording message
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 * @param referMessageId Referenced message ID
	 * @param messageBase Base message payload
	 */
	sendRecordMessage(conversationId: string, referMessageId: string, messageBase: SendData) {
		this.formatAndSendMessage(conversationId, messageBase, referMessageId)
	}

	/**
	 * Extract plain text from rich JSON content
	 * @param jsonValue Rich text JSON
	 * @returns Concatenated text
	 */
	extractText(jsonValue: JSONContent) {
		if (!jsonValue) {
			return ""
		}
		const text: string[] = []

		const formatContent = (content: JSONContent) => {
			content.forEach((item: JSONContent) => {
				if (item.type === "text") {
					text.push(item.text ?? "")
				}

				if (item.type === "image") {
					try {
						text.push(JSON.stringify(item.attrs))
					} catch (error) {
						text.push(JSON.stringify(item))
					}
				}

				if (Array.isArray(item.content)) {
					formatContent(item.content)
				}
			})
		}

		if (jsonValue.content) {
			formatContent(jsonValue.content)
		}

		return text.join(`\n`)
	}

	/**
	 * Check whether text exceeds size limit
	 * @param text Text
	 * @param limitKB Size limit in KB (default 20KB)
	 * @returns Whether it exceeds the limit
	 */
	isTextSizeOverLimit(text: string, limitKB = 20) {
		return getStringSizeInBytes(text) > limitKB
	}

	public async sendLongMessage(
		conversationId: string,
		data: MessageData,
		referMessageId?: string,
	) {
		const { normalValue, onlyTextContent, jsonValue, files } = data

		let text = ""

		// For pure text, use it directly
		if (onlyTextContent) {
			text = normalValue ?? ""
		} else {
			text = this.extractText(jsonValue)
		}

		const reportRes = await MessageFileService.uploadFileByText(text)

		return this.sendMessage(
			conversationId,
			{
				normalValue: "",
				onlyTextContent: true,
				jsonValue: {
					type: "doc",
					content: [{ type: "paragraph", attrs: { suggestion: "" } }],
				},
				files: [...(reportRes as any[]), ...files],
			},
			referMessageId,
		)
	}

	/**
	 * Send message
	 * @param conversationId Conversation ID
	 * @param data Message data
	 */
	public async sendMessage(conversationId: string, data: MessageData, referMessageId?: string) {
		const isAi = conversationStore.currentConversation?.isAiConversation
		const instructs = isAi ? ConversationBotDataService.genFlowInstructs() : undefined

		// Send message
		// TODO: Detect message type automatically
		const { normalValue, onlyTextContent, jsonValue, files } = data
		if (onlyTextContent) {
			if (!normalValue) {
				// If only files present, send files only
				if (files.length > 0) {
					console.log("Sending file message", files)
					this.formatAndSendMessage(
						conversationId,
						{
							type: ConversationMessageType.Files,
							files: {
								attachments: files.map((file) => ({
									file_id: file.file_id,
								})),
								instructs,
							},
						},
						referMessageId,
					)
				} else {
					throw new Error("empty message")
				}
				return
			}

			// Text-to-image referencing an image
			if (MessageReferStore.replyFile?.fileId && MessageReferStore.replyMessageId) {
				this.formatAndSendMessage(
					conversationId,
					{
						type: ConversationMessageType.Text,
						text: {
							content: normalValue,
							attachments: [{ file_id: MessageReferStore.replyFile?.fileId }],
						},
					},
					MessageReferStore.replyMessageId,
				)
				return
			}

			// Plain text message
			this.formatAndSendMessage(
				conversationId,
				{
					type: ConversationMessageType.Text, // Ensure correct type
					text: {
						content: normalValue,
						attachments: files.map((file) => ({
							file_id: file.file_id,
						})),
						instructs,
					},
				},
				referMessageId,
			)
		} else {
			// Rich text message: images or emojis
			if (!jsonValue) {
				throw new Error("has no jsonValue")
			}

			// If there's only a single image
			if (
				jsonValue.content?.length === 1 &&
				jsonValue.content[0].type === "paragraph" &&
				jsonValue.content[0].content?.length === 1 &&
				jsonValue.content[0].content[0].type === "image"
			) {
				console.log("Sending image message", jsonValue)
				const image = jsonValue.content[0].content[0]
				this.formatAndSendMessage(
					conversationId,
					{
						type: ConversationMessageType.Files,
						files: {
							attachments: [
								{
									file_id: image.attrs?.file_id,
									file_name: image.attrs?.file_name,
									file_extension: image.attrs?.file_extension,
									file_size: image.attrs?.file_size,
								},
							],
							instructs,
						},
					},
					referMessageId,
				)
			} else {
				// Text-to-image referencing an image
				if (MessageReferStore.replyFile?.fileId && MessageReferStore.replyMessageId) {
					this.formatAndSendMessage(
						conversationId,
						{
							type: ConversationMessageType.RichText,
							rich_text: {
								content: JSON.stringify(jsonValue),
								attachments: [{ file_id: MessageReferStore.replyFile?.fileId }],
								instructs,
							},
						},
						MessageReferStore.replyMessageId,
					)
					return
				}

				console.log(
					"Sending rich text message",
					normalValue,
					onlyTextContent,
					jsonValue,
					files,
				)
				this.formatAndSendMessage(
					conversationId,
					{
						type: ConversationMessageType.RichText,
						rich_text: {
							content: JSON.stringify(jsonValue),
							attachments: files.map((item) => ({
								file_id: item.file_id,
							})),
							instructs,
						},
					},
					referMessageId,
				)
			}
		}
	}

	/**
	 * Update status for a pending message
	 * @param messageId Message ID
	 * @param status Message status
	 * @param updateDb Whether to update the DB
	 */
	public async updatePendingMessageStatus(
		messageId: string,
		status: SendStatus,
		updateDb: boolean = true,
	) {
		// Update in-memory pendingMessages map
		if (this.pendingMessages.has(messageId)) {
			this.pendingMessages.set(messageId, {
				...this.pendingMessages.get(messageId),
				status,
			} as ConversationMessageSend)
			console.log(
				"Update message status",
				messageId,
				status,
				this.pendingMessages.get(messageId),
			)
		}

		if (updateDb) {
			// Update database
			setTimeout(() => {
				this.messageDbService.updatePendingMessageStatus(messageId, status)
			})
		}
	}

	/**
	 * Resend a message
	 * @param app_message_id App message ID
	 * @returns Promise
	 */
	public resendMessage(app_message_id: string) {
		const target = this.pendingMessages.get(app_message_id)

		if (!target) {
			throw Error(`resend message not found: ${app_message_id}`)
		}

		return this.send(target.conversation_id, target.refer_message_id ?? "", target)
	}

	/**
	 * Resend all pending messages
	 * @param user_id User ID
	 */
	public resendAllPendingMessages(user_id?: string) {
		// Get all unsent messages for current user
		const messages = Object.values(this.pendingMessages).filter(
			(message) => message.message.sender_id === user_id,
		)

		// Resend in parallel
		messages.forEach((message) => {
			this.resendMessage(message.message_id)
		})
	}

	/**
	 * Get messages by page
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 * @param page Page number (1-based)
	 * @param pageSize Page size
	 * @returns Paged result
	 */
	public async getMessagesByPage(
		conversationId: string,
		topicId: string = "",
		page: number = 1,
		pageSize: number = 10,
		userInfo: User.UserInfo | null = null,
	): Promise<MessagePage> {
		try {
			const res = await this.messageDbService.getMessagesByPage(
				conversationId,
				topicId,
				page,
				pageSize,
			)

			if (!userInfo) {
				userInfo = userStore.user.userInfo
			}

			// Format message data
			const messages = await Promise.all(
				res.messages.map((message: SeqResponse<ConversationMessage>) => {
					return this.checkMessageIntegrity(message)
						.then((message) => {
							return this.formatMessage(message, userInfo)
						})
						.catch((err) => {
							console.error("Message integrity check failed", err, message)
							return this.formatMessage(message, userInfo)
						})
				}),
			)

			return { messages, page: res.page, pageSize: res.pageSize, totalPages: res.totalPages }
		} catch (error) {
			console.error("Database access error, cannot get message", error)
			return { messages: [], page: 1, pageSize: 10, totalPages: 1 }
		}
	}

	/**
	 * Check message integrity
	 * @param message Message
	 * @returns Message
	 */
	private async checkMessageIntegrity(message: SeqResponse<ConversationMessage>) {
		const appMessageId = message.message?.app_message_id

		switch (message.message.type) {
			case ConversationMessageType.AggregateAISearchCardV2:
				const msg = (message as SeqResponse<AggregateAISearchCardConversationMessageV2>)
					.message
				if (
					appMessageId &&
					(msg.aggregate_ai_search_card_v2?.stream_options?.status !== StreamStatus.End ||
						msg.aggregate_ai_search_card_v2?.status !==
							AggregateAISearchCardV2Status.isEnd)
				) {
					const messages = await ChatApi.getMessagesByAppMessageId(appMessageId).then(
						(messages) => {
							return messages.filter(
								(m) =>
									m.seq.message.type ===
									ConversationMessageType.AggregateAISearchCardV2,
							)
						},
					)
					if (messages.length > 0) {
						const msg = messages[0]
							.seq as SeqResponse<AggregateAISearchCardConversationMessageV2>

						msg.message.aggregate_ai_search_card_v2!.status =
							AggregateAISearchCardV2Status.isEnd

						return msg
					}
					return message
				}
				return message
			case ConversationMessageType.Text:
				if (
					appMessageId &&
					// If type is Text with stream_options and status is not End
					(message as SeqResponse<TextConversationMessage>).message.text
						?.stream_options &&
					(message as SeqResponse<TextConversationMessage>).message.text?.stream_options
						?.status !== StreamStatus.End
				) {
					const messages = await ChatApi.getMessagesByAppMessageId(appMessageId).then(
						(messages) => {
							return messages.filter(
								(m) => m.seq.message.type === ConversationMessageType.Text,
							)
						},
					)
					if (messages.length > 0) {
						return messages[0].seq as SeqResponse<TextConversationMessage>
					}
					return message
				}
				return message
			case ConversationMessageType.Markdown:
				if (
					appMessageId &&
					// If type is Markdown with stream_options and status is not End
					(message as SeqResponse<MarkdownConversationMessage>).message.markdown
						?.stream_options &&
					(message as SeqResponse<MarkdownConversationMessage>).message.markdown
						?.stream_options?.status !== StreamStatus.End
				) {
					const messages = await ChatApi.getMessagesByAppMessageId(appMessageId).then(
						(messages) => {
							return messages.filter(
								(m) => m.seq.message.type === ConversationMessageType.Markdown,
							)
						},
					)
					if (messages.length > 0) {
						return messages[0].seq as SeqResponse<MarkdownConversationMessage>
					}
					return message
				}
				return message
			case ConversationMessageType.AggregateAISearchCard:
				if (
					appMessageId &&
					!(message as SeqResponse<AggregateAISearchCardConversationMessage<false>>)
						.message.aggregate_ai_search_card?.finish
				) {
					const messages = await ChatApi.getMessagesByAppMessageId(appMessageId).then(
						(messages) => {
							return messages.filter(
								(m) =>
									m.seq.message.type ===
									ConversationMessageType.AggregateAISearchCard,
							) as SeqRecord<AggregateAISearchCardConversationMessage>[]
						},
					)
					if (messages.length > 0) {
						return (
							AiSearchApplyService.combineAiSearchMessage(
								messages.map((m) => m.seq),
							) ?? message
						)
					}
					return message
				}
				return message
			default:
				return message
		}
	}

	/**
	 * Pull messages on first load
	 * @param delightful_id Delightful ID
	 * @param organization_code Organization code
	 * @returns Messages
	 */
	public pullMessageOnFirstLoad(delightful_id: string, organization_code: string) {
		return this.messagePullService.pullMessageOnFirstLoad(delightful_id, organization_code)
	}

	/**
	 * Get user info, prefer cache, otherwise from userInfoService
	 * @param userId User ID
	 * @returns User info
	 */
	private getUserInfo(userId: string): User.UserInfo | null {
		// 1) Try cache
		const cachedInfo = this.userInfoCache.get(userId)
		if (cachedInfo) {
			return cachedInfo
		}

		// 2) Fetch from userInfoService
		const userInfo = userInfoStore.get(userId)
		if (userInfo) {
			const info: User.UserInfo = {
				delightful_id: userInfo.delightful_id,
				user_id: userInfo.user_id,
				status: userInfo.status,
				nickname: userInfo.nickname,
				avatar: userInfo.avatar_url,
				organization_code: userInfo.organization_code,
			}
			this.userInfoCache.set(userId, info)
			return info
		}

		return null
	}

	/**
	 * Add historical messages into DB
	 * @param conversationId Conversation ID
	 * @param messages Message list
	 */
	addHistoryMessagesToDB(conversationId: string, messages: SeqResponse<ConversationMessage>[]) {
		this.messageDbService.addMessages(conversationId, messages)
	}

	/**
	 * Add a received message
	 * @param message Message
	 */
	addReceivedMessage(message: SeqResponse<ConversationMessage>) {
		// If current conversation/topic matches, add into current list
		if (
			MessageStore.conversationId === message.conversation_id &&
			MessageStore.topicId === message.message.topic_id
		) {
			const fullMessage = this.formatMessage(message, userStore.user.userInfo)

			// Check whether attachments expired
			this.checkMessageAttachmentExpired([fullMessage])

			MessageStore.addReceivedMessage(fullMessage)
			if (!fullMessage.is_self) this.sendReadReceipt([fullMessage])
		} else {
			const fullMessage = this.formatMessage(message, userStore.user.userInfo)
			MessageCacheStore.addOrReplaceMessage(
				message.conversation_id,
				message.message.topic_id ?? "",
				fullMessage,
			)
		}

		// Append to database
		this.messageDbService.addMessage(message.conversation_id, message)
	}

	/**
	 * Flag message as revoked
	 * @param conversationId Conversation ID
	 * @param messageId Message ID
	 * @param topicId Topic ID
	 */
	flagMessageRevoked(conversationId: string, topicId: string, messageId: string) {
		if (MessageStore.conversationId === conversationId && MessageStore.topicId === topicId) {
			// Update in-memory store message
			MessageStore.flagMessageRevoked(messageId)
		}
		// Update cache
		if (MessageCacheService.hasCache(conversationId, topicId)) {
			MessageCacheService.updateMessage(conversationId, topicId, messageId, (message) => {
				return {
					...message,
					revoked: true,
				}
			})
		}

		// If last message was revoked, update preview content
		const conversation = conversationStore.getConversation(conversationId)
		if (conversation && conversation.last_receive_message?.seq_id === messageId) {
			ConversationService.updateLastReceiveMessage(conversationId, {
				...conversation.last_receive_message,
				...getRevokedText(),
			})
		}

		// Fetch the message to decide unread dot updates
		this.getMessageFromDb(conversationId, messageId).then((message) => {
			if (message) {
				console.log("flagMessageRevoked ====> ", message)
				// If it was unread, reduce unread count
				if (message.message.status === ConversationMessageStatus.Unread) {
					DotsService.reduceTopicUnreadDots(
						conversationStore.currentConversation?.user_organization_code ?? "",
						conversationId,
						topicId,
						1,
					)
				}
			}
		})

		this.messageDbService.flagMessageRevoked(conversationId, messageId)
	}

	/**
	 * Get a message
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 * @param messageId Message ID
	 * @returns Message or undefined
	 */
	getMessage(
		conversationId: string,
		topicId: string,
		messageId: string,
	): FullMessage | undefined {
		// Check current in-memory list
		const message = MessageStore.messages.find((m) => m.message_id === messageId)
		if (message) return message

		// Check cache
		if (MessageCacheService.hasCache(conversationId, topicId)) {
			const cacheMessage = MessageCacheService.getMessages(conversationId, topicId)
			if (cacheMessage) {
				const target = cacheMessage.find((m) => m.message_id === messageId)
				if (target) return target
			}
		}

		return undefined
	}

	/**
	 * Get conversation message from DB
	 * @param conversationId Conversation ID
	 * @param messageId Message ID
	 * @returns DB message
	 */
	async getMessageFromDb(
		conversationId: string,
		messageId: string,
	): Promise<SeqResponse<ConversationMessage> | undefined> {
		const table = await this.messageDbService.getMessageTable(conversationId)
		return table
			.get(messageId)
			.then((message: SeqResponse<ConversationMessage>) => {
				return message
			})
			.catch((err: any) => {
				console.error("Failed to get message", err)
				return undefined
			})
	}

	/**
	 * Delete a message
	 * @param conversationId Conversation ID
	 * @param messageId Message ID
	 */
	removeMessage(conversationId: string, messageId: string, topicId: string) {
		if (MessageStore.conversationId === conversationId && MessageStore.topicId === topicId) {
			// Remove from store
			MessageStore.removeMessage(messageId)
		} else if (MessageCacheService.hasCache(conversationId, topicId)) {
			// Remove from cache
			MessageCacheService.removeMessageInCache(conversationId, messageId, topicId)
		}

		// If this was the last message preview, update it
		const lastMessage = conversationStore.getConversationLastMessage(conversationId)
		if (lastMessage && lastMessage.seq_id === messageId) {
			const lastMessage = MessageStore.getCurrentConversationLastMessage()
			if (lastMessage) {
				ConversationService.updateLastReceiveMessage(conversationId, {
					time: lastMessage.message.send_time,
					seq_id: lastMessage.message_id,
					...getSlicedText(lastMessage.message, lastMessage.revoked),
					topic_id: lastMessage.message.topic_id ?? "",
				})
			} else {
				ConversationService.updateLastReceiveMessage(conversationId, null)
			}
		}

		// Soft-delete in database
		this.messageDbService.removeMessage(conversationId, messageId)
	}

	/**
	 * Update a message
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 * @param messageId Message ID
	 * @param replace Replacement or mutator
	 */
	updateMessage(
		conversationId: string,
		topicId: string,
		messageId: string,
		replace: FullMessage | ((message: FullMessage) => FullMessage),
		saveToDb: boolean = false,
	) {
		let updatedMessage: FullMessage | undefined

		if (MessageStore.conversationId === conversationId && MessageStore.topicId === topicId) {
			updatedMessage = MessageStore.updateMessage(messageId, replace)
		}

		if (MessageCacheService.hasCache(conversationId, topicId)) {
			updatedMessage = MessageCacheService.updateMessage(
				conversationId,
				topicId,
				messageId,
				replace,
			)
		}

		if (updatedMessage && saveToDb) {
			this.messageDbService.updateMessage(messageId, conversationId, {
				message: toJS(updatedMessage.message),
			})
			ConversationService.updateLastReceiveMessage(conversationId, {
				time: updatedMessage.message.send_time,
				seq_id: updatedMessage.message_id,
				...getSlicedText(updatedMessage.message, updatedMessage.revoked),
				topic_id: updatedMessage.message.topic_id ?? "",
			})
		}
	}

	/**
	 * Replace a message (store + DB)
	 * @param message Message
	 */
	replaceMessage(message: SeqResponse<ConversationMessage>) {
		const fullMessage = this.formatMessage(message, userStore.user.userInfo)

		this.updateMessage(
			fullMessage.conversation_id,
			fullMessage.message.topic_id ?? "",
			fullMessage.message_id,
			fullMessage,
		)

		// Update database
		this.messageDbService.replaceMessage(message.conversation_id, message)
	}

	/**
	 * Update message read/seen status
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 * @param messageId Message ID
	 * @param seenMessage Seen message payload
	 */
	updateMessageStatus(
		conversationId: string,
		topicId: string,
		messageId: string,
		tempId: string,
		seenMessage: SeqResponse<SeenMessage>,
	) {
		if (MessageStore.conversationId === conversationId && MessageStore.topicId === topicId) {
			MessageStore.updateMessageSeenStatus(messageId, seenMessage.message.status)
			MessageStore.updateMessageSeenStatus(tempId, seenMessage.message.status)
		} else if (MessageCacheService.hasCache(conversationId, topicId)) {
			MessageCacheService.updateMessage(conversationId, topicId, messageId, (message) => {
				return {
					...message,
					seen_status: seenMessage.message.status,
				}
			})
		}

		// Update database
		this.messageDbService.updateMessageStatus(conversationId, messageId, seenMessage)
	}

	/**
	 * Update message unread count
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 * @param messageId Message ID
	 * @param tempId Temp ID
	 * @param unreadCount Unread count
	 */
	updateMessageUnreadCount(
		conversationId: string,
		topicId: string,
		messageId: string,
		tempId: string,
		unreadCount: number,
	) {
		if (MessageStore.conversationId === conversationId && MessageStore.topicId === topicId) {
			MessageStore.updateMessageUnreadCount(messageId, unreadCount)
			MessageStore.updateMessageUnreadCount(tempId, unreadCount)
		}

		if (MessageCacheService.hasCache(conversationId, topicId)) {
			MessageCacheService.updateMessage(conversationId, topicId, messageId, (message) => {
				const status =
					unreadCount > 0 ? message.message.status : ConversationMessageStatus.Read
				return {
					...message,
					message: {
						...message.message,
						status,
						unread_count: unreadCount,
					},
					unread_count: unreadCount,
					seen_status: status,
				}
			})
		}

		// Update database
		this.messageDbService.updateMessageUnreadCount(conversationId, messageId, unreadCount)
	}

	pullOfflineMessages() {
		const organizationSeqId = MessageSeqIdService.getOrganizationRenderSeqId(
			userStore.user.userInfo?.organization_code ?? "",
		)
		return this.messagePullService.pullOfflineMessages(organizationSeqId)
	}

	focusMessage(messageId: string) {
		// If the message is in current conversation
		if (MessageStore.messageIdMap.has(messageId)) {
			MessageStore.resetFocusMessageId()
			setTimeout(() => {
				MessageStore.setFocusMessageId(messageId)
			}, 50)
		}
	}

	/**
	 * Update DB message
	 * @param localMessageId Message ID
	 * @param conversation_id Conversation ID
	 * @param changes Message changes
	 */
	updateDbMessage(
		localMessageId: string,
		conversation_id: string,
		changes: UpdateSpec<SeqResponse<ConversationMessage>>,
	) {
		this.messageDbService.updateMessage(localMessageId, conversation_id, changes)
	}

	/**
	 * Update message ID mapping
	 * @param tempId Temp message ID
	 * @param messsageId Message ID
	 */
	updateMessageId(tempId: string, messsageId: string) {
		MessageStore.updateMessageId(tempId, messsageId)
	}

	/**
	 * Delete all messages of a topic
	 * @param conversationId Conversation ID
	 * @param deleteTopicId Topic ID
	 */
	removeTopicMessages(conversationId: string, deleteTopicId: string) {
		if (
			MessageStore.conversationId === conversationId &&
			MessageStore.topicId === deleteTopicId
		) {
			MessageStore.reset()
		} else if (MessageCacheService.hasCache(conversationId, deleteTopicId)) {
			// Remove messages in cache
			MessageCacheService.removeTopicMessages(conversationId, deleteTopicId)
		}

		// Remove messages in database
		this.messageDbService.removeTopicMessages(conversationId, deleteTopicId)
	}
}

export default new MessageService()
