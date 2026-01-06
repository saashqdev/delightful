import type { WidgetValue } from "@/opensource/pages/flow/common/Output"
import type { LLMLabelTagType } from "@/opensource/pages/flow/nodes/LLM/v0/components/LLMSelect/LLMLabel"
import type { TriggerType } from "@/opensource/pages/flow/nodes/Start/v0/constants"
import type { Expression } from "@delightful/delightful-flow/DelightfulConditionEdit/types/expression"
import type { NodeTestingCtx } from "@delightful/delightful-flow/DelightfulFlow/context/NodeTesingContext/Context"
import type { DelightfulFlow } from "@delightful/delightful-flow/DelightfulFlow/types/flow"
import type Schema from "@delightful/delightful-flow/DelightfulJsonSchemaEditor/types/Schema"
import type { Dayjs } from "dayjs"

/** Flow type */
export const enum FlowType {
	/** Main flow */
	Main = 1,
	/** Sub-flow */
	Sub = 2,
	/** Toolset */
	Tools = 3,
}

export interface WithPage<ListType> {
	page: number
	page_size: number
	list: ListType
	total: number
}

export type GetFlowListParams = {
	type: FlowType
	page?: number
	pageSize?: number
	name?: string
}
export interface TriggerConfig {
	trigger_type: TriggerType
	trigger_data: {
		nickname?: string
		chat_time?: string | Dayjs
		message_type?: string
		content?: string
		open_time?: string | Dayjs
	}
	conversation_id?: string
	[key: string]: any
}

// If节点的单条分直播hi
export interface IfBranch {
	branch_id: string
	next_nodes: string[]
	parameters: {
		id: string
		version: string
		type: string
		structure: Expression.Condition | undefined
	}
}

export type Widget<Structure> = {
	id: string
	version: string
	type: string
	structure: Structure
}

export namespace HTTP {
	export type Api = Widget<{
		method: string
		domain: string
		path: string
		uri: string
		url: string
		proxy: string
		request: {
			params_query: Widget<Schema>
			params_path: Widget<Schema>
			body_type: string
			body: Widget<Schema>
			headers: Widget<Schema>
		}
	}>

	export type Params = {
		api: Api
		quick_auth: string
	}
}

export interface NodeTestingResult {
	success: boolean
	start_time: number
	end_time: number
	elapsed_time: string
	error_message: string
	params: Pick<DelightfulFlow.Node, "params">
	input: string[]
	output: {
		content: string
	}
}

export type TestNodeParams = Pick<
	DelightfulFlow.Node,
	"params" | "input" | "node_type" | "node_version"
> & {
	trigger_config: {
		node_contexts: Record<string, any>
		global_variable: Record<string, any>
	}
}

/**
 * LLM model configuration
 */
export interface LLMModalOption {
	value: string
	label: string
	tags: [
		{
			type: LLMLabelTagType.Text
			value: string
		},
		{
			type: LLMLabelTagType.Icon
			value: string
		},
	]
	configs: {
		temperature: number
	}
	icon: string
	vision: boolean
}

/** platform */

export type PlatformItem = {
	id: string
	name: string
	avatar: string
}

/** Sub-flow input/output parameter data format */
export type SubFlowArgument = Pick<
	DelightfulFlow.Flow,
	"id" | "name" | "description" | "enabled" | "type"
> & {
	input: DelightfulFlow.Node["input"]
	output: DelightfulFlow.Node["input"]
	custom_system_input: WidgetValue["value"]
	icon: string
}

export type TestResult = {
	key: string
	success: boolean
	node_debug: NodeTestingCtx["testingResultMap"]
}

export namespace FlowDraft {
	export type Detail = {
		id: string
		name: string
		description: string
		creator: string
		created_at: string
		modifier: string
		updated_at: string
		delightful_flow: DelightfulFlow.Flow
		creator_info: PlatformItem
		modifier_info: PlatformItem
	}

	export type RequestArgs = Partial<Pick<Detail, "name" | "description" | "delightful_flow" | "id">>

	export type ListItem = Detail
}

export namespace FlowTool {
	export type Tool = {
		id?: string
		tool_set_id: string
		code: string
		name: string
		description: string
		icon: string
		enabled: boolean
	}
	export type Detail = {
		id: string
		name: string
		icon: string
		description: string
		creator: string
		created_at: string
		modifier: string
		updated_at: string
		tools: Tool[]
		tool_set_id: string
		agent_used_count: number
	}

	export type ListItem = Detail

	export type GetToolListParams = {
		page: number
		pageSize: number
		name: string
	}

	export type SaveToolParams = {
		id?: string
		name: string
		description: string
		icon: string
		enabled?: boolean
	}
}

// Available toolsets
export namespace UseableToolSet {
	export type UsableTool = {
		code: string
		name: string
		description: string
		input: WidgetValue["value"]
		output: WidgetValue["value"]
		custom_system_input: WidgetValue["value"]
	}

	export type Item = Omit<FlowTool.Detail, "tools"> & {
		tools: UsableTool[]
	}
}

export type ApiKey = {
	id: string
	flow_code: string
	type: string
	name: string
	description: string
	secret_key: string
	conversation_id: string
	webhook_url: string
	enabled: boolean
	last_used: string
	creator: string
	created_at: string
	modifier: string
	updated_at: string
	rel_code?: string
	rel_type?: Flow.ApiKeyType
}

export type NewKeyForm = {
	name: string
	description: string
}

export type ApiKeyRequestParams = Pick<ApiKey, "name" | "description" | "id"> & {
	conversation_id: string
}

/**
 * Component type
 */
export enum ComponentTypes {
	/** Form component */
	Form = "form",
	/** Widget component */
	Widget = "widget",
	/** Condition component */
	Condition = "condition",
	/** API component */
	Api = "api",
	/** Value component */
	Value = "value",
}

/**
 * Single-step debug reference value resolution rule
 */
export type ResolveRule = {
	// Type to resolve
	type: string // schema | expression
	// Parameter path for resolution
	path: string[]
	// Special resolution type
	paramsType?: string
	// Internal value keys for special resolution type
	subKeys?: string[]
}

export enum FlowRouteType {
	Agent = "agent",
	Sub = "sub",
	Tools = "tools",
	VectorKnowledge = "knowledge",
	Mcp = "mcp",
}

export namespace Flow {
	export interface VLMModel {
		id: string
		name: string
		model_id: string
		model_version: string
		model_type: number
		category: string
		icon: string
		description: string
	}

	export interface VLMProvider {
		id: string
		name: string
		models: VLMModel[]
	}

	export enum VLMModelType {
		/** Text-to-image */
		TextToImage = 0,
		/** Image-to-image */
		ImageToImage = 1,
		/** Image enhancement */
		ImageEnhance = 2,
		/** Large language model */
		LLM = 3,
		/** Embedding */
		Embedding = 4,
	}

	export namespace Mcp {
		export type Detail = {
			created_at: string
			created_uid: null
			creator: string
			creator_info: PlatformItem
			description: string
			enabled: boolean
			id: string
			mcp_server_code: string
			modifier: string
			modifier_info: PlatformItem
			name: string
			options: object
			rel_code: string
			rel_version_code: string
			source: number
			updated_at: string
			updated_uid: null
			version: string
			source_version: {
				latest_version_code: string
				latest_version_name: string
			}
			[property: string]: any
		}
		export type ListItem = Mcp.Detail
		export type SaveParams = {
			id?: string
			source?: Flow.Mcp.ToolSource
			rel_code?: string
			rel_version_code?: string
			name?: string
			description?: string
			enabled?: boolean
			rel_info?: Record<string, any>
			icon?: string
		}
		export type GetListParams = {
			page: number
			pageSize: number
			name: string
		}
		export enum ToolSource {
			/** Toolset */
			Toolset = 1,
		}
	}

	export enum ApiKeyType {
		None = 0,
		Flow = 1,
		Mcp = 2,
	}

	export type ApiKeyRequestParamsV1 = {
		id?: string
		name?: string
		description?: string
		rel_type?: ApiKeyType
		rel_code?: string
	}
}

/**
 * Vector knowledge base
 */
export namespace VectorKnowledge {
	export enum SearchType {
		/** All */
		All = 1,
		/** Enabled */
		Enabled = 2,
		/** Disabled */
		Disabled = 3,
	}
}
