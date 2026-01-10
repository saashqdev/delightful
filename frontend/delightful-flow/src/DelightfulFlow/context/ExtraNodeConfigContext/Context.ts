import { BaseNodeType } from "@/DelightfulFlow/register/node"
import React from "react"

export type ExtraNodeConfigCtx = React.PropsWithChildren<{
	// Common styles, override node outermost common styles
	commonStyle?: React.CSSProperties
	// Can customize other styles for specific nodes based on this
	nodeStyleMap?: Record<BaseNodeType, React.CSSProperties>
    // Custom node rendering configuration
    customNodeRenderConfig?: Record<BaseNodeType, {
        // Whether to hide description
        hiddenDesc?: boolean
    }>
}>  

export const ExtraNodeConfigContext = React.createContext({
	commonStyle: {},
	nodeStyleMap: {}
} as ExtraNodeConfigCtx)
