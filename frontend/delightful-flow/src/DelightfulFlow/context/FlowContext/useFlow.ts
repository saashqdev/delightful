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

// Keep original hook unchanged for backward compatibility
export const useFlow = () => useContext(FlowContext)

// Add specialized hooks to let components subscribe only to the data they need
export const useFlowData = () => useContext(FlowDataContext)

// Get all Flow Edges related state and actions
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

// Get only onEdgesChange method
export const useOnEdgesChange = () => {
	return useContext(FlowEdgesActionsContext).onEdgesChange
}

// Get only onConnect method
export const useOnConnect = () => {
	return useContext(FlowEdgesActionsContext).onConnect
}

// Get all Flow Nodes related state and actions
export const useFlowNodes = () => {
	return useContext(FlowNodesContext)
}

export const useFlowUI = () => useContext(FlowUIContext)

// Get node configuration
export const useNodeConfig = () => {
	const { nodeConfig } = useContext(NodeConfigContext)
	return { nodeConfig }
}

// Get node configuration operation methods
export const useNodeConfigActions = () => useContext(NodeConfigActionsContext)

// Get single node configuration, optimize rendering performance
export const useSingleNodeConfig = (nodeId: string) => {
	const { nodeConfig } = useContext(NodeConfigContext)
	return nodeConfig[nodeId]
}

// Create specific data selector to further reduce unnecessary rendering
export function createFlowSelector<T>(selector: (context: any) => T) {
	return function useFlowSelector() {
		const context = useContext(FlowContext)
		return React.useMemo(() => selector(context), [context])
	}
}

// Create node configuration selector, re-renders only when specific node configuration changes
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

// Get only add node method
export const useAddNode = () => {
	return useContext(FlowNodesActionsContext).addNode
}

// Get only delete nodes method  
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
