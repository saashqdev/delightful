import React, { useMemo } from "react"
import {
	FlowInteractionContext,
	FlowInteractionCtx,
	FlowInteractionStateContext,
	FlowInteractionActionsContext,
	FlowInteractionStateType,
	FlowInteractionActionsType,
} from "./Context"

export const FlowInteractionProvider = ({
	isDragging,
	resetLastLayoutData,
	onAddItem,
	layout,
	showParamsComp,
	showSelectionTools,
	setShowSelectionTools,
	onNodesDelete,
	currentZoom,
	reactFlowWrapper,
	selectionNodes,
	selectionEdges,
	children,
}: FlowInteractionCtx) => {
	// Separate state and actions caching to reduce unnecessary re-renders
	const stateValue = useMemo<FlowInteractionStateType>(() => {
		return {
			isDragging,
			showParamsComp,
			showSelectionTools,
			currentZoom,
			selectionNodes,
			selectionEdges,
		}
	}, [
		isDragging,
		showParamsComp,
		showSelectionTools,
		currentZoom,
		selectionNodes,
		selectionEdges,
	])

	// Actions rarely change, cache separately
	const actionsValue = useMemo<FlowInteractionActionsType>(() => {
		return {
			resetLastLayoutData,
			onAddItem,
			layout,
			setShowSelectionTools,
			onNodesDelete,
			reactFlowWrapper,
		}
	}, [
		resetLastLayoutData,
		onAddItem,
		layout,
		setShowSelectionTools,
		onNodesDelete,
		reactFlowWrapper,
	])

	// For backward compatibility, still provide complete Context
	const value = useMemo(() => {
		return {
			...stateValue,
			...actionsValue,
		}
	}, [stateValue, actionsValue])

	return (
		<FlowInteractionActionsContext.Provider value={actionsValue}>
			<FlowInteractionStateContext.Provider value={stateValue}>
				<FlowInteractionContext.Provider value={value}>
					{children}
				</FlowInteractionContext.Provider>
			</FlowInteractionStateContext.Provider>
		</FlowInteractionActionsContext.Provider>
	)
}

