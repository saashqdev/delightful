import type { ToolSelectedItem } from "@/opensource/pages/flow/components/ToolsSelect/types"
import { useFlowStore } from "@/opensource/stores/flow"
import { useMemoizedFn } from "ahooks"
import { cloneDeep, set } from "lodash-es"
import type { UseableToolSet } from "@/types/flow"
import { ComponentTypes } from "@/types/flow"
import { useCurrentNode } from "@bedelightful/delightful-flow/dist/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { genDefaultComponent } from "@/opensource/pages/flow/utils/helpers"

/**
 * Handling logic for legacy tools
 */
export default function useOldToolsHandleV0() {
	const { currentNode } = useCurrentNode()
	// Get all available toolsets
	const { useableToolSets } = useFlowStore()

	const findTargetToolSetByToolId = useMemoizedFn((toolId: string) => {
		return useableToolSets?.find?.((toolSet) => {
			return !!toolSet.tools?.find((t) => t.code === toolId)
		}) as UseableToolSet.Item
	})

	// Compatibility handling for legacy data
	const handleOldTools = useMemoizedFn(
		(params: Record<string, any>, oldKey = "tools", newKey = "option_tools") => {
			const cloneParams = cloneDeep(params)
			const oldTools = cloneParams?.[oldKey] || []
			const newTools = cloneParams[newKey] || []
			// If new tool list is empty and old list is not, use old list values as the new list
			if (newTools?.length === 0 && oldTools?.length > 0) {
				cloneParams[newKey] = cloneParams?.[oldKey]?.map(
					(tool: string | ToolSelectedItem) => {
						if (typeof tool === "string") {
							return {
								tool_id: tool,
								tool_set_id: findTargetToolSetByToolId(tool)?.id,
								async: false,
								custom_system_input: {
									form: genDefaultComponent(ComponentTypes.Form),
									widget: null,
								},
							} as ToolSelectedItem
						}
						return tool
					},
				)
				if (currentNode) set(currentNode, ["params", "option_tools"], cloneParams[newKey])
			}
			return cloneParams
		},
	)

	return {
		handleOldTools,
	}
}





