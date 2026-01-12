import { useExternalConfig } from '@/DelightfulFlow/context/ExternalContext/useExternal'
import { useFlowEdges, useNodeConfig, useNodeConfigActions } from '@/DelightfulFlow/context/FlowContext/useFlow'
import { useMemoizedFn } from 'ahooks'
import { useFlowInteractionActions } from '../../FlowDesign/context/FlowInteraction/useFlowInteraction'
import { generateLoopBody, getLatestNodeVersion, getNodeSchema, handleRenderProps, judgeLoopNode, searchLoopRelationNodesAndEdges } from '@/DelightfulFlow/utils'
import { WidgetValue } from '@/DelightfulFlow/examples/BaseFlow/common/Output'
import { useCurrentNode } from '@/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode'
import { InnerHandleType } from '@/DelightfulFlow/nodes'
import _ from 'lodash'
import { useNodes } from '@/DelightfulFlow/context/NodesContext/useNodes'

type UsePopupActionProps = {
	// Target node id when changing node type
	targetNodeId?: string
}

export default function usePopupAction({ targetNodeId }: UsePopupActionProps) {

    const { nodeConfig } = useNodeConfig()

    const { setNodeConfig, updateNodeConfig } = useNodeConfigActions()

	const { setEdges, edges } = useFlowEdges()

	const { nodes, setNodes } = useNodes()

	const { paramsName } = useExternalConfig()

	const { resetLastLayoutData } = useFlowInteractionActions()

	const { currentNode } = useCurrentNode()


	// Switch node type
	const toggleType = useMemoizedFn(({ key: targetNodeType }) => {
		const node = nodeConfig[targetNodeId as string]

		const nodeSchema = getNodeSchema(targetNodeType)

		const resultData = {
			...node,
			// Carry over existing node output
			output: nodeSchema?.output as WidgetValue['value'],
			// Carry over existing node input
			input: nodeSchema?.input as WidgetValue['value'],
			// Update node type
			[paramsName.nodeType]: targetNodeType,
			// Reset node config
			[paramsName.params]: _.cloneDeep(nodeSchema.params),
			name: nodeSchema.label,
            node_version: getLatestNodeVersion(nodeSchema.id),
            data: {
                icon: null
            }
		}
		
		handleRenderProps(resultData, resultData.step, paramsName)

		updateNodeConfig(resultData)

		const sourceNodeType = currentNode?.[paramsName.nodeType]

		// If switching from a regular node to a loop node
		if (judgeLoopNode(targetNodeType) && !judgeLoopNode(sourceNodeType)) {
			// Check whether the current node has a downstream node
			const toNextEdge = edges.find(e => e.source === node.id)
			const { newNodes: bodyNodes, newEdges: bodyEdges } = generateLoopBody(
				resultData,
				paramsName,
				edges,
			)
			// If a downstream node exists, reroute the sourceHandle to the loop body's next-node handle
			if(!!toNextEdge) {
				toNextEdge.sourceHandle = InnerHandleType.LoopNext
			}
			// Attach loop body nodes to nodeConfig
			bodyNodes.forEach(bodyNode => {
				nodeConfig[bodyNode.id] = bodyNode
			})
			setNodes([...nodes, ...bodyNodes])
			setEdges([...edges, ...bodyEdges])
			setNodeConfig({...nodeConfig})
		} else if(judgeLoopNode(sourceNodeType) && !judgeLoopNode(targetNodeType)) {
			// If switching from a loop node back to a regular node, remove the loop body and related nodes/edges
			const { nodeIds, edgeIds } = searchLoopRelationNodesAndEdges(node, nodes, edges)
			console.log("Loop-related edges", edgeIds)
			console.log("Loop-related nodes", nodeIds)
			// Remove loop body nodes from nodeConfig
			nodeIds.forEach(nId => {
				delete nodeConfig[nId]
			})
			setNodeConfig({...nodeConfig})
			setNodes(nodes.filter(n => !nodeIds.includes(n.id)))
			setEdges(edges.filter(e => !edgeIds.includes(e.id)))
		}
		else {
			setNodes([...nodes])
		}

		// Reset layout cache
		resetLastLayoutData()
	})

	return {
		toggleType
	}
}

