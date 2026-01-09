import { genRequestUrl } from "@/utils/http"
import { RequestUrl } from "../../constant"
import type {
	ConversationFromService,
	GroupConversationDetail,
	GroupConversationMember,
	OpenConversationMessage,
} from "@/types/chat/conversation"
import type {
	ConversationMessageSend,
	ChatFileUrlData,
	ConversationMessage,
} from "@/types/chat/conversation_message"
import type { RevokeMessage, GroupCreateMessage } from "@/types/chat/control_message"
import { type CMessage, type MessageReceiveType, EventType } from "@/types/chat"
import { ControlEventMessageType } from "@/types/chat/control_message"
import { IntermediateMessageType } from "@/types/chat/intermediate_message"
import type { LoginResponse, PaginationResponse, SeqResponse } from "@/types/request"
import { encodeSocketIoMessage } from "@/utils/socketio"
import { genAppMessageId, genRequestId } from "@/utils/random"
import type { DeleteTopicMessage, ConversationTopic, CreateTopicMessage } from "@/types/chat/topic"
import type { CreateGroupConversationParams } from "@/types/chat/seen_message"
import type {
	SeqRecord,
	GetDelightfulTopicNameResponse,
	MessageReceiveListResponse,
	GetConversationAiAutoCompletionResponse,
	GetConversationMessagesParams,
} from "@/opensource/apis/modules/chat/types"
import { isString } from "lodash-es"
import type { Bot } from "@/types/bot"
import type { TaskListParams, UserTask, CreateTaskParams, ListData } from "@/types/chat/task"
import type { HttpClient } from "../../core/HttpClient"
import type { ChatWebSocket } from "../../clients/chatWebSocket"
import { fetchPaddingData } from "@/utils/request"
export const generateChatApi = (fetch: HttpClient, socket: ChatWebSocket) => ({
	/**
	 * Login
	 * @param authorization Authorization
	 * @returns
	 */
	login(authorization: string) {
		return socket.apiSend<LoginResponse>(
			encodeSocketIoMessage(
				EventType.Login,
				{
					message: {
						type: "text",
						text: {
							content: "Login",
						},
						app_message_id: genAppMessageId(),
					},
					conversation_id: "",
				},
				0,
				{
					authorization,
				},
			),
		)
	},
	/**
	 * Send message
	 * @param chatMessage Message content
	 * @param penddingIndex Pending send index
	 * @returns
	 */
	chat(
		chatType: EventType,
		chatMessage: {
			message: Omit<ConversationMessageSend["message"], "send_time" | "sender_id">
			conversation_id: string
			refer_message_id?: string
		},
		penddingIndex: number,
	) {
		return socket.apiSend<{ type: "seq"; seq: SeqResponse<CMessage> }>(
			encodeSocketIoMessage(chatType, chatMessage, penddingIndex),
			penddingIndex,
		)
	},

	/**
	 * Create conversation
	 * @param receive_type Receiver type
	 * @param receive_id Receiver id
	 * @returns
	 */
	createConversation(receive_type: MessageReceiveType, receive_id: string) {
		return socket.apiSend<{ type: "seq"; seq: SeqResponse<OpenConversationMessage> }>(
			encodeSocketIoMessage(
				EventType.Control,
				{
					message: {
						type: ControlEventMessageType.OpenConversation,
						[ControlEventMessageType.OpenConversation]: {
							receive_type,
							receive_id,
						},
						app_message_id: genAppMessageId(),
					},
					request_id: genRequestId(),
				},
				0,
			),
		)
	},

	/**
	 * Mark messages as read
	 * @param messageIds Message id list
	 * @returns
	 */
	seenMessages(messageIds: string[]) {
		return socket.apiSend<
			{
				type: "seq"
				seq: SeqResponse<ConversationMessage>
			}[]
		>(
			encodeSocketIoMessage(EventType.Control, {
				message: {
					type: ControlEventMessageType.SeenMessages,
					[ControlEventMessageType.SeenMessages]: {
						refer_message_ids: messageIds,
					},
					app_message_id: genAppMessageId(),
				},
				request_id: genRequestId(),
			}),
		)
	},

	/**
	 * Get multiple conversation details
	 * @param ids Conversation id list
	 * @returns
	 */
	getConversationList(
		ids?: string[],
		options?: {
			status?: number
			limit?: number
			page_token?: string
			is_not_disturb?: 0 | 1
			is_top?: 0 | 1
			is_mark?: 0 | 1
			organization_code?: string
		},
	) {
		return fetch.post<PaginationResponse<ConversationFromService>>(
			genRequestUrl(RequestUrl.getConversationList),
			{ ids, ...options },
		)
	},

	/**
	 * Pull offline messages
	 * @param max_seq_info Maximum sequence number
	 * @returns
	 */
	messagePull(data: { page_token: string }) {
		return fetch.get<PaginationResponse<SeqRecord<CMessage>>>(
			genRequestUrl(RequestUrl.messagePull, {}, data),
		)
	},

	/**
	 * Pull recent offline messages
	 * @param max_seq_info Maximum sequence number
	 * @returns
	 */
	messagePullCurrent(data: { page_token: string }) {
		return fetch.get<PaginationResponse<{ type: "seq"; seq: SeqResponse<CMessage> }>>(
			genRequestUrl(RequestUrl.messagePullCurrent, {}, data),
		)
	},

	/**
	 * Create topic
	 * @param topicName Topic name
	 * @param conversation_id Conversation id
	 * @returns
	 */
	createTopic(topicName: string | undefined, conversation_id: string) {
		return socket.apiSend<{
			type: "seq"
			seq: SeqResponse<CreateTopicMessage>
		}>(
			encodeSocketIoMessage(EventType.Control, {
				message: {
					type: ControlEventMessageType.CreateTopic,
					[ControlEventMessageType.CreateTopic]: {
						name: topicName,
						conversation_id,
					},
				},
			}),
		)
	},

	/**
	 * Update topic
	 * @param topicId Topic id
	 * @param topicName Topic name
	 * @returns
	 */
	updateTopic(conversation_id: string, topicId: string, topicName: string) {
		return socket.apiSend(
			encodeSocketIoMessage(EventType.Control, {
				message: {
					type: ControlEventMessageType.UpdateTopic,
					[ControlEventMessageType.UpdateTopic]: {
						id: topicId,
						name: topicName,
						conversation_id,
					},
				},
			}),
		)
	},

	/**
	 * Delete topic
	 * @param topicId Topic id
	 * @returns
	 */
	deleteTopic(conversation_id: string, topicId: string) {
		return socket.apiSend<{
			type: "seq"
			seq: SeqResponse<DeleteTopicMessage>
		}>(
			encodeSocketIoMessage(EventType.Control, {
				message: {
					type: ControlEventMessageType.DeleteTopic,
					[ControlEventMessageType.DeleteTopic]: {
						id: topicId,
						conversation_id,
					},
				},
			}),
		)
	},

	/**
	 * Get AI summary topic name
	 * @param conversation_id Conversation id
	 * @param id Topic id
	 * @returns
	 */
	getDelightfulTopicName(conversation_id: string, id: string) {
		return fetch.put<GetDelightfulTopicNameResponse>(
			genRequestUrl(RequestUrl.getDelightfulTopicName, {
				conversationId: conversation_id,
				topicId: id,
			}),
		)
	},

	/**
	 * Get topic list
	 * @param conversation_id Conversation id
	 * @returns
	 */
	getTopicList(conversationId: string) {
		return fetch.post<ConversationTopic[]>(
			genRequestUrl(RequestUrl.getTopicList, { conversationId }),
		)
	},

	/**
	 * Get topic messages
	 * @param {string | Array<string>} topic_ids Topic id list
	 * @returns
	 */
	getTopicMessages(topicIds: string | string[]) {
		const topic_ids = isString(topicIds) ? [topicIds] : topicIds

		return fetch.post(genRequestUrl(RequestUrl.getTopicMessages), { topic_ids })
	},

	/**
	 * Start conversation input
	 * @param conversation_id Conversation id
	 * @returns
	 */
	startConversationInput(conversation_id: string) {
		return socket.apiSend(
			encodeSocketIoMessage(EventType.Control, {
				message: {
					type: IntermediateMessageType.StartConversationInput,
					[IntermediateMessageType.StartConversationInput]: {
						conversation_id,
					},
				},
			}),
		)
	},

	/**
	 * End conversation input
	 * @param conversation_id Conversation id
	 * @returns
	 */
	endConversationInput(conversation_id: string) {
		return socket.apiSend(
			encodeSocketIoMessage(EventType.Control, {
				message: {
					type: IntermediateMessageType.EndConversationInput,
					[IntermediateMessageType.EndConversationInput]: {
						conversation_id,
					},
				},
			}),
		)
	},

	/**
	 * Create group chat
	 * @param data Create group chat parameters
	 * @returns
	 */
	createGroupConversation(data: CreateGroupConversationParams) {
		return fetch.post<{ type: "seq"; seq: SeqResponse<GroupCreateMessage> }>(
			genRequestUrl(RequestUrl.createGroupConversation),
			data,
		)
	},

	/**
	 * Get group chat details in batch
	 * @param data Get group chat details parameters
	 * @returns
	 */
	getGroupConversationDetails(data: { group_ids: string[]; page_token?: string }) {
		return fetch.post<PaginationResponse<GroupConversationDetail>>(
			genRequestUrl(RequestUrl.getGroupConversationDetail),
			data,
		)
	},

	/**
	 * Get group chat members
	 * @param conversation_id Conversation id
	 * @returns
	 */
	getGroupConversationMembers(data: { group_id: string; page_token?: string }) {
		return fetch.get<PaginationResponse<GroupConversationMember>>(
			genRequestUrl(
				RequestUrl.getGroupConversationMembers,
				{ id: data.group_id },
				{
					page_token: data.page_token,
				},
			),
		)
	},

	/**
	 * Get message receiver list
	 * @param messageId Message id
	 * @returns
	 */
	getMessageReceiveList(messageId: string) {
		return fetch.get<MessageReceiveListResponse>(
			genRequestUrl(RequestUrl.getMessageReceiveList, { messageId }),
		)
	},

	/**
	 * Add group members
	 * @param data Add group members parameters
	 * @returns
	 */
	addGroupUsers(data: { group_id: string; user_ids?: string[]; department_ids?: string[] }) {
		return fetch.post(genRequestUrl(RequestUrl.addGroupUsers, { id: data.group_id }), data)
	},

	/**
	 * Revoke message
	 * @param messageId Message id
	 * @returns
	 */
	revokeMessage(messageId: string) {
		return socket.apiSend<{
			type: "seq"
			seq: SeqResponse<RevokeMessage>
		}>(
			encodeSocketIoMessage(EventType.Control, {
				message: {
					type: ControlEventMessageType.RevokeMessage,
					[ControlEventMessageType.RevokeMessage]: {
						refer_message_id: messageId,
					},
					app_message_id: genAppMessageId(),
				},
				request_id: genRequestId(),
			}),
		)
	},

	/**
	 * Leave group chat
	 * @param data Leave group chat parameters
	 * @returns
	 */
	leaveGroup(data: { group_id: string }) {
		return fetch.delete(genRequestUrl(RequestUrl.leaveGroup, { id: data.group_id }))
	},

	/**
	 * Kick out from group chat
	 * @param data Kick out from group chat parameters
	 * @returns
	 */
	kickGroupUsers(data: { group_id: string; user_ids: string[] }) {
		return fetch.delete(genRequestUrl(RequestUrl.kickGroupUsers, { id: data.group_id }), data)
	},

	/**
	 * Dismiss group chat
	 * @param data Dismiss group chat parameters
	 * @returns
	 */
	removeGroup(data: { group_id: string }) {
		return fetch.delete(genRequestUrl(RequestUrl.removeGroup, { id: data.group_id }))
	},

	/**
	 * Update group information
	 * @param data Update group information parameters
	 * @returns
	 */
	updateGroupInfo(data: { group_id: string; group_name?: string; group_avatar?: string }) {
		return fetch.put<GroupConversationDetail>(
			genRequestUrl(RequestUrl.updateGroupInfo, { id: data.group_id }),
			data,
		)
	},

	/**
	 * Get chat file information
	 * @param data Get chat file parameters
	 * @returns
	 */
	getChatFileUrls(data: { file_id: string; message_id: string }[]) {
		return fetch.post<Record<string, ChatFileUrlData>>(
			genRequestUrl(RequestUrl.getChatFileUrls),
			data,
		)
	},

	/**
	 * Pin conversation
	 */
	topConversation(conversation_id: string, is_top: 0 | 1) {
		return socket.apiSend(
			encodeSocketIoMessage(EventType.Control, {
				message: {
					type: ControlEventMessageType.TopConversation,
					[ControlEventMessageType.TopConversation]: {
						conversation_id,
						is_top,
					},
					app_message_id: genAppMessageId(),
				},
				request_id: genRequestId(),
			}),
		)
	},

	/**
	 * 隐藏会话
	 */
	hideConversation(conversation_id: string) {
		return socket.apiSend(
			encodeSocketIoMessage(EventType.Control, {
				message: {
					type: ControlEventMessageType.HideConversation,
					[ControlEventMessageType.HideConversation]: {
						conversation_id,
					},
					app_message_id: genAppMessageId(),
				},
				request_id: genRequestId(),
			}),
		)
	},

	/**
	 * Do not disturb messages
	 */
	muteConversation(conversation_id: string, is_not_disturb: 0 | 1) {
		return socket.apiSend(
			encodeSocketIoMessage(EventType.Control, {
				message: {
					type: ControlEventMessageType.MuteConversation,
					[ControlEventMessageType.MuteConversation]: {
						conversation_id,
						is_not_disturb,
					},
					app_message_id: genAppMessageId(),
				},
				request_id: genRequestId(),
			}),
		)
	},

	/**
	 * Get AI summary message
	 * @returns
	 * @param data
	 */
	getConversationAiAutoCompletion(data: {
		conversation_id: string
		topic_id: string
		message: string
	}) {
		return fetch.post<GetConversationAiAutoCompletionResponse>(
			genRequestUrl(RequestUrl.getConversationAiAutoCompletion, {
				conversationId: data.conversation_id,
			}),
			data,
		)
	},

	/**
	 * Get bot information corresponding to AI assistant
	 * @param data Get bot information corresponding to AI assistant parameters
	 * @returns
	 */
	getAiAssistantBotInfo(data: { user_id: string }) {
		return fetch.get<Bot.Detail["botEntity"]>(
			genRequestUrl(RequestUrl.getAiAssistantBotInfo, { userId: data.user_id }),
		)
	},

	/**
	 * Update AI conversation quick command configuration
	 * @param data
	 * @returns
	 */
	updateAiConversationQuickInstructionConfig(data: {
		conversation_id: string
		receive_id: string
		instructs?: Record<string, unknown>
	}) {
		return fetch.post<{ instructs?: Record<string, unknown> }>(
			genRequestUrl(RequestUrl.updateAiConversationQuickInstructionConfig, {
				conversationId: data.conversation_id,
			}),
			data,
		)
	},

	/**
	 * 获取会话消息
	 * @param conversationId
	 * @param data
	 */
	getConversationMessages(conversationId: string, data: GetConversationMessagesParams) {
		return fetch.post<PaginationResponse<SeqRecord<ConversationMessage>>>(
			genRequestUrl(RequestUrl.getConversationMessages, { conversationId }),
			data,
		)
	},

	/**
	 * Pull offline messages
	 * Called when WebSocket reconnects or page changes from invisible to visible
	 */
	pullOfflineMessages() {
		// Get the current latest seq_id
		const lastSeqId = localStorage.getItem("lastSeqId") || "0"
		return this.messagePull({ page_token: lastSeqId })
	},

	/**
	 * Get conversation messages in batch
	 * @param arg0
	 * @returns
	 */
	batchGetConversationMessages(arg0: { conversation_ids?: string[]; limit?: number }) {
		return fetch.post<Record<string, SeqRecord<ConversationMessage>[]>>(
			genRequestUrl(RequestUrl.batchGetConversationMessagesV2),
			arg0,
		)
	},

	/**
	 * Get message by application message ID
	 * @param appMessageId Application message ID
	 * @returns
	 */
	getMessagesByAppMessageId(appMessageId: string) {
		return fetchPaddingData(
			(params) => {
				return fetch.post<PaginationResponse<SeqRecord<ConversationMessage>>>(
					genRequestUrl(RequestUrl.getMessagesByAppMessageId, { appMessageId }),
					params,
				)
			},
			[],
			"",
		)
	},

	/**
	 * 获取用户任务列表
	 * @param data
	 * @returns
	 */
	getTaskList(data: TaskListParams) {
		return fetch.get<ListData<UserTask>>(genRequestUrl(RequestUrl.getTaskList, {}, { ...data }))
	},

	/**
	 * 获取用户任务
	 * @param id
	 * @returns
	 */
	getTask(id: string) {
		return fetch.get<null>(genRequestUrl(RequestUrl.getTask, { id }))
	},

	/**
	 * 更新用户任务
	 * @param id
	 * @param data
	 * @returns
	 */
	updateTask(data: UserTask) {
		return fetch.put<ListData<UserTask>>(
			genRequestUrl(RequestUrl.getTask, { id: data.id }),
			data,
		)
	},

	/**
	 * 删除用户任务
	 * @param id
	 * @returns
	 */
	deleteTask(id: string) {
		return fetch.delete<null>(genRequestUrl(RequestUrl.getTask, { id }))
	},

	/**
	 * Create user task
	 * @param {CreateTaskParams} data
	 * @returns
	 */
	createTask(data: CreateTaskParams) {
		return fetch.post<boolean>(genRequestUrl(RequestUrl.createTask), data)
	},
})
