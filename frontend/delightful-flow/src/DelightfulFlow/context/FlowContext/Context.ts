import { DelightfulFlow } from "@/DelightfulFlow/types/flow"
import React from "react"
import { Edge } from "reactflow"
import { EventEmitter } from "ahooks/lib/useEventEmitter"
import { BatchProcessingOptions } from "@/DelightfulFlow/hooks/useNodeBatchProcessing"

// Split into multiple specialized Context types
// Flow data related
export type FlowDataCtx = {
    flow: DelightfulFlow.Flow | null
    description: string
    debuggerMode: boolean
    updateFlow: (this: any, flowConfig: any) => void
}

// Edge-related state
export type FlowEdgesStateType = {
    edges: Edge[]
    selectedEdgeId: string | null
}

// Edge-related actions
export type FlowEdgesActionsType = {
    onEdgesChange: (this: any, changes: any) => void
    onConnect: (this: any, connection: any) => void
    setEdges: React.Dispatch<React.SetStateAction<Edge[]>>
    setSelectedEdgeId: React.Dispatch<React.SetStateAction<string | null>>
    updateNextNodeIdsByDeleteEdge: (connection: Edge) => void
    updateNextNodeIdsByConnect: (newEdge: Edge) => void
    deleteEdges: (edgesToDelete: Edge[]) => void
}

// Edge-related
export type FlowEdgesCtx = FlowEdgesStateType & FlowEdgesActionsType

// Edge-related state Context
export const FlowEdgesStateContext = React.createContext<FlowEdgesStateType>({
    edges: [] as Edge[],
    selectedEdgeId: null,
})

// Edge-related actions Context
export const FlowEdgesActionsContext = React.createContext<FlowEdgesActionsType>({
    onEdgesChange: () => {},
    onConnect: () => {},
    setEdges: () => {},
    setSelectedEdgeId: () => {},
    updateNextNodeIdsByDeleteEdge: () => {},
    updateNextNodeIdsByConnect: () => {},
    deleteEdges: () => {},
})

// Add NodeConfigContext for managing node configuration data
export type NodeConfigCtx = {
	// Node configuration
	nodeConfig: Record<string, any>
}

export const NodeConfigContext = React.createContext({
	nodeConfig: {},
} as NodeConfigCtx)

// Add NodeConfigActionsContext to store all operation methods
export type NodeConfigActionsCtx = {
	// Set node configuration
	setNodeConfig: React.Dispatch<React.SetStateAction<Record<string, any>>>
	// Update node configuration
	updateNodeConfig: (node: DelightfulFlow.Node, originalNode?: DelightfulFlow.Node) => void
	// Notify node change
	notifyNodeChange: (nodeId?: string) => void
}

export const NodeConfigActionsContext = React.createContext({
	setNodeConfig: () => {},
	updateNodeConfig: () => {},
	notifyNodeChange: () => {},
} as NodeConfigActionsCtx)

// Split FlowNodesCtx into state and actions parts
export type FlowNodesStateType = {
  selectedNodeId: string
  triggerNode: any | null
}

export type FlowNodesActionsType = {
    addNode: (node: DelightfulFlow.Node | DelightfulFlow.Node[], meta?: any) => void
    deleteNodes: (nodeIds: string[]) => void
    updateNodesPosition: (nodeId: string[], position: Record<string,{ x: number; y: number }>) => void
    setSelectedNodeId: (id: string) => void
    getNewNodeIndex: () => number
    processNodesBatch: (allNodes: any[], processCallback: (nodes: any[]) => void, customOptions?: Partial<BatchProcessingOptions>) => () => void
}

export type FlowNodesCtx = FlowNodesStateType & FlowNodesActionsType

// State Context
export const FlowNodesStateContext = React.createContext<FlowNodesStateType>({
  selectedNodeId: "",
  triggerNode: null,
})

// Actions Context
export const FlowNodesActionsContext = React.createContext<FlowNodesActionsType>({
  addNode: () => {},
  deleteNodes: () => {},
  updateNodesPosition: () => {},
  setSelectedNodeId: () => {},
  getNewNodeIndex: () => 0,
  processNodesBatch: () => () => {},
})

// Preserve original Context for backward compatibility
export const FlowNodesContext = React.createContext({
  addNode: () => {},
  deleteNodes: () => {},
  updateNodesPosition: () => {},
  selectedNodeId: "",
  setSelectedNodeId: () => {},
  triggerNode: null,
  getNewNodeIndex: () => 0,
  processNodesBatch: () => () => {},
} as FlowNodesCtx)

// UI state related
export type FlowUICtx = {
    flowInstance: React.MutableRefObject<any>
    showMaterialPanel: boolean
    setShowMaterialPanel: React.Dispatch<React.SetStateAction<boolean>>
    flowDesignListener: EventEmitter<DelightfulFlow.FlowEventListener>
}

// Original complete Context type
export type FlowCtx = React.PropsWithChildren<
    FlowDataCtx &
        FlowEdgesCtx &
        FlowNodesCtx &
        FlowUICtx &
        NodeConfigCtx &
        NodeConfigActionsCtx
>  

// Create separated Contexts
export const FlowDataContext = React.createContext<FlowDataCtx>({
    flow: null,
    description: "",
    debuggerMode: false,
    updateFlow: () => {},
})

// Preserve original FlowEdgesContext for backward compatibility
export const FlowEdgesContext = React.createContext<FlowEdgesCtx>({
    edges: [] as Edge[],
    onEdgesChange: () => {},
    onConnect: () => {},
    setEdges: () => {},
    selectedEdgeId: null,
    setSelectedEdgeId: () => {},
    updateNextNodeIdsByDeleteEdge: () => {},
    updateNextNodeIdsByConnect: () => {},
    deleteEdges: () => {},
})

export const FlowUIContext = React.createContext<FlowUICtx>({
    flowInstance: null as any,
    showMaterialPanel: true,
    setShowMaterialPanel: () => {},
    flowDesignListener: {
        emit: () => {},
        useSubscription: () => {}
    } as any,
})

// Preserve original FlowContext for compatibility
export const FlowContext = React.createContext({} as FlowCtx)
