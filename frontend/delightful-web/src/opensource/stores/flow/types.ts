import type { DelightfulFlow } from "@bedelightful/delightful-flow/DelightfulFlow/types/flow"
import type { FlowDraft, LLMModalOption, UseableToolSet, Flow } from "@/types/flow"
import type { Knowledge } from "@/types/knowledge"
import type { DataSourceOption } from "@bedelightful/delightful-flow/common/BaseUI/DropdownRenderer/Reference"
import type { useFlowStore } from "."

export interface FlowState {
	subFlows: DelightfulFlow.Flow[]
	draftList: FlowDraft.ListItem[]
	publishList: FlowDraft.ListItem[]
	isGlobalVariableChanged: boolean
	toolInputOutputMap: Record<string, DelightfulFlow.Flow>
	useableToolSets: UseableToolSet.Item[]
	models: LLMModalOption[]
	useableDatabases: Knowledge.KnowledgeItem[]
	useableTeamshareDatabase: Knowledge.KnowledgeDatabaseItem[]
	methodsDataSource: DataSourceOption[]
	visionModels: Flow.VLMProvider[]
}

export type FlowStore = ReturnType<typeof useFlowStore>
