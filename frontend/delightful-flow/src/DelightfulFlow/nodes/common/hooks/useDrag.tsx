/**
 * Define drag-over and drag-leave handlers for nodes
 */
import { FLOW_EVENTS, flowEventBus } from "@/common/BaseUI/Select/constants"
import { useFlowInteractionActions } from "@/DelightfulFlow/components/FlowDesign/context/FlowInteraction/useFlowInteraction"
import { useExternalConfig } from "@/DelightfulFlow/context/ExternalContext/useExternal"
import { useNodeConfig } from "@/DelightfulFlow/context/FlowContext/useFlow"
import { judgeIsLoopBody } from "@/DelightfulFlow/utils"
import { getSubNodePosition } from "@/DelightfulFlow/utils/reactflowUtils"
import { useMemoizedFn } from "ahooks"
import { useMemo } from "react"
import { useReactFlow } from "reactflow"

type UseDrag = {
	id: string
}

export default function useDrag({ id }: UseDrag) {
	const { nodeConfig } = useNodeConfig()
	const { paramsName } = useExternalConfig()
	const { onAddItem } = useFlowInteractionActions()
	const { screenToFlowPosition } = useReactFlow()

	const currentNode = useMemo(() => {
		return nodeConfig[id]
	}, [nodeConfig, id])

	const isGroupType = useMemo(() => {
		return judgeIsLoopBody(currentNode?.[paramsName.nodeType])
	}, [currentNode])

	const selectNode = useMemoizedFn((nodeId: string) => {
		flowEventBus.emit(FLOW_EVENTS.NODE_SELECTED, nodeId)
	})

	const onDragOver = useMemoizedFn(() => {
		/** If this node is a group node, select the group */
		if (isGroupType) {
			selectNode(id)
			return
		}
		/** For a child node, select its parent group */
		if (currentNode?.parentId) {
			selectNode(currentNode?.parentId)
		}
	})

	const onDragLeave = useMemoizedFn(() => {
		if (isGroupType || currentNode?.parentId) {
			selectNode(null)
		}
	})

	/** Add a child node under a group */
	const addChildNode = useMemoizedFn((event, pid) => {
		event.stopPropagation()
		const jsonString = event.dataTransfer.getData("node-data")
		const parentNode = nodeConfig?.[pid]

		const position = getSubNodePosition(event, screenToFlowPosition, parentNode)

		if (jsonString) {
			const nodeSchema = JSON.parse(jsonString)
			onAddItem(event, nodeSchema, {
				parentId: pid,
				expandParent: true,
				extent: "parent",
				meta: {
					position,
					parent_id: pid,
				},
			})
		}
	})

	const onDrop = useMemoizedFn((event) => {
		if (isGroupType) {
			addChildNode(event, id)
			return
		}
		if (currentNode?.parentId) {
			addChildNode(event, currentNode?.parentId)
		}
	})

	return {
		onDragOver,
		onDragLeave,
		onDrop,
	}
}
