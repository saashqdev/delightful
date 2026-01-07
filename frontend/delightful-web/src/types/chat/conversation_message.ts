/**
 * Chat event types
 */
import type { QuickInstruction, SystemInstruct } from "../bot"
import type { StreamStatus } from "../request"
import type { SeqMessageBase } from "./base"
import {
	RevokeMessage,
	GroupAddMemberMessage,
	GroupCreateMessage,
	GroupDisbandMessage,
	GroupUsersRemoveMessage,
	GroupUpdateMessage,
} from "./control_message"

export const enum ConversationMessageType {
	/** Text message */
	Text = "text",

	/** Rich text message */
	RichText = "rich_text",

	/** Markdown message */
	Markdown = "markdown",

	/** AI search */
	AggregateAISearchCard = "aggregate_ai_search_card",

	/** AI search - new version */
	AggregateAISearchCardV2 = "aggregate_ai_search_card_v2",

	/** Image message */
	Image = "image",

	/** Video message */
	Video = "video",

	/** File message */
	Files = "files",

	/** Voice message */
	Voice = "voice",

	/** Delightful search card message */
	DelightfulSearchCard = "delightful_search_card",

	/** AI text-to-image */
	AiImage = "ai_image_card",

	/** Convert original image to HD */
	HDImage = "image_convert_high_card",

	/** Recording summary */
	RecordingSummary = "recording_summary",

	/** Super Magic message */
	BeDelightful = "general_agent_card",
}

/**
 * With stream message status
 */
export interface WithStreamOptions<T extends object = object> {
	stream_options?: {
		/** Stream message status */
		status: StreamStatus
		/** Stream message ID */
		stream: boolean
	} & T
}

/**
 * With reasoning content and stream status
 */
export interface WithReasoningContentAndStreamOptions {
	reasoning_content?: string
	stream_options?: {
		/** Stream message status */
		status: StreamStatus
		/** Stream message ID */
		stream: boolean
	}
}

/**
 * Conversation message
 */
export interface ConversationMessageBase extends SeqMessageBase {
	/** App message ID */
	app_message_id: string
	/** Sender ID */
	sender_id: string
	/** Sent time */
	send_time: number
	/** Message status */
	status: ConversationMessageStatus
	/** Unread message count */
	unread_count: number
	/** Topic ID */
	topic_id?: string
	/** Whether revoked */
	revoked?: boolean
	/** Local temporary data storage */
	temp_custom_data?: Record<string, unknown>
	/** Whether deleted locally */
	is_local_deleted?: boolean
}

export interface BeDelightfulContent {
	type: ConversationMessageType.BeDelightful
	content: string
}


/**
 * Message attachment
 */
export type ConversationMessageAttachment = {
	file_id: string
	file_type?: number
	file_extension?: string
	file_size?: number
	file_name?: string
}

/**
 * Conversation message instruction
 */
export type ConversationMessageInstruct = {
	value: string
	instruction: Exclude<QuickInstruction, SystemInstruct>
}

/**
 * Message with instruction payload
 */
export type WithConversationMessageInstruct = {
	instructs?: ConversationMessageInstruct[] | undefined
}

/**
 * External file
 */
export type ExternalFile = {
	url?: string
	file_extension?: string
}

/**
 * Chat file info
 */
export interface ChatFileInfo {
	file_id: string
	user_id: string
	delightful_message_id: string
	organization_code: string
	file_extension: string
	file_key: string
	file_size: number
	created_at: string
	updated_at: string
}

/**
 * Chat file URL data
 */
export interface ChatFileUrlData {
	path: string
	url: string
	expires: number
	download_name: string
}

/**
 * Rich text message
 */
export interface RichTextConversationMessage extends ConversationMessageBase {
	type: ConversationMessageType.RichText
	/** Message content */
	rich_text?: {
		content: string
		attachments?: ConversationMessageAttachment[]
	} & WithConversationMessageInstruct
}

/**
 * Text message
 */
export interface TextConversationMessage extends ConversationMessageBase {
	type: ConversationMessageType.Text
	/** Message content */
	text?: {
		content: string
		attachments?: ConversationMessageAttachment[]
	} & WithReasoningContentAndStreamOptions &
		WithConversationMessageInstruct
}

export type RecordSummaryOriginContent = {
	duration: string
	speaker: string
	text: string
	start_time: string
	end_time: string
}[]

/**
 * Recording summary message
 */
export interface RecordSummaryConversationMessage extends ConversationMessageBase {
	type: ConversationMessageType.RecordingSummary
	/** Message content */
	recording_summary?: {
		status?: RecordSummaryStatus
		text?: string // Result of a single translation
		recording_blob?: string // Data source for translation
		origin_content?: RecordSummaryOriginContent // Original content
		attachments?: ConversationMessageAttachment[] // Audio files
		full_text?: string // Current full translation result
		ai_result?: string // AI summarized result
		duration?: string // Total duration
		title?: string // AI summary title
		audio_link?: string // Recording link
		is_recognize?: boolean // Whether recognition is performed
	}
}

/**
 * Markdown message
 */
export interface MarkdownConversationMessage extends ConversationMessageBase {
	type: ConversationMessageType.Markdown
	/** Message content */
	markdown?: {
		content: string
		attachments?: ConversationMessageAttachment[]
	} & WithReasoningContentAndStreamOptions &
		WithConversationMessageInstruct
}

/**
 * AI search - related question
 */
export interface AssociateQuestion {
	/** Question */
	title: string
	/** LLM answer */
	llm_response: string | null
	/** Search keywords */
	search_keywords: string[] | null
	/** Total words */
	total_words?: number | null
	/** Total pages read */
	page_count?: number | null
	/** Total pages matched */
	match_count?: number | null
}

/**
 * AI search - search section
 */
export interface AggregateAISearchCardSearch {
	/** Search result ID */
	id: string
	/** Search result name */
	name: string
	/** Search result URL */
	url: string
	/** Search result publish time */
	datePublished: string
	/** Search result publish time display text */
	datePublishedDisplayText: string
	/** Whether the search result is family-friendly */
	isFamilyFriendly: boolean
	/** Search result display URL */
	displayUrl: string
	/** Search result summary */
	snippet: string
	/** Search result last crawled time */
	dateLastCrawled: string
	/** Search result cached URL */
	cachedPageUrl: string
	/** Search result language */
	language: string
	/** Whether the search result is navigational */
	isNavigational: boolean
	/** Whether the search result is cached */
	noCache: boolean
	/** Source index */
	index?: number
}

/**
 * AI search - mind map - child node
 */
export interface AggregateAISearchCardMindMapChildren {
	data: {
		text: string
	}
	children?: AggregateAISearchCardMindMapChildren[]
}

export const enum AggregateAISearchCardDataType {
	/** Search result */
	Search = 0,
	/** Answer */
	LLMResponse = 1,
	/** Mind map */
	MindMap = 2,
	/** Related question */
	AssociateQuestion = 3,
	/** Event */
	Event = 4,
	/** Ping pong */
	PingPong = 5,
	/** Terminated with error */
	Terminate = 6,
	/** PPT */
	PPT = 7,
	/** Search depth */
	SearchDeepLevel = 8,
}

export const enum RecordSummaryStatus {
	/** Start recording */
	Start = 1,
	/** Recording */
	Doing = 2,
	/** End recording */
	End = 3,
	/** Summarizing recording */
	Summarizing = 4,
	/** Recording summary complete */
	Summarized = 5,
}

/**
 * AI search - event
 */
export interface AggregateAISearchCardEvent {
	name: string
	time: string
	description: string
}

export const enum AggregateAISearchCardDeepLevel {
	/** Simple search */
	Simple = 1,
	/** Deep search */
	Deep = 2,
}

/**
 * AI search content
 */
interface AggregateAISearchCardContentFromService extends WithReasoningContentAndStreamOptions {
	/** Parent ID */
	parent_id: string
	/** Current ID */
	id: string
	/** Type */
	type: AggregateAISearchCardDataType
	/** Answer */
	llm_response: string | undefined
	/** Related question */
	/** FIXME: Backend should return AssociateQuestion; string can be removed later */
	associate_questions: Record<string, string | AssociateQuestion> | undefined
	/** Mind map */
	mind_map: AggregateAISearchCardMindMapChildren | string | undefined
	/** Search result */
	search: AggregateAISearchCardSearch[] | undefined
	/** Keywords */
	search_keywords: string[] | undefined
	/** Event */
	event: AggregateAISearchCardEvent[] | undefined
	/** Search depth: 1 - simple search, 2 - deep search */
	search_deep_level: AggregateAISearchCardDeepLevel | undefined
	/** PPT */
	ppt: string | undefined
	/** Total words */
	total_words: number | undefined
	/** Total matched pages */
	match_count: number | undefined
	/** Total pages read */
	page_count: number | undefined
}

export type AggregateAISearchCardContent = Pick<
	AggregateAISearchCardContentFromService,
	| "mind_map"
	| "llm_response"
	| "event"
	| "ppt"
	| "search_deep_level"
	| "reasoning_content"
	| "stream_options"
> & {
	search: Record<string, AggregateAISearchCardSearch[]>
	associate_questions: Record<string, AssociateQuestion>
	finish: boolean
	error: boolean
}

/**
 * AI search message
 */
export interface AggregateAISearchCardConversationMessage<FromService extends boolean = true>
	extends ConversationMessageBase {
	type: ConversationMessageType.AggregateAISearchCard
	aggregate_ai_search_card?: FromService extends true
		? AggregateAISearchCardContentFromService
		: AggregateAISearchCardContent
}

/**
 * AI search (v2) - related question
 */
export interface AssociateQuestionV2 {
	parent_question_id: string
	question_id: string
	question: string
}

/**
 * AI search (v2) - search result
 */
export interface SearchWebPageItem {
	id: string
	name: string
	url: string
	datePublished: string
	datePublishedDisplayText: string
	isFamilyFriendly: boolean
	displayUrl: string
	snippet: string
	dateLastCrawled: string
	cachedPageUrl: string
	language: string
	isNavigational: boolean
	noCache: boolean
	detail: string
	index?: number
}

/**
 * AI search (v2) - search result
 */
export interface SearchWebPage {
	question_id: string
	search: SearchWebPageItem[]
	total_words: number
	match_count: number
	page_count: number
}

export interface NoRepeatSearchDetail {
	id: string
	name: string
	url: string
	date_published: string
	date_published_display_text: string
	is_family_friendly: boolean
	display_url: string
	snippet: string
	date_last_crawled: string
	cached_page_url: string
	language: string
	is_navigational: boolean
	no_cache: boolean
}

/**
 * Stream message stage finish reason
 */
export const enum StreamStepFinishedReason {
	/** Success */
	Success = 0,
	/** Failure */
	Error = 1,
}

export interface StreamStepFinished {
	key: string
	finished_reason: StreamStepFinishedReason
}

/**
 * AI search (v2) - stream message options
 */
export interface AggregateAISearchCardV2StreamOptions
	extends WithStreamOptions<{
		stream_app_message_id: string
		steps_finished: Record<string, any>
	}> {}

/**
 * AI search (v2) - status
 */
export const enum AggregateAISearchCardV2Status {
	/** Searching */
	isSearching = 1,
	/** Reading */
	isReading = 2,
	/** Reasoning */
	isReasoning = 3,
	/** Summarizing */
	isSummarizing = 4,
	/** Finished */
	isEnd = 5,
}

/**
 * AI search (v2) - content
 */
export interface AggregateAISearchCardContentV2 extends AggregateAISearchCardV2StreamOptions {
	search_deep_level?: AggregateAISearchCardDeepLevel
	associate_questions?: Record<string, AssociateQuestionV2[]>
	search_web_pages?: SearchWebPage[]
	no_repeat_search_details?: SearchWebPageItem[]
	summary?: {
		reasoning_content?: string
		content: string
	}
	events?: AggregateAISearchCardEvent[]
	mind_map?: string
	ppt?: string
	status?: AggregateAISearchCardV2Status
}

/**
 * AI search (v2)
 */
export interface AggregateAISearchCardConversationMessageV2 extends ConversationMessageBase {
	type: ConversationMessageType.AggregateAISearchCardV2
	aggregate_ai_search_card_v2?: AggregateAISearchCardContentV2
}

/**
 * File message
 */
export interface FileConversationMessage extends ConversationMessageBase {
	type: ConversationMessageType.Files
	/** Message content */
	files?: {
		attachments: ConversationMessageAttachment[]
	} & WithConversationMessageInstruct
}

/**
 * Image message
 */
export interface ImageConversationMessage extends ConversationMessageBase {
	type: ConversationMessageType.Image
	/** Message content */
	image?: {
		file_id: string
	} & WithConversationMessageInstruct
}

/**
 * Video message
 */
export interface VideoConversationMessage extends ConversationMessageBase {
	type: ConversationMessageType.Video
	/** Message content */
	video?: {
		file_id: string
	} & WithConversationMessageInstruct
}

/**
 * Voice message
 */
export interface VoiceConversationMessage extends ConversationMessageBase {
	type: ConversationMessageType.Voice
	/** Message content */
	voice?: {
		file_id: string
	} & WithConversationMessageInstruct
}

/**
 * Magic search card search result
 */

export interface WikiSearchItem {
	/** Search result ID */
	id: string
	/** Search result name */
	name: string
	/** Search result URL */
	url: string
	/** Search result publish time */
	datePublished: number
	/** Search result publish time display text */
	datePublishedDisplayText: string
	/** Whether the search result is family-friendly */
	isFamilyFriendly: boolean
	/** Search result display URL */
	displayUrl: string
	/** Search result summary */
	snippet: string
	/** Search result last crawled time */
	dateLastCrawled: string
	/** Search result cached URL */
	cachedPageUrl: string
	/** Search result language */
	language: string
	/** Whether the search result is navigational */
	isNavigational: boolean
	/** Whether the search result is cached */
	noCache: boolean
}

export interface DelightfulSearchCardContent {
	/** Search results */
	search: WikiSearchItem[]
	/** Answer */
	llm_response: string
	/** Related questions */
	related_questions: string[]
}

/**
 * Magic search card message
 */
export interface DelightfulSearchCardConversationMessage extends ConversationMessageBase {
	type: ConversationMessageType.DelightfulSearchCard
	delightful_search_card?: DelightfulSearchCardContent
}

export const enum AIImagesDataType {
	/** Start generating */
	StartGenerate = 1,
	/** Generation complete */
	GenerateComplete = 2,
	/** Reference image */
	ReferImage = 3,
	/** Terminated with error */
	Error = 4,
}

export interface AIImagesContentItem {
	file_id: string
	url: string
}
export interface AIImagesContent {
	// Generation status
	type: AIImagesDataType
	// Image list
	items: AIImagesContentItem[]
	// Text description
	text: string
	// Referenced file
	refer_file_id: string
	// Text description
	refer_text: string
	// Error message
	error_message?: string
	// Aspect ratio
	radio?: string
}

/**
 * AI text-to-image
 */
export interface AIImagesMessage extends ConversationMessageBase {
	type: ConversationMessageType.AiImage
	/** Message content */
	ai_image_card?: AIImagesContent
}

export enum HDImageDataType {
	/** Start generating */
	StartGenerate = 1,
	/** Generation complete */
	GenerateComplete = 2,
	/** Terminated with error */
	Error = 3,
}

export interface HDImageContent {
	// Generation status
	type: HDImageDataType
	// Original file ID
	origin_file_id: string
	// HD file ID
	new_file_id: string
	// Text description
	refer_text: string
	// Error message
	error_message?: string
	// Aspect ratio
	radio?: string
}

/**
 * Convert original image to HD
 */
export interface HDImageMessage extends ConversationMessageBase {
	type: ConversationMessageType.HDImage
	/** Message content */
	image_convert_high_card?: HDImageContent
}

/**
 * Referable message
 */
export type MessageCanRefer = MessageCanRevoke

/**
 * Forwardable message
 */
export type MessageCanForward = MessageCanRevoke

export type MessageCanRenderToText = MessageCanRevoke

/**
 * Revocable message
 */
export type MessageCanRevoke =
	| TextConversationMessage
	| RichTextConversationMessage
	| MarkdownConversationMessage
	| DelightfulSearchCardConversationMessage
	| FileConversationMessage
	| ImageConversationMessage
	| VideoConversationMessage
	| VoiceConversationMessage
	| AggregateAISearchCardConversationMessage<false>
	| AIImagesMessage
	| RecordSummaryConversationMessage

/** Conversation message */
export type ConversationMessage =
	| TextConversationMessage
	| RichTextConversationMessage
	| MarkdownConversationMessage
	| DelightfulSearchCardConversationMessage
	| FileConversationMessage
	| ImageConversationMessage
	| VideoConversationMessage
	| VoiceConversationMessage
	| RevokeMessage
	| GroupAddMemberMessage
	| GroupCreateMessage
	| GroupDisbandMessage
	| GroupUsersRemoveMessage
	| GroupUpdateMessage
	| AggregateAISearchCardConversationMessage<false>
	| AggregateAISearchCardConversationMessageV2
	| AIImagesMessage
	| RecordSummaryConversationMessage
	| HDImageMessage

/** Send status */
export const enum SendStatus {
	/** Sending */
	Pending = 1,
	/** Sent successfully */
	Success = 2,
	/** Failed to send */
	Failed = 3,
}

/** Message send payload */
export interface ConversationMessageSend {
	// FIXME: TextConversationMessage should be changed to ConversationMessage
	message:
		| Pick<
				TextConversationMessage,
				| "type"
				| "text"
				| "app_message_id"
				| "topic_id"
				| "revoked"
				| "sender_id"
				| "send_time"
		  >
		| Pick<
				RichTextConversationMessage,
				| "type"
				| "rich_text"
				| "app_message_id"
				| "topic_id"
				| "revoked"
				| "sender_id"
				| "send_time"
		  >
		| Pick<
				MarkdownConversationMessage,
				| "type"
				| "markdown"
				| "app_message_id"
				| "topic_id"
				| "revoked"
				| "sender_id"
				| "send_time"
		  >
		| Pick<
				FileConversationMessage,
				| "type"
				| "files"
				| "app_message_id"
				| "topic_id"
				| "revoked"
				| "sender_id"
				| "send_time"
		  >
		| Pick<
				AIImagesMessage,
				| "type"
				| "ai_image_card"
				| "app_message_id"
				| "topic_id"
				| "revoked"
				| "sender_id"
				| "send_time"
		  >
		| Pick<
				RecordSummaryConversationMessage,
				| "type"
				| "recording_summary"
				| "app_message_id"
				| "topic_id"
				| "revoked"
				| "sender_id"
				| "send_time"
		  >
		| Pick<
				HDImageMessage,
				| "type"
				| "image_convert_high_card"
				| "app_message_id"
				| "topic_id"
				| "revoked"
				| "sender_id"
				| "send_time"
		  >
	conversation_id: string
	refer_message_id?: string
	status: SendStatus
	message_id: string
}

/**
 * Conversation message status
 */
export const enum ConversationMessageStatus {
	/** Unread */
	Unread = "unread",
	/** Read */
	Read = "read",
	/** Seen */
	Seen = "seen",
	/** Revoked */
	Revoked = "revoked",
}

/**
 * HD image conversion result
 */
export interface HDImageResult {
	/** Error message */
	error: string
	/** Completion status */
	finish_status: boolean
	/** Progress */
	progress: number
	/** HD image URLs */
	urls: string[]
}
