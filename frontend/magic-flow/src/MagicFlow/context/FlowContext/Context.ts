import { MagicFlow } from "@/MagicFlow/types/flow"
import React from "react"
import { Edge } from "reactflow"
import { EventEmitter } from "ahooks/lib/useEventEmitter"
import { BatchProcessingOptions } from "@/MagicFlow/hooks/useNodeBatchProcessing"

// Split into multiple specialized context types
// Flow data
export type FlowDataCtx = {
    flow: MagicFlow.Flow | null
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

// Combined edge context
export type FlowEdgesCtx = FlowEdgesStateType & FlowEdgesActionsType

// Edge state context
export const FlowEdgesStateContext = React.createContext<FlowEdgesStateType>({
    edges: [] as Edge[],
    selectedEdgeId: null,
})

// Edge actions context
export const FlowEdgesActionsContext = React.createContext<FlowEdgesActionsType>({
    onEdgesChange: () => {},
    onConnect: () => {},
    setEdges: () => {},
    setSelectedEdgeId: () => {},
    updateNextNodeIdsByDeleteEdge: () => {},
    updateNextNodeIdsByConnect: () => {},
    deleteEdges: () => {},
})

// NodeConfigContext manages node configuration data
export type NodeConfigCtx = {
    // Node configuration
	nodeConfig: Record<string, any>
}

export const NodeConfigContext = React.createContext({
	nodeConfig: {},
} as NodeConfigCtx)

// NodeConfigActionsContext stores mutation helpers
export type NodeConfigActionsCtx = {
    // Set node configuration
	setNodeConfig: React.Dispatch<React.SetStateAction<Record<string, any>>>
    // Update node configuration
	updateNodeConfig: (node: MagicFlow.Node, originalNode?: MagicFlow.Node) => void
    // Notify about node changes
	notifyNodeChange: (nodeId?: string) => void
}

export const NodeConfigActionsContext = React.createContext({
	setNodeConfig: () => {},
	updateNodeConfig: () => {},
	notifyNodeChange: () => {},
} as NodeConfigActionsCtx)

// Split FlowNodesCtx into state and actions
export type FlowNodesStateType = {
  selectedNodeId: string
  triggerNode: any | null
}

export type FlowNodesActionsType = {
    addNode: (node: MagicFlow.Node | MagicFlow.Node[], meta?: any) => void
    deleteNodes: (nodeIds: string[]) => void
    updateNodesPosition: (nodeId: string[], position: Record<string,{ x: number; y: number }>) => void
    setSelectedNodeId: (id: string) => void
    getNewNodeIndex: () => number
    processNodesBatch: (allNodes: any[], processCallback: (nodes: any[]) => void, customOptions?: Partial<BatchProcessingOptions>) => () => void
}

export type FlowNodesCtx = FlowNodesStateType & FlowNodesActionsType

// Node state context
export const FlowNodesStateContext = React.createContext<FlowNodesStateType>({
  selectedNodeId: "",
  triggerNode: null,
})

// Node action context
export const FlowNodesActionsContext = React.createContext<FlowNodesActionsType>({
  addNode: () => {},
  deleteNodes: () => {},
  updateNodesPosition: () => {},
  setSelectedNodeId: () => {},
  getNewNodeIndex: () => 0,
  processNodesBatch: () => () => {},
})

// Preserve original combined context for backward compatibility
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

// UI state
export type FlowUICtx = {
    flowInstance: React.MutableRefObject<any>
    showMaterialPanel: boolean
    setShowMaterialPanel: React.Dispatch<React.SetStateAction<boolean>>
    flowDesignListener: EventEmitter<MagicFlow.FlowEventListener>
}

// Full combined context type
export type FlowCtx = React.PropsWithChildren<
    FlowDataCtx &
        FlowEdgesCtx &
        FlowNodesCtx &
        FlowUICtx &
        NodeConfigCtx &
        NodeConfigActionsCtx
>  

// Create separate contexts
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
