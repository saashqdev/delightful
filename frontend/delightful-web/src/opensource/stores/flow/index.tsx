import { RequestUrl } from "@/opensource/apis/constant"
import type {
	FlowDraft,
	PlatformItem,
	TestResult,
	TriggerConfig,
	WithPage,
	FlowTool,
	GetFlowListParams,
	UseableToolSet,
	LLMModalOption,
	Flow,
} from "@/types/flow"
import type { DelightfulFlow } from "@bedelightful/delightful-flow/DelightfulFlow/types/flow"
import type { SWRResponse } from "swr"
import useSWR from "swr"
import { create } from "zustand"
import type { Knowledge } from "@/types/knowledge"
import type { DataSourceOption } from "@bedelightful/delightful-flow/common/BaseUI/DropdownRenderer/Reference"
import { FlowApi } from "@/apis"
import type { FlowState } from "./types"

const defaultState: FlowState = {
	subFlows: [],
	draftList: [],
	publishList: [],
	isGlobalVariableChanged: false,
	toolInputOutputMap: {},
	useableToolSets: [],
	models: [],
	useableDatabases: [],
	useableTeamshareDatabase: [],
	methodsDataSource: [],
	visionModels: [], // Add visual understanding model array
}

export interface FlowStoreState {
	subFlows: DelightfulFlow.Flow[]
	draftList: FlowDraft.ListItem[]
	publishList: FlowDraft.ListItem[]
	useableToolSets: UseableToolSet.Item[]
	models: LLMModalOption[]
	useableDatabases: Knowledge.KnowledgeItem[]
	useableTeamshareDatabase: Knowledge.KnowledgeDatabaseItem[]
	methodsDataSource: DataSourceOption[]
	toolInputOutputMap: Record<string, DelightfulFlow.Flow>
	visionModels: Flow.VLMProvider[] // Use type from Flow namespace
	updateVisionModels: (visionModels: Flow.VLMProvider[]) => void // Use type from Flow namespace
	updateToolInputOutputMap: (toolInputOutputMap: Record<string, DelightfulFlow.Flow>) => void
	updateMethodDataSource: (dataSource: DataSourceOption[]) => void
	updateSubFlowList: (flows: DelightfulFlow.Flow[]) => void
	updateUseableTeamshareDatabase: (databases: Knowledge.KnowledgeDatabaseItem[]) => void
	updateUseableDatabases: (databases: Knowledge.KnowledgeItem[]) => void
	updateModels: (models: LLMModalOption[]) => void
	updateUseableToolSets: (toolSets: UseableToolSet.Item[]) => void
	useFlowList: (params: GetFlowListParams) => SWRResponse<WithPage<DelightfulFlow.Flow[]>>
	updateFlowDraftList: (flowList: FlowDraft.ListItem[]) => void
	updateFlowPublishList: (flowList: FlowDraft.ListItem[]) => void
	useFlowDetail: (flowId: string) => SWRResponse<DelightfulFlow.Flow>
	useTestFlow: (
		flow: DelightfulFlow.Flow & { trigger_config: TriggerConfig & { debug: boolean } },
	) => SWRResponse<TestResult>
	useGetOpenApiAccountList: (flowId: string) => SWRResponse<WithPage<PlatformItem[]>>
	useGetOpenPlatformAccountsOfMine: () => SWRResponse<WithPage<PlatformItem[]>>
	useFlowToolList: (
		params: FlowTool.GetToolListParams,
	) => SWRResponse<WithPage<DelightfulFlow.Flow[]>>
	isGlobalVariableChanged: boolean
	updateIsGlobalVariableChanged: (value: boolean) => void
}
export const useFlowStore = create<FlowStoreState>((set) => ({
	...defaultState,
	useFlowList: (params: GetFlowListParams) => {
		return useSWR(RequestUrl.getFlowList, () => FlowApi.getFlowList(params))
	},

	useFlowDetail: (flowId: string) => {
		return useSWR(`${RequestUrl.getFlow}/${flowId}`, () => FlowApi.getFlow(flowId))
	},

	useTestFlow: (
		flow: DelightfulFlow.Flow & { trigger_config: TriggerConfig & { debug: boolean } },
	) => {
		return useSWR(RequestUrl.testFlow, () => FlowApi.testFlow(flow))
	},

	useGetOpenApiAccountList: (flowId: string) => {
		return useSWR(RequestUrl.getOpenApiAccountList, () => FlowApi.getOpenApiAccountList(flowId))
	},

	useGetOpenPlatformAccountsOfMine: () => {
		return useSWR(RequestUrl.getOpenApiAccountList, () => FlowApi.getOpenPlatformOfMine())
	},

	updateFlowDraftList: (flowList: FlowDraft.ListItem[]) => {
		set(() => ({
			draftList: flowList,
		}))
	},

	updateFlowPublishList: (flowList: FlowDraft.ListItem[]) => {
		set(() => ({
			publishList: flowList,
		}))
	},

	useFlowToolList: ({ page, pageSize, name }: FlowTool.GetToolListParams) => {
		return useSWR(RequestUrl.getToolList, () =>
			FlowApi.getToolList({
				page,
				pageSize,
				name,
			}),
		)
	},

	updateIsGlobalVariableChanged: (changed: boolean) => {
		set({
			isGlobalVariableChanged: changed,
		})
	},

	updateUseableToolSets: (toolSets: UseableToolSet.Item[]) => {
		set({
			useableToolSets: toolSets,
		})
	},

	updateModels: (models: LLMModalOption[]) => {
		set({
			models,
		})
	},

	updateUseableDatabases: (databases: Knowledge.KnowledgeItem[]) => {
		set({
			useableDatabases: databases,
		})
	},

	updateUseableTeamshareDatabase: (databases: Knowledge.KnowledgeDatabaseItem[]) => {
		set({
			useableTeamshareDatabase: databases,
		})
	},

	updateSubFlowList: (flowList: DelightfulFlow.Flow[]) => {
		set({
			subFlows: flowList,
		})
	},

	updateMethodDataSource: (dataSource: DataSourceOption[]) => {
		set({
			methodsDataSource: dataSource,
		})
	},

	updateVisionModels: (visionModels: Flow.VLMProvider[]) => {
		set({
			visionModels,
		})
	},

	updateToolInputOutputMap: (toolInputOutputMap: Record<string, DelightfulFlow.Flow>) => {
		set({
			toolInputOutputMap,
		})
	},
}))
