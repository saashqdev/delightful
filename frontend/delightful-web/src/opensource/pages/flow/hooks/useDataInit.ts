import { useFlowStore } from "@/opensource/stores/flow"
import { transformDataSource } from "@bedelightful/delightful-flow/dist/DelightfulExpressionWidget/helpers"
import { FlowType } from "@/types/flow"
import { useMemoizedFn, useMount } from "ahooks"
import type { DelightfulFlow } from "@bedelightful/delightful-flow/dist/DelightfulFlow/types/flow"
import { useEffect } from "react"
import { FlowApi } from "@/apis"
import { customNodeType } from "../constants"
import type { ToolSelectedItem } from "../components/ToolsSelect/types"

type UseDataInitProps = {
	currentFlow?: DelightfulFlow.Flow
}

/**
 * Data that needs to be initialized when the flow is first loaded
 */
export default function useDataInit({ currentFlow }: UseDataInitProps) {
	const {
		updateUseableToolSets,
		updateModels,
		updateUseableDatabases,
		updateSubFlowList,
		updateMethodDataSource,
		updateToolInputOutputMap,
	} = useFlowStore()

	const initMethodsDataSource = useMemoizedFn(async () => {
		const { expression_data_source } = await FlowApi.getMethodsDataSource()
		// Further transform the data source into a structure recognizable by the flow
		const methodsOptions = transformDataSource(expression_data_source)
		updateMethodDataSource(methodsOptions)
	})

	const initSubFlows = useMemoizedFn(async () => {
		const subFlowResponse = await FlowApi.getFlowList({ type: FlowType.Sub })

		if (subFlowResponse.list) {
			updateSubFlowList(subFlowResponse.list)
		}
	})

	const initUseableToolSets = useMemoizedFn(async () => {
		try {
			const response = await FlowApi.getUseableToolList()
			console.log("FlowApi.getUseableToolList()", response)
			if (response && response.list) {
				updateUseableToolSets(response.list)
			}
		} catch (e) {
			console.log("initUseableToolSets error", e)
		}
	})

	const initUseableDatabases = useMemoizedFn(async () => {
		FlowApi.getUseableDatabaseList().then((response) => {
			if (response && response.list) {
				updateUseableDatabases(response.list)
			}
		})
	})

	const initModels = useMemoizedFn(async () => {
		const { models } = await FlowApi.getLLMModal()
		updateModels(models)
	})

	// Initialize templates for configured tools
	const initToolInputOutputMap = useMemoizedFn(async () => {
		// Tools configured in all LLM nodes
		const llmNodeToolIds =
			currentFlow?.nodes
				?.filter?.((node) => node.node_type === customNodeType.LLM)
				?.reduce?.((ids, node) => {
					const optionTools = node?.params?.option_tools as ToolSelectedItem[]
					const currentNodeToolIds = optionTools?.map?.((tool) => tool.tool_id)
					return [...new Set([...ids, ...currentNodeToolIds])]
				}, [] as string[]) || []
		// Tools configured in tool nodes
		const toolsNodeToolIds =
			currentFlow?.nodes
				?.filter?.((node) => node.node_type === customNodeType.Tools)
				?.reduce((ids, node) => {
					const toolId = node?.params?.tool_id as string
					return [...new Set([...ids, toolId])]
				}, [] as string[]) || []
		const flowToolIds = [...new Set([...llmNodeToolIds, ...toolsNodeToolIds])]
		if (flowToolIds.length > 0) {
			const response = await FlowApi.getAvailableTools(flowToolIds)
			if (response.list) {
				const map = response.list.reduce((toolsMap, currentTool) => {
					if (!currentTool?.id) return toolsMap
					toolsMap[currentTool.id] = currentTool
					return toolsMap
				}, {} as Record<string, DelightfulFlow.Flow>)
				updateToolInputOutputMap(map)
			}
		}
	})

	const initData = useMemoizedFn(async () => {
		initUseableToolSets()
		initModels()
		initUseableDatabases()
		initSubFlows()
		initMethodsDataSource()
	})

	useMount(() => {
		initData()
	})

	useEffect(() => {
		if (currentFlow) {
			initToolInputOutputMap()
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [currentFlow])
}





