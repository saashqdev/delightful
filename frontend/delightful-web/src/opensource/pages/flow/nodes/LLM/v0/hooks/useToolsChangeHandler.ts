import type { ToolSelectedItem } from "@/opensource/pages/flow/components/ToolsSelect/types"
import { useFlow } from "@bedelightful/delightful-flow/dist/DelightfulFlow/context/FlowContext/useFlow"
import { useCurrentNode } from "@bedelightful/delightful-flow/dist/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { useMemoizedFn } from "ahooks"
import { set, map, merge } from "lodash-es"

export default function useToolsChangeHandlerV0() {
	const { currentNode } = useCurrentNode()

	const { updateNodeConfig } = useFlow()

	// Handle tools change
	const handleToolsChanged = useMemoizedFn((changeValues) => {
		if (!currentNode) return
		const oldTools = currentNode?.params?.option_tools || []
		const newToolIds = changeValues?.option_tools
			?.map?.((tool: ToolSelectedItem) => tool.tool_id)
			?.filter?.((tool: string) => !!tool)
		const isToolsEmpty = changeValues.option_tools.length === 0
		// When id exists or list is empty, it means add or delete, not modify
		if (newToolIds.length > 0 || isToolsEmpty) {
			set(currentNode, ["params", "option_tools"], changeValues.option_tools)
		} else {
			const mergedTools = map(oldTools, (oldToolItem, index) => {
				return merge({}, oldToolItem, { ...changeValues?.option_tools?.[index] })
			})
			set(currentNode, ["params", "option_tools"], mergedTools)
		}
		// Trigger rerender
		updateNodeConfig(currentNode)
	})

	return {
		handleToolsChanged,
	}
}





