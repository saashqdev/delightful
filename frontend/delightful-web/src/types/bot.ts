/** Bot-related types */
import type { OperationTypes } from "@/opensource/pages/flow/components/AuthControlButton/types"
import type {
	InsertLocationMap,
	StatusIconKey,
} from "@/opensource/pages/flow/components/QuickInstructionButton/const"
import type { DelightfulFlow } from "@bedelightful/delightful-flow/DelightfulFlow/types/flow"

export interface WithPage<ListType> {
	page: number
	page_size: number
	total: number
	list: ListType
}

export namespace Bot {
	// User info
	export interface User {
		delightful_id: string
		id: string
		label: string
		like_num: number
		avatar_url: string
		description: string
		nickname: string
		organization_code: string
		status: number
		user_id: string
		user_type: number
	}

	/** Basic bot list item */
	export interface BotItem {
		id: string
		flow_id: string
		bot_version_id: string
		robot_name: string
		robot_avatar: string
		robot_description: string
		organization_code: string
		status: number
		enabled?: boolean
		created_at: string
		created_uid: string
		updated_at: string
		updated_uid: string
		deleted_at?: string
		deleted_uid?: string
		bot_version?: BotVersion
		quote?: number // Reference count
		user_operation: OperationTypes
		created_info: {
			delightful_id: string
			id: string
			user_id: string
			user_type: number
			user_manual: string
			label: string[]
			like_num: number
			avatar_url: string
			description: string
			nickname: string
			organization_code: string
			status: number
			extra: string
			created_at: string
			updated_at: string
			deleted_at: string
		}
		is_add?: boolean
		user_id?: string
		start_page: boolean
	}

	export type VisibilityConfig = {
		users: {
			id: string
			name?: string
		}[]
		departments: {
			id: string
			name?: string
		}[]
		visibility_type: VisibleRangeType
	}

	/** Bot version */
	export type BotVersion = Omit<BotItem, "status" | "bot_version"> & {
		root_id: string
		version_number: string
		version_name: string
		version_description: string
		release_scope: number
		review_status: number
		approval_status: number
		enterprise_release_status: number
		app_market_status: number
		flow_code: string
		flow_version: string
		delightfulUserEntity: User
		third_platform_list: Bot.ThirdPartyPlatform[]
		visibility_config: VisibilityConfig
	}

	/** Enterprise bot list item */
	export type OrgBotItem = BotItem & {
		root_id: string
		app_market_status: string
		approval_status: string
		enterprise_release_status: string
		release_scope: number
		review_status: string
		version_description: string
		version_name: string
		version_number: string
		is_add: boolean
	}

	/** Bot detail */
	export type Detail = {
		botEntity: {
			id: string
			flow_code: string
			root_id: string
			robot_name: string
			robot_avatar: string
			robot_description: string
			organization_code: string
			status: number
			updated_at: string
			updated_uid: string
			created_at: string
			created_uid: string
			user_operation: OperationTypes
			instructs: QuickInstructionList[]
			start_page: boolean
		}
		delightfulFlowEntity: DelightfulFlow.Flow & {
			updated_at: string
		}
		delightfulUserEntity: User
		botVersionEntity: {
			id: string
			flow_code: string
			root_id: string
			robot_name: string
			robot_avatar: string
			robot_description: string
			organization_code: string
			version_number: string
			version_description: string
			release_scope: number
			approval_status: number
			enterprise_release_status: number
			created_at: string
			created_uid: string
			updated_at: string
			updated_uid: string
			instructs: QuickInstructionList[]
		}
	}

	export type GetUserBotListParams = {
		page?: number
		pageSize?: number
		keyword?: string
	}

	export type SaveBotParams = {
		id?: string
		robot_name: string
		robot_avatar: string
		robot_description?: string
		start_page: boolean
	}

	export type SaveInstructParams = {
		bot_id: string
		instructs: QuickInstructionList[]
	}

	export type PublishBotParams = {
		bot_id?: string
		version_number: string
		version_description: string
		release_scope: number
		delightful_flow: DelightfulFlow.Flow
		third_platform_list: Bot.ThirdPartyPlatform[]
		visibility_config: VisibilityConfig
	}

	export type AddFriend = {
		avatar_url: string
		id: string
		nickname: string
		description: string
		label: string
		like_num: number
		delightful_id: string
		organization_code: string
		status: number
		user_id: string
		user_name: string
		user_type: number
	}

	export type PublishBot = {
		user: User
	}

	export type DefaultIcon = {
		icons: {
			bot: string
			flow: string
			tool_set: string
			mcp: string
		}
	}

	export type ThirdPartyPlatform = {
		id?: string
		bot_id?: string
		identification: string
		key: string
		type: ThirdPartyPlatformType
		enabled: boolean
		options: {
			app_key: string
			app_secret: string
		}
	}
}

export enum ThirdPartyPlatformType {
	DingTalk = "ding_robot",
	FeiShu = "fei_shu_robot",
	EnterpriseWeChat = "wechat_robot",
}

/* Release scope */
export enum ScopeType {
	// Personal
	private = 0,
	// Organization
	organization = 1,
	// Marketplace
	public = 2,
}

/* Visibility scope */
export enum VisibleRangeType {
	/* Visible to everyone */
	AllMember = 1,
	/* Visible to specified members/departments */
	SpecifiedMemberOrDepartment = 2,
}

/** Quick instruction types */
export const enum InstructionType {
	// Single choice
	SINGLE_CHOICE = 1,
	// Switch
	SWITCH = 2,
	// Text
	TEXT = 3,
	// Status
	STATUS = 4,
}

/** Quick instruction group types */
export const enum InstructionGroupType {
	// Toolbar
	TOOL = 1,
	// Dialog
	DIALOG = 2,
}

/** Quick instruction return type */
export type RespondInstructType = {
	[key: string]: string
}

/** Quick instruction description */
export type InstructionExplanation = {
	// Name
	name: string
	// Description
	description: string
	// Image
	image: string
	// Temporary image URL
	temp_image_url?: string
}

/** Quick instruction value */
export type InstructionValue = {
	/** ID */
	id: string
	/** Name */
	name: string
	/** Value */
	value: string
	/** Quick instruction description */
	instruction_explanation?: InstructionExplanation
}

/** Quick instruction */
export interface QuickInstructionBase {
	id: string
	/** Name */
	name: string
	/** Description */
	description: string
	/** Send instruction directly */
	send_directly?: boolean
	/** Instruction description */
	instruction_explanation?: InstructionExplanation
	/** Instruction insertion position */
	insert_location?: InsertLocationMap
	/** Instruction stays resident */
	residency?: boolean
	/** Instruction type: flow instruction or conversation instruction */
	instruction_type?: InstructionMode
}

/** Single-choice instruction */
export interface SelectorQuickInstruction extends QuickInstructionBase {
	type: InstructionType.SINGLE_CHOICE
	/** Values */
	values: InstructionValue[]
	/** Content */
	content: string
}

/** Switch instruction */
export interface SwitchQuickInstruction extends QuickInstructionBase {
	type: InstructionType.SWITCH
	/** On label */
	on?: string
	/** Off label */
	off?: string
	/** Default value */
	default_value: "on" | "off"
	/** Content */
	content: string
}

/** Text instruction */
export interface TextQuickInstruction extends QuickInstructionBase {
	type: InstructionType.TEXT
	/** Content */
	content: string
}

/** Status text color */
export enum StatusTextColor {
	DEFAULT = "default",
	GREEN = "green",
	RED = "red",
	ORANGE = "orange",
}

/** Status value */
export type InstructionStatus = {
	/** ID */
	id?: string
	/** Icon */
	icon: StatusIconKey
	/** Status label */
	status_text: string
	/** Status label color */
	text_color: string
	/** Value */
	value: string
	/** Switch state */
	switch: boolean
}

/** Status button */
export interface StatusQuickInstruction extends QuickInstructionBase {
	type: InstructionType.STATUS
	/** Default value */
	default_value: number
	/** Values */
	values: InstructionStatus[]
}

/** Quick instruction display type */
export const enum DisplayType {
	// System
	SYSTEM = 2,
}

/** System instruction type */
export const enum SystemInstructType {
	// Emoji
	EMOJI = 1,
	// File
	FILE = 2,
	// Topic
	TOPIC = 3,
	// Scheduled task
	TASK = 4,
	// Recording
	RECORD = 5,
}

/** System instruction map */
export type SystemInstructMap = Record<SystemInstructType, string>

/** System instruction */
export interface SystemInstruct extends QuickInstructionBase {
	/** Type */
	type: SystemInstructType
	/** Visibility */
	hidden: boolean
	/** Display type */
	display_type: DisplayType
	/** Icon */
	icon: StatusIconKey
}

/** Quick instruction mode */
export const enum InstructionMode {
	// Flow
	Flow = 1,
	// Chat
	Chat = 2,
}

/** Quick instruction */
export type QuickInstruction =
	| SelectorQuickInstruction
	| SwitchQuickInstruction
	| TextQuickInstruction
	| StatusQuickInstruction
	| SystemInstruct

export type CommonQuickInstruction = Exclude<QuickInstruction, SystemInstruct>

/** Quick instruction group */
export type QuickInstructionList = {
	position: InstructionGroupType
	items: QuickInstruction[]
}
