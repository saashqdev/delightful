import { DelightfulFlow } from "@/DelightfulFlow/types/flow"
import React from "react"

export type CurrentNodeCtx = React.PropsWithChildren<{
    currentNode: null | DelightfulFlow.Node
}>  

export const CurrentNodeContext = React.createContext({
	currentNode: null
} as CurrentNodeCtx)
