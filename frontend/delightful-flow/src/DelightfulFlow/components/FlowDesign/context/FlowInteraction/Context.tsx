import { NodeSchema } from "@/DelightfulFlow/register/node"
import { DelightfulFlow } from "@/DelightfulFlow/types/flow"
import React from "react"
import { Edge, Node } from "reactflow"

// 将FlowInteractionCtx拆分为status和动作两部分
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

// statusContext
export const FlowInteractionStateContext = React.createContext<FlowInteractionStateType>({
	isDragging: false,
	showParamsComp: true,
	showSelectionTools: false,
	currentZoom: 1,
	selectionNodes: [],
	selectionEdges: [],
})

// 动作Context
export const FlowInteractionActionsContext = React.createContext<FlowInteractionActionsType>({
	resetLastLayoutData: () => {},
	onAddItem: (() => Promise.resolve()) as any,
	layout: () => [],
	setShowSelectionTools: () => {},
	onNodesDelete: () => {},
	reactFlowWrapper: undefined,
})

// 保持原有Context向后兼容
export const FlowInteractionContext = React.createContext({
	// 是否处于拖拽status
	isDragging: false,

	// reset布局
	resetLastLayoutData: () => {},

	// 新增node
	onAddItem: (() => {}) as any,

	// 布局optimization
	layout: () => [],

	// 是否显示component的parameterconfiguration
	showParamsComp: true,

	/** 是否显示多选的选框的toolbar */
	showSelectionTools: false,
	setShowSelectionTools: () => {},

	onNodesDelete: () => {},

	currentZoom: 1,

	selectionNodes: [],
	selectionEdges: [],
} as FlowInteractionCtx)

