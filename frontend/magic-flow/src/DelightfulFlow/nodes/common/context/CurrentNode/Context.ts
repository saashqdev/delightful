import { MagicFlow } from "@/DelightfulFlow/types/flow"
import React from "react"

export type CurrentNodeCtx = React.PropsWithChildren<{
    currentNode: null | MagicFlow.Node
}>  

export const CurrentNodeContext = React.createContext({
	currentNode: null
} as CurrentNodeCtx)
