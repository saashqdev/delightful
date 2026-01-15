import type {
	AssociateQuestion,
	AggregateAISearchCardConversationMessage,
} from "@/types/chat/conversation_message"
import { AggregateAISearchCardDataType } from "@/types/chat/conversation_message"
import { StreamStatus, type SeqResponse } from "@/types/request"
import { cloneDeep, merge } from "lodash-es"
import { makeObservable, observable, toJS } from "mobx"
import MessageService from "../../MessageService"
import StreamMessageApplyServiceV2 from "../StreamMessageApplyServiceV2"
import { bigNumCompare } from "@/utils/string"

interface RelationInfo {
	message_id: string
	conversation_id: string
	topic_id: string
}

class AiSearchApplyService {
	/**
	 * Map of aggregate AI search card app message ID to actual message info.
	 * key: app_message_id, value: object containing message_id, conversation_id, topic_id.
	 */
	aggregateAISearchCardMessageIdMap: Record<string, RelationInfo> = {}

	/**
	 * Temporary storage for aggregate AI search card messages.
	 * key: app_message_id, value: message object.
	 */
	tempMessageMapContent: Record<
		string,
		SeqResponse<AggregateAISearchCardConversationMessage<false>>["message"]
	> = {}

	/**
	 * Map LLM streaming seq_id to app_message_id for lookup during streaming.
	 * key: seq_id, value: app_message_id.
	 */
	llmResponseSeqIdMap: Record<string, string> = {}

	constructor() {
		makeObservable(this, {
			tempMessageMapContent: observable,
		})
	}

	/**
	 * Get the app message ID for a given LLM streaming seq_id.
	 * @param llmResponseSeqId Streaming seq_id from LLM responses.
	 * @returns The corresponding app message ID.
	 */
	getAppMessageIdByLLMResponseSeqId(llmResponseSeqId: string) {
		return this.llmResponseSeqIdMap[llmResponseSeqId]
	}

	/**
	 * Record the mapping from streaming seq_id to app message ID.
	 * @param llmResponseSeqId Streaming seq_id from LLM responses.
	 * @param appMessageId The app message ID to associate.
	 */
	setLLMResponseSeqId(llmResponseSeqId: string, appMessageId: string) {
		this.llmResponseSeqIdMap[llmResponseSeqId] = appMessageId
	}

	/**
	 * Record a temporary message.
	 * @param appMessageId App message ID.
	 * @param message Original message.
	 * @returns Converted message stored in the temporary map.
	 */
	recordTempMessage(
		appMessageId: string,
		message: SeqResponse<AggregateAISearchCardConversationMessage<true>>,
	) {
		const newMessage = this.createAggregateAISearchCardMessage(message)
		this.tempMessageMapContent[appMessageId] = newMessage.message
		return newMessage
	}

	/**
	 * Get a temporary message by app message ID.
	 * @param appMessageId App message ID.
	 * @returns The temporary message or undefined if not found.
	 */
	getTempMessageContent(appMessageId: string) {
		return this.tempMessageMapContent[appMessageId]
	}

	/**
	 * Apply an Aggregate AI Search Card message.
	 * Update local cached message content based on the message type.
	 * @param message The received message.
	 */
	apply(message: SeqResponse<AggregateAISearchCardConversationMessage<true>>) {
		switch (message.message.aggregate_ai_search_card?.type) {
			case AggregateAISearchCardDataType.SearchDeepLevel:
				this.updateSearchDeepLevel(message)
				break
			case AggregateAISearchCardDataType.AssociateQuestion:
				this.updateAssociateQuestion(message)
				break
			case AggregateAISearchCardDataType.MindMap:
				this.updateMindMap(message)
				break
			case AggregateAISearchCardDataType.Search:
				this.updateSearch(message)
				break
			case AggregateAISearchCardDataType.LLMResponse:
				this.updateLLMResponse(message)
				break
			case AggregateAISearchCardDataType.Event:
				this.updateEvent(message)
				break
			case AggregateAISearchCardDataType.PingPong:
				this.updatePingPong(message)
				break
			case AggregateAISearchCardDataType.Terminate:
				this.updateTerminate(message)
				break
			case AggregateAISearchCardDataType.PPT:
				this.updatePPT(message)
				break
			default:
				break
		}

		if (!this.getTempMessageContent(message.message.app_message_id)) {
			MessageService.addReceivedMessage(this.generateMessage(message))
			// Record mapping between app message ID and actual message identifiers
			StreamMessageApplyServiceV2.recordMessageInfo(message)
		} else {
			const messageInfo = StreamMessageApplyServiceV2.queryMessageInfo(
				message.message.app_message_id,
			)
			// Update the existing message
			MessageService.updateMessage(
				messageInfo.conversationId,
				messageInfo.topicId,
				messageInfo.messageId,
				(m) => {
					;(
						m.message as AggregateAISearchCardConversationMessage<false>
					).aggregate_ai_search_card = cloneDeep(
						this.getTempMessageContent(message.message.app_message_id)
							.aggregate_ai_search_card,
					)
					return m
				},
				true,
			)
		}
	}

	/**
	 * Append reasoning content during streaming.
	 * @param appMessageId App message ID.
	 * @param reasoningContent Reasoning text to append.
	 */
	appendReasoningContent(seqId: string, reasoningContent: string) {
		const appMessageId = this.getAppMessageIdByLLMResponseSeqId(seqId)
		const localMessage = this.getTempMessageContent(appMessageId)
		if (!localMessage) return
		localMessage.aggregate_ai_search_card!.reasoning_content =
			(localMessage.aggregate_ai_search_card!.reasoning_content ?? "") + reasoningContent

		const messageInfo = StreamMessageApplyServiceV2.queryMessageInfo(appMessageId)
		if (!messageInfo) return

		// Update view data
		MessageService.updateMessage(
			messageInfo.conversationId,
			messageInfo.topicId,
			messageInfo.messageId,
			(m) => {
				const textMessage = m.message as AggregateAISearchCardConversationMessage
				if (textMessage.aggregate_ai_search_card) {
					textMessage.aggregate_ai_search_card.reasoning_content =
						localMessage.aggregate_ai_search_card!.reasoning_content
				}
				return m
			},
		)
	}

	/**
	 * Append LLM response content during streaming.
	 * @param seqId Streaming seq_id.
	 * @param content Text content chunk to append.
	 */
	appendContent(seqId: string, content: string) {
		const appMessageId = this.getAppMessageIdByLLMResponseSeqId(seqId)
		const localMessage = this.getTempMessageContent(appMessageId)
		if (!localMessage) return
		// Update local cache
		localMessage.aggregate_ai_search_card!.llm_response =
			(localMessage.aggregate_ai_search_card!.llm_response ?? "") + content

		const messageInfo = StreamMessageApplyServiceV2.queryMessageInfo(appMessageId)
		if (!messageInfo) return

		// Update view data
		MessageService.updateMessage(
			messageInfo.conversationId,
			messageInfo.topicId,
			messageInfo.messageId,
			(m) => {
				const textMessage = m.message as AggregateAISearchCardConversationMessage
				if (textMessage.aggregate_ai_search_card) {
					textMessage.aggregate_ai_search_card.llm_response =
						localMessage.aggregate_ai_search_card!.llm_response
				}
				return m
			},
		)
	}

	/**
	 * Generate a new message object, replacing content with the temporary cache if present.
	 * @param message Original message.
	 * @returns Converted message.
	 */
	generateMessage(
		message: SeqResponse<AggregateAISearchCardConversationMessage<true>>,
	): SeqResponse<AggregateAISearchCardConversationMessage<false>> {
		const {
			message: { app_message_id },
		} = message
		const tempMessage = this.getTempMessageContent(app_message_id)

		if (tempMessage) {
			return {
				...message,
				message: {
					...message.message,
					aggregate_ai_search_card: toJS(tempMessage.aggregate_ai_search_card),
				},
			}
		}

		return this.recordTempMessage(app_message_id, message)
	}

	/**
	 * Create an Aggregate AI Search Card message.
	 * Convert the original message into a local-display/storage friendly structure.
	 * @param message Original message.
	 * @returns Converted message ready for local usage.
	 */
	createAggregateAISearchCardMessage(
		message: SeqResponse<AggregateAISearchCardConversationMessage<true>>,
	): SeqResponse<AggregateAISearchCardConversationMessage<false>> {
		return {
			...message,
			message: {
				...message.message,
				aggregate_ai_search_card: {
					llm_response: undefined,
					associate_questions: {},
					mind_map: undefined,
					search: {},
					event: [],
					finish: false,
					error: false,
					ppt: undefined,
					search_deep_level: undefined,
					reasoning_content: message.message.aggregate_ai_search_card?.reasoning_content,
					stream_options: message.message.aggregate_ai_search_card?.stream_options,
				},
			},
		}
	}

	/**
	 * Update search depth.
	 * Handle messages of type SearchDeepLevel to update depth-related info.
	 * @param receiveMessage The received message.
	 */
	updateSearchDeepLevel(
		receiveMessage: SeqResponse<AggregateAISearchCardConversationMessage<true>>,
	) {
		const { aggregate_ai_search_card: { search_deep_level = undefined } = {}, app_message_id } =
			receiveMessage.message
		const localMessage = this.getTempMessageContent(app_message_id)
		if (!localMessage) return
		localMessage.aggregate_ai_search_card!.search_deep_level = search_deep_level
	}

	/**
	 * Update associate questions.
	 * Handle messages of type AssociateQuestion to update the list.
	 * @param message The received message.
	 */
	updateAssociateQuestion(message: SeqResponse<AggregateAISearchCardConversationMessage<true>>) {
		const { aggregate_ai_search_card: { associate_questions = {} } = {}, app_message_id } =
			message.message
		const localMessage = this.getTempMessageContent(app_message_id)
		if (!localMessage) return
		localMessage.aggregate_ai_search_card!.associate_questions = Object.entries(
			associate_questions,
		).reduce((acc, [key, value]) => {
			if (typeof value === "string") {
				acc[key] = {
					title: value,
					llm_response: null,
					search_keywords: null,
					total_words: 0,
				}
			} else {
				acc[key] = merge(
					{
						title: value,
						llm_response: null,
						search_keywords: null,
						total_words: 0,
					},
					value,
				)
			}
			return acc
		}, {} as Record<string, AssociateQuestion>)
	}

	/**
	 * Update mind map.
	 * Handle messages of type MindMap to update mind map data.
	 * @param message The received message.
	 */
	updateMindMap(message: SeqResponse<AggregateAISearchCardConversationMessage<true>>) {
		const { aggregate_ai_search_card, app_message_id } = message.message
		const localMessage = this.getTempMessageContent(app_message_id)
		if (!localMessage) return
		localMessage.aggregate_ai_search_card!.mind_map =
			aggregate_ai_search_card?.mind_map ?? undefined
	}

	/**
	 * Update search results.
	 * Handle messages of type Search to update results and metadata.
	 * @param message The received message.
	 */
	updateSearch(message: SeqResponse<AggregateAISearchCardConversationMessage<true>>) {
		const { aggregate_ai_search_card, app_message_id } = message.message
		const localMessage = this.getTempMessageContent(app_message_id)
		if (!localMessage || !aggregate_ai_search_card) return

		const parentId = aggregate_ai_search_card.parent_id
		if (!parentId) return

		if (!localMessage.aggregate_ai_search_card!.search?.[parentId]) {
			localMessage.aggregate_ai_search_card!.search[parentId] = []
		}
		if (aggregate_ai_search_card.search) {
			localMessage.aggregate_ai_search_card!.search[parentId] =
				aggregate_ai_search_card.search
		}

		// When parentId is "0", it's the root question; do not store as associate.
		if (parentId !== "0") {
			if (!localMessage.aggregate_ai_search_card!.associate_questions?.[parentId]) {
				localMessage.aggregate_ai_search_card!.associate_questions[parentId] = {
					title: "",
					llm_response: null,
					search_keywords: null,
					total_words: 0,
				}
			}
			localMessage.aggregate_ai_search_card!.associate_questions[parentId].search_keywords =
				aggregate_ai_search_card.search_keywords ?? []

			// Update total word count
			localMessage.aggregate_ai_search_card!.associate_questions[parentId].total_words =
				aggregate_ai_search_card.total_words ?? 0

			// Update matched page count
			localMessage.aggregate_ai_search_card!.associate_questions[parentId].match_count =
				aggregate_ai_search_card.match_count ?? 0

			// Update read page count
			localMessage.aggregate_ai_search_card!.associate_questions[parentId].page_count =
				aggregate_ai_search_card.page_count ?? 0
		}
	}

	/**
	 * Update LLM response.
	 * Handle messages of type LLMResponse; update AI answer for root and associate questions.
	 * @param message The received message.
	 */
	updateLLMResponse(message: SeqResponse<AggregateAISearchCardConversationMessage<true>>) {
		const { aggregate_ai_search_card, app_message_id } = message.message
		const localMessage = this.getTempMessageContent(app_message_id)
		if (!localMessage || !aggregate_ai_search_card) return

		const parentId = aggregate_ai_search_card.parent_id
		if (!parentId) return

		if (parentId !== "0") {
			/**
			 * Find the associate question by key question ID and update its answer.
			 */
			const associateQuestion =
				localMessage.aggregate_ai_search_card!.associate_questions?.[parentId]
			if (associateQuestion) {
				associateQuestion.llm_response = aggregate_ai_search_card.llm_response ?? null
			} else {
				console.warn("Key question not found", parentId, message)
			}
		} else {
			/**
			 * Update the root question's answer.
			 */
			localMessage.aggregate_ai_search_card!.llm_response =
				aggregate_ai_search_card.llm_response ?? undefined
			localMessage.aggregate_ai_search_card!.stream_options =
				aggregate_ai_search_card.stream_options ?? undefined

			this.updateAggregateAISearchCardLlmResponseByStreamOptions(localMessage, message)
		}
	}

	/**
	 * Update events.
	 * Handle messages of type Event to update the event list.
	 * @param message The received message.
	 */
	updateEvent(message: SeqResponse<AggregateAISearchCardConversationMessage<true>>) {
		const { aggregate_ai_search_card, app_message_id } = message.message
		const localMessage = this.getTempMessageContent(app_message_id)
		if (!localMessage) return
		localMessage.aggregate_ai_search_card!.event = aggregate_ai_search_card?.event ?? []
	}

	/**
	 * Update Ping-Pong status.
	 * Handle messages of type PingPong; mark the message as finished.
	 * @param message The received message.
	 */
	updatePingPong(message: SeqResponse<AggregateAISearchCardConversationMessage<true>>) {
		const {
			message: { app_message_id },
		} = message
		const localMessage = this.getTempMessageContent(app_message_id)
		if (!localMessage) {
			return
		}
		if (localMessage.aggregate_ai_search_card?.finish === false) {
			localMessage.aggregate_ai_search_card.finish = true
		}
	}

	/**
	 * Update termination state.
	 * Handle messages of type Terminate; mark error state and remove the message.
	 * @param message The received message.
	 */
	updateTerminate(message: SeqResponse<AggregateAISearchCardConversationMessage<true>>) {
		const { app_message_id } = message.message
		const localMessage = this.getTempMessageContent(app_message_id)
		if (!localMessage) return
		localMessage.aggregate_ai_search_card!.error = true

		// Remove the message
		MessageService.removeMessage(
			message.conversation_id,
			message.message_id,
			message.conversation_id,
		)

		// Mark as finished
		if (localMessage.aggregate_ai_search_card?.finish === false) {
			localMessage.aggregate_ai_search_card.finish = true
		}
	}

	/**
	 * Update PPT data.
	 * Handle messages of type PPT to update slide data.
	 * @param message The received message.
	 */
	updatePPT(message: SeqResponse<AggregateAISearchCardConversationMessage<true>>) {
		const { aggregate_ai_search_card, app_message_id } = message.message
		const localMessage = this.getTempMessageContent(app_message_id)
		if (!localMessage) return
		localMessage.aggregate_ai_search_card!.ppt = aggregate_ai_search_card?.ppt ?? undefined
	}

	/**
	 * Update Aggregate AI Search Card LLM response.
	 * Update LLM response content based on stream options, handling start/end states.
	 * @param localMessage The local stored message.
	 * @param message The received message.
	 */
	updateAggregateAISearchCardLlmResponseByStreamOptions(
		localMessage: SeqResponse<AggregateAISearchCardConversationMessage<false>>["message"],
		message: SeqResponse<AggregateAISearchCardConversationMessage<true>>,
	) {
		const { aggregate_ai_search_card } = message.message
		if (!aggregate_ai_search_card) return
		const { stream_options } = aggregate_ai_search_card
		// If not streaming, update the LLM response directly
		if (!stream_options || !stream_options.stream) {
			localMessage.aggregate_ai_search_card!.llm_response =
				message.message.aggregate_ai_search_card?.llm_response ?? ""
			return
		}

		// If streaming, update LLM response based on streaming status
		const { status } = stream_options
		if (status === StreamStatus.End) {
			// Streaming ended; update LLM response
			localMessage.aggregate_ai_search_card!.llm_response =
				message.message.aggregate_ai_search_card?.llm_response ?? ""
		} else if (status === StreamStatus.Start) {
			this.setLLMResponseSeqId(message.seq_id, message.message.app_message_id)

			// Defer to next tick to ensure message info has been recorded
			setTimeout(() => {
				// Streaming started; add to task queue
				StreamMessageApplyServiceV2.apply({
					target_seq_id: message.seq_id,
					// @ts-ignore
					streams: {
						stream_options: {
							status: StreamStatus.Start,
						},
						reasoning_content:
							message.message.aggregate_ai_search_card?.reasoning_content ?? "",
						content: "",
						llm_response: "",
					},
				})
			})
		}
	}

	/**
	 * Combine multiple AI search messages into one consolidated message.
	 * @param aiSearchMessages The list of AI search messages.
	 * @returns The combined message (or undefined if input is empty).
	 */
	combineAiSearchMessage(
		aiSearchMessages: SeqResponse<AggregateAISearchCardConversationMessage<true>>[],
	) {
		if (aiSearchMessages.length === 0) return

		aiSearchMessages.sort((a, b) =>
			bigNumCompare(
				a.message.aggregate_ai_search_card?.id ?? "",
				b.message.aggregate_ai_search_card?.id ?? "",
			),
		)

		const combinedMessage = this.createAggregateAISearchCardMessage(aiSearchMessages[0])
		for (let i = 1; i < aiSearchMessages.length; i++) {
			const message = aiSearchMessages[i]
			switch (message.message.aggregate_ai_search_card?.type) {
				case AggregateAISearchCardDataType.SearchDeepLevel:
					combinedMessage.message.aggregate_ai_search_card!.search_deep_level =
						message.message.aggregate_ai_search_card!.search_deep_level
					break
				case AggregateAISearchCardDataType.AssociateQuestion:
					combinedMessage.message.aggregate_ai_search_card!.associate_questions =
						Object.entries(
							message.message.aggregate_ai_search_card!.associate_questions ?? {},
						).reduce((acc, [key, value]) => {
							if (typeof value === "string") {
								acc[key] = {
									title: value,
									llm_response: null,
									search_keywords: null,
									total_words: 0,
								}
							} else {
								acc[key] = merge(
									{
										title: value,
										llm_response: null,
										search_keywords: null,
										total_words: 0,
									},
									value,
								)
							}
							return acc
						}, {} as Record<string, AssociateQuestion>)
					break
				case AggregateAISearchCardDataType.MindMap:
					combinedMessage.message.aggregate_ai_search_card!.mind_map =
						message.message.aggregate_ai_search_card!.mind_map
					break
				case AggregateAISearchCardDataType.Search:
					const parentId = message.message.aggregate_ai_search_card!.parent_id

					combinedMessage.message.aggregate_ai_search_card!.search[parentId] =
						message.message.aggregate_ai_search_card!.search ?? []

					if (parentId !== "0") {
						if (
							!combinedMessage.message.aggregate_ai_search_card!
								.associate_questions?.[parentId]
						) {
							combinedMessage.message.aggregate_ai_search_card!.associate_questions[
								parentId
							] = {
								title: "",
								llm_response: null,
								search_keywords: null,
								total_words: 0,
							}
						}
						combinedMessage.message.aggregate_ai_search_card!.associate_questions[
							parentId
						].search_keywords =
							message.message.aggregate_ai_search_card!.search_keywords ?? []

						// Update total word count
						combinedMessage.message.aggregate_ai_search_card!.associate_questions[
							parentId
						].total_words = message.message.aggregate_ai_search_card!.total_words ?? 0

						// Update matched page count
						combinedMessage.message.aggregate_ai_search_card!.associate_questions[
							parentId
						].match_count = message.message.aggregate_ai_search_card!.match_count ?? 0

						// Update read page count
						combinedMessage.message.aggregate_ai_search_card!.associate_questions[
							parentId
						].page_count = message.message.aggregate_ai_search_card!.page_count ?? 0
					}
					break
				case AggregateAISearchCardDataType.LLMResponse:
					if (message.message.aggregate_ai_search_card?.parent_id === "0") {
						combinedMessage.message.aggregate_ai_search_card!.llm_response =
							message.message.aggregate_ai_search_card!.llm_response
					} else if (message.message.aggregate_ai_search_card?.parent_id) {
						const parentId = message.message.aggregate_ai_search_card!.parent_id
						combinedMessage.message.aggregate_ai_search_card!.associate_questions[
							parentId
						].llm_response =
							message.message.aggregate_ai_search_card!.llm_response ?? null
					}
					break
				case AggregateAISearchCardDataType.Event:
					combinedMessage.message.aggregate_ai_search_card!.event =
						message.message.aggregate_ai_search_card!.event
					break
				case AggregateAISearchCardDataType.PingPong:
					combinedMessage.message.aggregate_ai_search_card!.finish = true
					break
				case AggregateAISearchCardDataType.Terminate:
					combinedMessage.message.aggregate_ai_search_card!.error = true
					break
				case AggregateAISearchCardDataType.PPT:
					combinedMessage.message.aggregate_ai_search_card!.ppt =
						message.message.aggregate_ai_search_card!.ppt
					break
			}
		}
		return combinedMessage
	}
}

export default new AiSearchApplyService()
