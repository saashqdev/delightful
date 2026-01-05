import React, { useContext } from "react"
import {
	FlowContext,
	FlowDataContext,
	FlowEdgesContext,
	FlowEdgesStateContext,
	FlowEdgesActionsContext,
	FlowNodesActionsContext,
	FlowNodesContext,
	FlowNodesStateContext,
	FlowUIContext,
	NodeConfigActionsContext,
	NodeConfigContext,
	FlowNodesStateType,
	FlowNodesActionsType,
	FlowEdgesStateType,
	FlowEdgesActionsType
} from "./Context"

// Keep original hook for backward compatibility
export const useFlow = () => useContext(FlowContext)

// Dedicated hooks so components subscribe only to what they need
export const useFlowData = () => useContext(FlowDataContext)

// Get all Flow Edges state and actions
export const useFlowEdges = () => useContext(FlowEdgesContext)

// Get Flow Edges state
export const useFlowEdgesState = (): FlowEdgesStateType => {
	return useContext(FlowEdgesStateContext)
}

// Get Flow Edges actions
export const useFlowEdgesActions = (): FlowEdgesActionsType => {
	return useContext(FlowEdgesActionsContext)
}

// Get only edges data
export const useEdges = () => {
	return useContext(FlowEdgesStateContext).edges
}

// Get only selectedEdgeId
export const useSelectedEdgeId = () => {
	const { selectedEdgeId } = useContext(FlowEdgesStateContext)
	const { setSelectedEdgeId } = useContext(FlowEdgesActionsContext)
	return { selectedEdgeId, setSelectedEdgeId }
}

// Get only onEdgesChange
export const useOnEdgesChange = () => {
	return useContext(FlowEdgesActionsContext).onEdgesChange
}

// Get only onConnect
export const useOnConnect = () => {
	return useContext(FlowEdgesActionsContext).onConnect
}

// Get all Flow Nodes state and actions
export const useFlowNodes = () => {
	return useContext(FlowNodesContext)
}

export const useFlowUI = () => useContext(FlowUIContext)

// Get node configuration
export const useNodeConfig = () => {
	const { nodeConfig } = useContext(NodeConfigContext)
	return { nodeConfig }
}

// Get node configuration mutation helpers
export const useNodeConfigActions = () => useContext(NodeConfigActionsContext)

// Get a single node configuration to reduce re-renders
export const useSingleNodeConfig = (nodeId: string) => {
	const { nodeConfig } = useContext(NodeConfigContext)
	return nodeConfig[nodeId]
}

// Create a selector hook to further reduce unnecessary renders
export function createFlowSelector<T>(selector: (context: any) => T) {
	return function useFlowSelector() {
		const context = useContext(FlowContext)
		return React.useMemo(() => selector(context), [context])
	}
}

// Create a selector for node config; only re-renders when that node changes
export function createNodeConfigSelector(nodeId: string) {
	return () => {
		const { nodeConfig } = useNodeConfig()
		return nodeConfig[nodeId]
	}
}

// Get only selected node ID
export const useSelectedNodeId = () => {
	return useContext(FlowNodesStateContext).selectedNodeId
}

// Get only triggerNode
export const useTriggerNode = () => {
	return useContext(FlowNodesStateContext).triggerNode
}

// Get only addNode
export const useAddNode = () => {
	return useContext(FlowNodesActionsContext).addNode
}

// Get only deleteNodes  
export const useDeleteNodes = () => {
	return useContext(FlowNodesActionsContext).deleteNodes
}

export const useNodeOperations = () => {
	const { addNode, deleteNodes, updateNodesPosition } = useFlowNodes()
	return { addNode, deleteNodes, updateNodesPosition }
}

export const useMaterialPanel = () => {
	const { showMaterialPanel, setShowMaterialPanel } = useFlowUI()
	return { showMaterialPanel, setShowMaterialPanel }
}
