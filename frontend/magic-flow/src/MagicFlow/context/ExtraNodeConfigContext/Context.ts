import { BaseNodeType } from "@/MagicFlow/register/node"
import React from "react"

export type ExtraNodeConfigCtx = React.PropsWithChildren<{
	// Shared styles that override the outermost node wrapper
	commonStyle?: React.CSSProperties
	// Node-type-specific style overrides
	nodeStyleMap?: Record<BaseNodeType, React.CSSProperties>
	// Custom rendering options per node type
	customNodeRenderConfig?: Record<BaseNodeType, {
		// Hide description text
		hiddenDesc?: boolean
	}>
}>  

export const ExtraNodeConfigContext = React.createContext({
	commonStyle: {},
	nodeStyleMap: {}
} as ExtraNodeConfigCtx)
