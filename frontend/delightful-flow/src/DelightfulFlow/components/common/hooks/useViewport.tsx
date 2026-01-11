import { useFlowUI, useNodeConfig } from "@/DelightfulFlow/context/FlowContext/useFlow"
import { useResize } from "@/DelightfulFlow/context/ResizeContext/useResize"
import { useMemoizedFn } from "ahooks"
import _ from "lodash"
import { useReactFlow } from "reactflow"
import { controlDuration } from "../../FlowDesign/hooks/useFlowControls"
import { MaterialPanelWidth } from "../../FlowMaterialPanel"

export default function useViewport() {
	const { nodeConfig } = useNodeConfig()
	const { showMaterialPanel } = useFlowUI()
	const { setViewport } = useReactFlow()

	const { windowSize } = useResize()

	// Update current viewport to point to target node center
	const updateViewPortToTargetNode = useMemoizedFn((currentNode) => {
		const materialPanelWidth = showMaterialPanel ? MaterialPanelWidth : 0

		const cloneCurrentNode = _.cloneDeep(currentNode)
		// Handle when adding node inside loop body, position = loop body internal position + loop body position
		if (cloneCurrentNode?.meta?.parent_id) {
			const parentLoopBodyNode = nodeConfig?.[cloneCurrentNode?.meta?.parent_id]
			if (parentLoopBodyNode) {
				cloneCurrentNode.position = {
					x: cloneCurrentNode?.position.x + parentLoopBodyNode?.position?.x,
					y: cloneCurrentNode?.position.y + parentLoopBodyNode?.position?.y,
				}
			}
		}

		console.log(
			-cloneCurrentNode?.position?.x -
				cloneCurrentNode?.width / 2 +
				(windowSize.width - materialPanelWidth) / 2,
			cloneCurrentNode,
			windowSize,
		)

		// Position to validation failed node
		setViewport(
			{
				x:
					-cloneCurrentNode?.position?.x -
					cloneCurrentNode?.width / 2 +
					(windowSize.width - materialPanelWidth) / 2,
				y:
					-cloneCurrentNode?.position?.y -
					cloneCurrentNode?.height / 2 +
					windowSize.height / 2,
				zoom: 1,
			},
			{
				duration: controlDuration,
			},
		)
	})

	return {
		updateViewPortToTargetNode,
	}
}

