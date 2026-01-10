import React, { useMemo } from "react"
import {
	FlowContext,
	FlowCtx,
	FlowDataContext,
	FlowEdgesContext,
	FlowEdgesStateContext,
	FlowEdgesActionsContext,
	FlowNodesContext,
	FlowUIContext,
	NodeConfigActionsContext,
	NodeConfigContext,
	FlowNodesActionsContext,
	FlowNodesCtx,
	FlowNodesStateContext,
	FlowUICtx,
	FlowEdgesCtx,
} from "./Context"

export const FlowProvider = ({
	flow,
	edges,
	onEdgesChange,
	onConnect,
	updateFlow,
	nodeConfig,
	setNodeConfig,
	updateNodeConfig,
	addNode,
	deleteNodes,
	deleteEdges,
	updateNodesPosition,
	selectedNodeId,
	setSelectedNodeId,
	triggerNode,
	selectedEdgeId,
	setSelectedEdgeId,
	setEdges,
	updateNextNodeIdsByDeleteEdge,
	updateNextNodeIdsByConnect,
	description,
	flowInstance,
	debuggerMode,
	getNewNodeIndex,
	showMaterialPanel,
	setShowMaterialPanel,
	flowDesignListener,
	notifyNodeChange,
    processNodesBatch,
	children,
}: FlowCtx) => {
	// Group data into different contexts
	const flowDataValue = useMemo(() => {
		return {
			flow,
			description,
			debuggerMode,
			updateFlow,
		}
	}, [flow, description, debuggerMode, updateFlow])

	// Edge state value
	const flowEdgesStateValue = useMemo(() => {
		return {
			edges,
			selectedEdgeId,
		}
	}, [edges, selectedEdgeId])

	// Edge actions value
	const flowEdgesActionsValue = useMemo(() => {
		return {
			onEdgesChange,
			onConnect,
			setEdges,
			setSelectedEdgeId,
			updateNextNodeIdsByDeleteEdge,
			updateNextNodeIdsByConnect,
			deleteEdges,
		}
	}, [
		onEdgesChange,
		onConnect,
		setEdges,
		setSelectedEdgeId,
		updateNextNodeIdsByDeleteEdge,
		updateNextNodeIdsByConnect,
		deleteEdges,
	])

	// Original edge value (backward compatible)
	const flowEdgesValue = useMemo(() => {
		return {
			...flowEdgesStateValue,
			...flowEdgesActionsValue,
		}
	}, [flowEdgesStateValue, flowEdgesActionsValue])

	const flowNodesValue = useMemo(() => {
		return {
			addNode,
			deleteNodes,
			updateNodesPosition,
			selectedNodeId,
			setSelectedNodeId,
			triggerNode,
			getNewNodeIndex,
			processNodesBatch,
		}
	}, [
		addNode,
		deleteNodes,
		updateNodesPosition,
		selectedNodeId,
		setSelectedNodeId,
		triggerNode,
		getNewNodeIndex,
		processNodesBatch,
	])

	const flowUIValue = useMemo(() => {
		return {
			flowInstance,
			showMaterialPanel,
			setShowMaterialPanel,
			flowDesignListener,
		}
	}, [flowInstance, showMaterialPanel, setShowMaterialPanel, flowDesignListener])

	const nodeConfigValue = useMemo(() => {
		return {
			nodeConfig,
		}
	}, [nodeConfig])

	const nodeConfigActionsValue = useMemo(() => {
		return {
			setNodeConfig,
			updateNodeConfig,
			notifyNodeChange,
		}
	}, [setNodeConfig, updateNodeConfig, notifyNodeChange])

	// For backward compatibility, preserve the overall FlowContext
	const fullValue = useMemo(() => {
		return {
			...flowDataValue,
			...flowEdgesValue,
			...flowNodesValue,
			...flowUIValue,
			...nodeConfigValue,
			...nodeConfigActionsValue,
		}
	}, [
		flowDataValue,
		flowEdgesValue,
		flowNodesValue,
		flowUIValue,
		nodeConfigValue,
		nodeConfigActionsValue,
	])

	return (
		<FlowDataContext.Provider value={flowDataValue}>
			<FlowEdgesActionsContext.Provider value={flowEdgesActionsValue}>
				<FlowEdgesStateContext.Provider value={flowEdgesStateValue}>
					<FlowEdgesContext.Provider value={flowEdgesValue}>
						<FlowNodesContext.Provider value={flowNodesValue}>
							<FlowUIContext.Provider value={flowUIValue}>
								<NodeConfigActionsContext.Provider value={nodeConfigActionsValue}>
									<NodeConfigContext.Provider value={nodeConfigValue}>
										<FlowContext.Provider value={fullValue}>
											{children}
										</FlowContext.Provider>
									</NodeConfigContext.Provider>
								</NodeConfigActionsContext.Provider>
							</FlowUIContext.Provider>
						</FlowNodesContext.Provider>
					</FlowEdgesContext.Provider>
				</FlowEdgesStateContext.Provider>
			</FlowEdgesActionsContext.Provider>
		</FlowDataContext.Provider>
	)
}


// FlowEdgesProvider component: specialized Provider for edge-related data
export const FlowEdgesProvider = ({
	children,
	...props
}: React.PropsWithChildren<FlowEdgesCtx>) => {
	// Separate edge-related state and actions
	const stateValue = useMemo(() => {
		return {
			edges: props.edges,
			selectedEdgeId: props.selectedEdgeId,
		}
	}, [props.edges, props.selectedEdgeId])

	const actionsValue = useMemo(() => {
		return {
			onEdgesChange: props.onEdgesChange,
			onConnect: props.onConnect,
			setEdges: props.setEdges,
			setSelectedEdgeId: props.setSelectedEdgeId,
			updateNextNodeIdsByDeleteEdge: props.updateNextNodeIdsByDeleteEdge,
			updateNextNodeIdsByConnect: props.updateNextNodeIdsByConnect,
			deleteEdges: props.deleteEdges,
		}
	}, [
		props.onEdgesChange,
		props.onConnect,
		props.setEdges,
		props.setSelectedEdgeId,
		props.updateNextNodeIdsByDeleteEdge,
		props.updateNextNodeIdsByConnect,
		props.deleteEdges,
	])

	// Complete edge value (backward compatible)
	const value = useMemo(() => {
		return {
			...stateValue,
			...actionsValue,
		}
	}, [stateValue, actionsValue])

	return (
		<FlowEdgesActionsContext.Provider value={actionsValue}>
			<FlowEdgesStateContext.Provider value={stateValue}>
				<FlowEdgesContext.Provider value={value}>{children}</FlowEdgesContext.Provider>
			</FlowEdgesStateContext.Provider>
		</FlowEdgesActionsContext.Provider>
	)
}
