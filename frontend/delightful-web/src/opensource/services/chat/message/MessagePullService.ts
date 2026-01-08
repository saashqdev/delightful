/* eslint-disable class-methods-use-this */
import { fetchPaddingData } from "@/utils/request"
import { bigNumCompare } from "@/utils/string"
import type { SeqResponse } from "@/types/request"
import { MessageReceiveType, type CMessage } from "@/types/chat"
import { ConversationStatus } from "@/types/chat/conversation"
import { groupBy, last } from "lodash-es"
import { getDataContext } from "@/opensource/providers/DataContextProvider/hooks"
import type {
	AggregateAISearchCardConversationMessage,
	ConversationMessage,
} from "@/types/chat/conversation_message"
import conversationService from "@/opensource/services/chat/conversation/ConversationService"
import MessageStore from "@/opensource/stores/chatNew/message"
import { ChatApi } from "@/apis"
import MessageSeqIdService from "./MessageSeqIdService"
import MessageApplyServices from "./MessageApplyServices"
import ChatMessageApplyServices from "./MessageApplyServices/ChatMessageApplyServices"
import ControlMessageApplyService from "./MessageApplyServices/ControlMessageApplyService"
import type { ApplyMessageOptions } from "./MessageApplyServices/ChatMessageApplyServices/types"
import MessageService from "./MessageService"
import {
	AggregateAISearchCardDataType,
	ConversationMessageStatus,
	ConversationMessageType,
} from "@/types/chat/conversation_message"
import DotsService from "../dots/DotsService"
import { userStore } from "@/opensource/models/user"
import AiSearchApplyService from "./MessageApplyServices/ChatMessageApplyServices/AiSearchApplyService"
import Logger from "@/utils/log/Logger"
import OrganizationDotsService from "../dots/OrganizationDotsService"

interface PullMessagesFromServerOptions {
	conversationId: string
	topicId?: string
	pageSize?: number
	withoutSeqId?: boolean
	loadHistory?: boolean
}

const logger = new Logger("MessagePullService")

class MessagePullService {
	/** Message pull loop */
	private pullMessageInterval: NodeJS.Timeout | undefined

	/** Message pull frequency */
	private messagePullFrequency: number = 1000 * 30

	/** Message pull trigger list */
	private pullTriggerList: string[] = []

	private triggerPromise: Promise<void> | undefined

	/**
	 * Register the message pull loop.
	 */
	public registerMessagePullLoop() {
		if (this.pullMessageInterval) {
			clearInterval(this.pullMessageInterval)
		}

		this.pullMessageInterval = setInterval(() => {
			logger.log("pullMessageInterval: Pull offline messages")
			const organizationSeqId = MessageSeqIdService.getOrganizationRenderSeqId(
				userStore.user.userInfo?.organization_code ?? "",
			)
			this.pullOfflineMessages(organizationSeqId)
		}, this.messagePullFrequency)
	}

	/**
	 * Unregister the message pull loop.
	 */
	public unregisterMessagePullLoop() {
		if (this.pullMessageInterval) {
			clearInterval(this.pullMessageInterval)
		}
	}

	/**
	 * Pull messages from server for a conversation and persist locally.
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 * @param pageSize Page size
	 * @param withoutSeqId Whether to ignore seq_id
	 */
	public async pullMessagesFromServer({
		conversationId,
		topicId = "",
		pageSize = 10,
		loadHistory = false,
		withoutSeqId = false,
	}: PullMessagesFromServerOptions): Promise<SeqResponse<ConversationMessage>[]> {
		let pageToken = ""

		if (!withoutSeqId) {
			// Load history: use earliest seq_id in the list
			if (loadHistory) {
				pageToken = MessageStore.firstSeqId
			} else {
				// Load latest: prefer cached seq_id
				pageToken =
					MessageSeqIdService.getConversationPullSeqId(conversationId) ||
					MessageSeqIdService.getConversationRenderSeqId(conversationId) ||
					""
			}
		}

		const res = await ChatApi.getConversationMessages(conversationId, {
			topic_id: topicId,
			limit: pageSize,
			order: "desc",
			page_token: pageToken,
		})

		console.log("pullMessagesFromServer res =======> ", res)

		// if (!res.has_more) {
		// 	MessageStore.setHasMoreHistoryMessage(false)
		// }

		if (res.items && res.items.length > 0) {
			let conversationMessages = res.items
				.filter(
					(item) =>
						ChatMessageApplyServices.isChatHistoryMessage(item.seq) ||
						ControlMessageApplyService.isControlMessageShouldRender(item.seq),
				)
				.map((item) => item.seq)

			// Merge AI Search messages
			conversationMessages = await this.handleCombineAiSearchMessageByFetch(
				conversationMessages,
			)

			// Write to DB asynchronously
			requestIdleCallback(() => {
				MessageService.addHistoryMessagesToDB(conversationId, conversationMessages)
			})

			// Update conversation pull seq_id
			MessageSeqIdService.updateConversationPullSeqId(
				conversationId,
				conversationMessages[0]?.seq_id ?? "",
			)

			return conversationMessages
		}
		return []
	}

	/**
	 * Combine AI Search messages in a sequence array.
	 */
	handleCombineAiSearchMessage(
		conversationMessages: SeqResponse<
			ConversationMessage | AggregateAISearchCardConversationMessage<true>
		>[],
	) {
		let index = conversationMessages.findIndex(
			(item) => item.message.type !== ConversationMessageType.AggregateAISearchCard,
		)

		const array = [] as SeqResponse<ConversationMessage>[]

		if (index > 0) {
			// Has AI Search messages
			const aiSearchMessages = conversationMessages.slice(0, index) as SeqResponse<
				AggregateAISearchCardConversationMessage<true>
			>[]
			// Merge when the condition matches
			if (
				aiSearchMessages[0].message.aggregate_ai_search_card?.type ===
				AggregateAISearchCardDataType.PingPong
			) {
				const combinedMessage =
					AiSearchApplyService.combineAiSearchMessage(aiSearchMessages)
				if (combinedMessage) {
					array.push(combinedMessage)
				}
			}
		}

		if (index === -1) index = 0

		while (index < conversationMessages.length) {
			if (
				conversationMessages[index].message.type !==
				ConversationMessageType.AggregateAISearchCard
			) {
				array.push(conversationMessages[index] as SeqResponse<ConversationMessage>)
				index++
			} else {
				let nextIndex = conversationMessages.findIndex(
					(item, i) =>
						item.message.type !== ConversationMessageType.AggregateAISearchCard &&
						i > index,
				)

				if (nextIndex > 0) {
					const aiSearchMessages = conversationMessages.slice(
						index,
						nextIndex,
					) as SeqResponse<AggregateAISearchCardConversationMessage<true>>[]
					const combinedMessage =
						AiSearchApplyService.combineAiSearchMessage(aiSearchMessages)
					if (combinedMessage) {
						array.push(combinedMessage)
					}
				} else {
					nextIndex = conversationMessages.length
				}

				index = nextIndex
			}

			if (index >= conversationMessages.length) {
				break
			}
		}

		return array
	}

	/**
	 * Combine AI Search messages by fetching complete groups from backend.
	 */
	async handleCombineAiSearchMessageByFetch(
		conversationMessages: SeqResponse<
			ConversationMessage | AggregateAISearchCardConversationMessage<true>
		>[],
	) {
		const { conversationMessages: messages, promises } = conversationMessages.reduce(
			(acc, item) => {
				if (item.message.type === ConversationMessageType.AggregateAISearchCard) {
					const appMessageId = item.message?.app_message_id
					if (!acc.promises[appMessageId]) {
						const index = acc.conversationMessages.length
						acc.promises[appMessageId] = ChatApi.getMessagesByAppMessageId(
							appMessageId,
						).then((array) => {
							const combinedMessage = AiSearchApplyService.combineAiSearchMessage(
								array.reduce((prev, current) => {
									if (
										current.seq.message.type ===
										ConversationMessageType.AggregateAISearchCard
									) {
										prev.push(
											current.seq as SeqResponse<
												AggregateAISearchCardConversationMessage<true>
											>,
										)
									}
									return prev
								}, [] as SeqResponse<AggregateAISearchCardConversationMessage<true>>[]),
							)

							return {
								index: index,
								message: combinedMessage,
							}
						})
						acc.conversationMessages.push(item as SeqResponse<ConversationMessage>)
					}
				} else {
					acc.conversationMessages.push(item as SeqResponse<ConversationMessage>)
				}

				return acc
			},
			{
				promises: {} as Record<
					string,
					Promise<{
						message:
							| SeqResponse<AggregateAISearchCardConversationMessage<false>>
							| undefined
						index: number
					}>
				>,
				conversationMessages: [] as SeqResponse<ConversationMessage>[],
			},
		)

		await Promise.all(Object.values(promises)).then((array) => {
			array.forEach((item) => {
				if (item) {
					messages[item.index] = item.message!
				}
			})
		})

		return messages
	}

	/**
	 * Get current messages (most recent) with pagination.
	 */
	private async fetchCurrentMessages() {
		return fetchPaddingData(
			(p) =>
				ChatApi.messagePullCurrent(p).then((res) => ({
					...res,
					items: res.items.reverse(),
				})),
			[],
			MessageSeqIdService.getGlobalPullSeqId(),
		)
	}

	/**
	 * Pull offline messages.
	 * Pull page-by-page and apply immediately to improve timeliness.
	 */
	public async pullOfflineMessages(triggerSeqId?: string) {
		if (triggerSeqId) {
			this.pullTriggerList.push(triggerSeqId)
		}

		console.log("this.pullTriggerList ====> ", this.pullTriggerList)
		console.log("this.triggerPromise ====> ", this.triggerPromise)
		// Avoid concurrent pulls
		if (this.triggerPromise) {
			logger.log("pullOfflineMessages: Currently pulling, skip duplicate pull")
			return
		}

		try {
			this.triggerPromise = this.doPullOfflineMessages()
			await this.triggerPromise
		} finally {
			logger.log("pullOfflineMessages: Pull completed, reset pull trigger list")
			this.triggerPromise = undefined
		}
	}

	/**
	 * Core logic of pulling offline messages.
	 */
	private async doPullOfflineMessages() {
		// Use loop instead of recursion to avoid deep call stacks
		while (this.pullTriggerList.length > 0) {
			const organizationCode = userStore.user.userInfo?.organization_code ?? ""

			if (!organizationCode) {
				logger.warn("pullOfflineMessages: Current organization is empty")
				return
			}

			const globalPullSeqId = MessageSeqIdService.getOrganizationRenderSeqId(organizationCode)
			await this.pullMessagesFromPageToken(globalPullSeqId)
		}
	}

	/**
	 * Pull messages starting from the specified page token.
	 */
	private async pullMessagesFromPageToken(pageToken: string) {
		let currentPageToken = pageToken
		let hasMore = true
		let totalProcessed = 0

		while (hasMore) {
			try {
				const res = await ChatApi.messagePull({ page_token: currentPageToken })

				logger.log("pullMessagesFromPageToken: Pull messages", res)

				// Immediately process current page messages
				if (res.items && res.items.length > 0) {
					const sorted = res.items
						.map((item) => item.seq)
						.sort((a, b) => bigNumCompare(a.seq_id ?? "", b.seq_id ?? ""))

					logger.log(`Processing page with ${sorted.length} messages`)
					this.applyMessages(sorted)
					MessageSeqIdService.updateGlobalPullSeqId(last(sorted)?.seq_id ?? "")

					this.pullTriggerList = this.pullTriggerList.filter(
						(item) => bigNumCompare(item, last(sorted)?.seq_id ?? "") > 0,
					)

					totalProcessed += sorted.length
				} else {
					this.pullTriggerList = this.pullTriggerList.filter(
						(item) => bigNumCompare(item, pageToken) > 0,
					)
				}

				// Check if more data remains
				hasMore = res.has_more
				currentPageToken = res.page_token || ""

				// Add small delay to avoid overly frequent requests
				if (hasMore) {
					await new Promise((resolve) => setTimeout(resolve, 50))
				}
			} catch (error) {
				logger.error("pullMessagesFromPageToken error:", error)
				// Break pulling on error
				throw error
			}
		}

		logger.log(`Total messages processed: ${totalProcessed}`)
	}

	/**
	 * Device/login initialization: load conversations, users/groups, and recent messages.
	 */
	public async pullMessageOnFirstLoad(delightfulId: string, organizationCode: string) {
		if (!organizationCode) {
			logger.warn("pullOfflineMessages: Current organization is empty")
			return
		}

		try {
			// Step 1: fetch all user conversations
			const conversationShouldHandle = await fetchPaddingData(({ page_token }) => {
				return ChatApi.getConversationList(undefined, {
					status: ConversationStatus.Normal,
					page_token,
				})
			})

			// Step 2: fetch users and groups for all conversations
			const {
				[MessageReceiveType.User]: users = [],
				[MessageReceiveType.Ai]: ais = [],
				[MessageReceiveType.Group]: groups = [],
			} = groupBy(conversationShouldHandle, (item) => item.receive_type)

			const { userInfoService, groupInfoService } = getDataContext()

			// Step 2-1: fetch user info
			const user_ids = [...users, ...ais].map((u) => u.receive_id)

			// Include current user id
			if (userStore.user.userInfo?.user_id) user_ids.push(userStore.user.userInfo?.user_id)

			if (user_ids.length > 0) {
				await userInfoService.fetchUserInfos(user_ids, 2)
			}

			// Step 2-2: fetch group info
			const group_ids = groups.map((g) => g.receive_id)
			if (group_ids.length > 0) {
				await groupInfoService.fetchGroupInfos(group_ids)
			}

			// Step 3: init conversations (load data first, then render to avoid duplication)
			conversationService.initOnFirstLoad(delightfulId, organizationCode, conversationShouldHandle)

			// Step 4: pull recent messages
			const items = await this.fetchCurrentMessages()

			if (items.length > 0) {
				const seqId = last(items)?.seq.seq_id ?? ""
				MessageSeqIdService.updateGlobalPullSeqId(seqId)
				this.applyMessages(items.map((item) => item.seq))
				MessageSeqIdService.checkAllOrganizationRenderSeqId()
			}

			// Step 5: pull history for conversations without messages
			const conversationIds: string[] = conversationShouldHandle.map((item) => item.id)

			if (conversationIds.length > 0) {
				const data = await ChatApi.batchGetConversationMessages({
					conversation_ids: conversationIds,
					limit: 5,
				})

				const list = [] as SeqResponse<CMessage>[]
				// If data exists, combine and apply message lists
				if (data && Object.keys(data).length > 0) {
					// Combine lists in order
					const reverseItems = Object.keys(data).reduce((prev, cId) => {
						const c = data[cId].reverse().map((i) => i.seq)
						prev.push(...c)
						return prev
					}, [] as SeqResponse<ConversationMessage>[])

					if (reverseItems.length > 0) {
						list.push(...reverseItems)
					}

					if (list.length > 0) {
						// Apply messages
						this.applyMessages(list, { isHistoryMessage: true, sortCheck: false })
					}
				}
			}
		} catch (error) {
			logger.error("pullMessageOnFirstLoad error =======> ", error)
		}
	}

	/**
	 * Apply messages to UI/DB.
	 * @param messages Messages
	 * @param options Apply options
	 */
	private applyMessages(
		messages: SeqResponse<CMessage> | SeqResponse<CMessage>[],
		options?: ApplyMessageOptions,
	) {
		messages = Array.isArray(messages) ? messages : [messages]
		// Messages processed in ascending order
		const currentOrganization = userStore.user.userInfo?.organization_code

		if (!currentOrganization) {
			logger.warn("applyMessages: Current organization is empty")
			return
		}

		messages.forEach((message) => {
			// If message org differs from current org, handle as non-current org flow
			if (message.organization_code !== currentOrganization) {
				// unhandleMessages.push(message)
				// let organizationSeqId = OrganizationDotsService.getOrganizationDotSeqId(
				// 	message.organization_code,
				// )
				// console.log("organizationSeqId dot", organizationSeqId, message.seq_id)
				// if (!organizationSeqId) {
				// 	organizationSeqId = MessageSeqIdService.getOrganizationRenderSeqId(
				// 		message.organization_code,
				// 	)
				// }
				// console.log("organizationSeqId 2", organizationSeqId)
				// if (bigNumCompare(organizationSeqId, message.seq_id) < 0) {
				const m = message as SeqResponse<ConversationMessage>

				// FIXME: Need confirmation, remove later
				// const delightfulAccount = useUserStore
				// 	.getState()
				// 	.accounts.find((account) => account.delightful_id === message.delightful_id)

				const organization = userStore.user.delightfulOrganizationMap?.[m.organization_code]

				const isSelf = organization?.delightful_user_id === m.message.sender_id

				if (
					// Is history message (FIXME: AI Search not handled yet)
					ChatMessageApplyServices.isChatHistoryMessage(message) &&
					// Message is unread
					m.message.status === ConversationMessageStatus.Unread &&
					// Not sent by self
					!isSelf
				) {
					if (
						// Message seq_id is greater than this organization's render seq_id
						bigNumCompare(
							message.seq_id,
							MessageSeqIdService.getOrganizationRenderSeqId(
								message.organization_code,
							),
						) > 0
					) {
						logger.log("Add organization unread dot", message)
						DotsService.addConversationUnreadDots(
							message.organization_code,
							message.conversation_id,
							m.message.topic_id ?? "",
							message.seq_id,
							1,
						)
					} else {
						logger.log("Not this organization's message, but already applied", {
							seqId: message.seq_id,
							// organizationSeqId,
							organizationCode: message.organization_code,
						})
					}
				}

				// If receiving another organization's message, update that org's seq_id
				MessageSeqIdService.updateOrganizationRenderSeqId(
					currentOrganization,
					message.seq_id,
				)
			} else {
				logger.log("applyMessages: Apply message", message)
				// If same org, apply and persist
				MessageApplyServices.applyMessage(message, options)

				// Update other organizations' render seq_id
				Object.values(userStore.user.delightfulOrganizationMap).forEach((item) => {
					if (item.delightful_organization_code !== currentOrganization) {
						// If that org has no red dots, update its seq_id
						if (
							OrganizationDotsService.getOrganizationDot(
								item.delightful_organization_code,
							) <= 0
						) {
							MessageSeqIdService.updateOrganizationRenderSeqId(
								item.delightful_organization_code,
								message.seq_id,
							)
						}
					} else {
						// Update render seq_id for current org
						MessageSeqIdService.updateOrganizationRenderSeqId(
							message.organization_code,
							message.seq_id,
						)
					}
				})
			}
		})

		// PS: Skip handling other organizations here; handle on org switch by pulling offline messages.
		// unhandleMessages.forEach((message) => {
		// 	MessageApplyServices.applyMessage(message)
		// })
	}
}

export default MessagePullService
