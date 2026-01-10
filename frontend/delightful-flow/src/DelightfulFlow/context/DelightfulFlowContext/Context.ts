import { createStore } from "@/DelightfulFlow/store"
import React from "react"

export type DelightfulFlowCtx = React.PropsWithChildren<ReturnType<typeof createStore> | null>  

export const DelightfulFlowContext = React.createContext({
} as DelightfulFlowCtx)
