import { DelightfulFlow } from "@/DelightfulFlow/types/flow"
import React from "react"

// State part
export type NodesStateCtx = {
    // Node data
    nodes: DelightfulFlow.Node[]
}

export const NodesStateContext = React.createContext<NodesStateCtx>({
    nodes: [],
})

// Action part
export type NodesActionCtx = {
    setNodes: React.Dispatch<React.SetStateAction<DelightfulFlow.Node[]>>
    onNodesChange: (changes: any) => void
}

export const NodesActionContext = React.createContext<NodesActionCtx>({
    setNodes: () => {},
    onNodesChange: () => {},
})

// Complete Context preserved for backward compatibility
export type NodesCtx = React.PropsWithChildren<NodesStateCtx & NodesActionCtx>

export const NodesContext = React.createContext<NodesCtx>({
    // Node data
    nodes: [] as DelightfulFlow.Node[],
    setNodes: () => {},
    onNodesChange: () => {},
}) 