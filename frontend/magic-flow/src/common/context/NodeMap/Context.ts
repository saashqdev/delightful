import { BaseNodeType, NodeVersionWidget, NodeWidget } from "@/DelightfulFlow/register/node"
import React from "react"

export type NodeVersionMap = Record<BaseNodeType, NodeVersionWidget>

export type NodeMapCtx = React.PropsWithChildren<{
    nodeMap: NodeVersionMap
}>  

export const NodeMapContext = React.createContext({
	nodeMap: {} as NodeVersionMap
} as NodeMapCtx)
