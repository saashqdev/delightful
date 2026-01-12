import { NodeSchema } from "@/DelightfulFlow/register/node"
import { DelightfulFlow } from "@/DelightfulFlow/types/flow"
import React from "react"
import { Edge, Node } from "reactflow"

// Split FlowInteractionCtx into state and actions parts
export type FlowInteractionStateType = {
	isDragging: boolean
	showParamsComp: boolean
	showSelectionTools: boolean
	currentZoom: number
	selectionNodes: DelightfulFlow.Node[]
	selectionEdges: Edge[]
}

export type FlowInteractionActionsType = {
	resetLastLayoutData: () => void
	onAddItem: (
		event: any,
		nodeData: NodeSchema,
		extraConfig?: Record<string, any>,
	) => Promise<void>
	layout: () => DelightfulFlow.Node[]
	setShowSelectionTools: React.Dispatch<React.SetStateAction<boolean>>
	onNodesDelete: (_nodes: Node[]) => void
	reactFlowWrapper?: React.RefObject<HTMLDivElement>
}

export type FlowInteractionCtx = React.PropsWithChildren<
	FlowInteractionStateType & FlowInteractionActionsType
>

// State context
export const FlowInteractionStateContext = React.createContext<FlowInteractionStateType>({
	isDragging: false,
	showParamsComp: true,
	showSelectionTools: false,
	currentZoom: 1,
	selectionNodes: [],
	selectionEdges: [],
})

// Actions context
export const FlowInteractionActionsContext = React.createContext<FlowInteractionActionsType>({
	resetLastLayoutData: () => {},
	onAddItem: (() => Promise.resolve()) as any,
	layout: () => [],
	setShowSelectionTools: () => {},
	onNodesDelete: () => {},
	reactFlowWrapper: undefined,
})

// Maintain original Context for backward compatibility
export const FlowInteractionContext = React.createContext({
	// Whether in dragging state
	isDragging: false,

	// Reset layout
	resetLastLayoutData: () => {},

	// Add new node
	onAddItem: (() => {}) as any,

	// Layout optimization
	layout: () => [],

	// Whether to show component parameter configuration
	showParamsComp: true,

	/** Whether to show toolbar for multi-selection */
	showSelectionTools: false,
	setShowSelectionTools: () => {},

	onNodesDelete: () => {},

	currentZoom: 1,

	selectionNodes: [],
	selectionEdges: [],
} as FlowInteractionCtx)

