
import { useMemoizedFn } from "ahooks"
import { useFlowData, useFlowEdges, useFlowEdgesActions, useFlowNodes, useNodeConfig, useNodeConfigActions } from "@/DelightfulFlow/context/FlowContext/useFlow"
import { generateSnowFlake } from "@/common/utils/snowflake"
import { useReactFlow, useStoreApi } from "reactflow"
import { useFlowInteractionActions } from "@/DelightfulFlow/components/FlowDesign/context/FlowInteraction/useFlowInteraction"
import useViewport from "@/DelightfulFlow/components/common/hooks/useViewport"
import { nodeManager } from "@/DelightfulFlow/register/node"
import { useExternalConfig } from "@/DelightfulFlow/context/ExternalContext/useExternal"
import { defaultEdgeConfig } from "@/DelightfulFlow/edges"
import { generatePasteNode, judgeIsLoopBody, judgeLoopNode } from "@/DelightfulFlow/utils"
import _ from "lodash"
import { useNodesActions } from "@/DelightfulFlow/context/NodesContext/useNodes"
import { FLOW_EVENTS, flowEventBus } from "@/common/BaseUI/Select/constants"

export const pasteTranslateSize = 20

export default function useToolbar () {

    const { nodeConfig } = useNodeConfig()
    const { setNodeConfig, notifyNodeChange } = useNodeConfigActions()
    const {  updateNextNodeIdsByDeleteEdge, setEdges } = useFlowEdgesActions()
    const { debuggerMode } = useFlowData()
	const { setNodes } = useNodesActions()
	const { layout } = useFlowInteractionActions()
    const { getEdges, getNodes } = useReactFlow()

	const { paramsName } = useExternalConfig()

	const { updateViewPortToTargetNode } = useViewport()

	const store = useStoreApi()

	// Delete a single node
	const deleteNode = useMemoizedFn((id: string) => {
        const edges = getEdges()
        const nodes = Object.values(nodeConfig)
		const deleteIds = _.castArray(id).reduce((acc, nId) => {
			const n = nodeConfig[nId]
			// @ts-ignore
			const nodeType = n[paramsName.nodeType]
			// If deleting a group node, delete its child nodes too
			if(judgeIsLoopBody(nodeType)) {
				const subNodeIds = nodes.filter(_n => _n.parentId === n.id).map(_n => _n.id)
				const result = [...subNodeIds, n.id]
				// If deleting a loop body, delete the loop node as well
				if(n.meta.parent_id) {
					result.push(n.meta.parent_id)
				}
				return result
			}
			// If deleting a loop node, delete the loop body and its nodes
			if(judgeLoopNode(nodeType)) {
				const loopBody = nodes.find(_n => _n.meta.parent_id === n.id)
				if(loopBody) {
					const loopBodyNodeIds = nodes.filter(_n => _n.meta.parent_id === loopBody?.id).map(_n => _n.id)
					return [...loopBodyNodeIds,loopBody.id, n.id]
				}
			}
			return [...acc, n.id]
		}, [] as string[])


		const deleteEdges = edges.filter(e => deleteIds.includes(e.target) || deleteIds.includes(e.source))
		const leaveEdges = edges.filter(e => !deleteIds.includes(e.target) && !deleteIds.includes(e.source))

		// Update edge data
		setEdges(leaveEdges)

		// Update nextNodeIds
		deleteEdges.forEach(e => updateNextNodeIdsByDeleteEdge(e))

		setNodes(nodes.filter(n => !deleteIds.includes(n.id)))

		deleteIds.forEach(id => {
			if (Reflect.has(nodeConfig, id)) {
				Reflect.deleteProperty(nodeConfig, id)
				setNodeConfig({...nodeConfig})
			}
		})

        notifyNodeChange?.()

		if (debuggerMode) {
			console.trace("Deleted node", id)
		}
	})

    const selectNode = useMemoizedFn((nodeId: string) => {
        flowEventBus.emit(FLOW_EVENTS.NODE_SELECTED, nodeId)
    })

	const pasteNode = useMemoizedFn((id) => {
		const storeStates = store.getState()
		const node = nodeConfig[id]
        if(!node) return
		const config = nodeConfig[id]

		const { pasteNode: _pasteNode } = generatePasteNode(node, paramsName)

		const newId = _pasteNode.id

		const newEdge = {
			id: generateSnowFlake(),
			source: id,
			target: newId,
			...defaultEdgeConfig
		}

		/** If the previous node is a branch, add next_nodes inside that branch */
		// TODO Should use configurable param names supporting next_nodes or nextNodes
		if (nodeManager.branchNodeIds.includes(`${node[paramsName.nodeType]}`)) {
			config?.content?.branches?.[0]?.nextNodes?.push?.(newId)
			config?.content?.branches?.[0]?.next_nodes?.push?.(newId)
		}
		config?.nextNodes?.push(newId)
		config?.next_nodes?.push(newId)

        const edges = getEdges()
        const nodes = getNodes()
        setEdges([...edges, newEdge])
        // @ts-ignore
		setNodes([...nodes, _pasteNode])
		nodeConfig[newId] = _pasteNode

		storeStates.unselectNodesAndEdges()

		setTimeout(() => {
			const layoutNodes = layout()
			const currentNode = layoutNodes.find(n => n.node_id === newId)
			updateViewPortToTargetNode(currentNode)
			selectNode(null)
		}, 200)

        notifyNodeChange?.()

	})

	return {
		deleteNode,
		pasteNode
	}
}

