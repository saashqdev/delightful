import { MagicFlow } from "@/DelightfulFlow/types/flow"
import React from "react"

// State segment
export type NodesStateCtx = {
    // Node data
    nodes: MagicFlow.Node[]
}

export const NodesStateContext = React.createContext<NodesStateCtx>({
    nodes: [],
})

// Action segment
export type NodesActionCtx = {
    setNodes: React.Dispatch<React.SetStateAction<MagicFlow.Node[]>>
    onNodesChange: (changes: any) => void
}

export const NodesActionContext = React.createContext<NodesActionCtx>({
    setNodes: () => {},
    onNodesChange: () => {},
})

// Full context kept for backward compatibility
export type NodesCtx = React.PropsWithChildren<NodesStateCtx & NodesActionCtx>

export const NodesContext = React.createContext<NodesCtx>({
    // Node data
    nodes: [] as MagicFlow.Node[],
    setNodes: () => {},
    onNodesChange: () => {},
}) 