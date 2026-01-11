import React, { useState, useMemo, memo } from "react"
import styles from "./index.module.less"

import { useExternalConfig } from "@/DelightfulFlow/context/ExternalContext/useExternal"
import { useFlowEdges, useFlowUI } from "../../context/FlowContext/useFlow"
import { edgeModels } from "../../edges"
import nodeModels from "../../nodes/index"
import FlowBackground from "./components/FlowBackground"
import useSelections from "./components/SelectionTools/useSelections"
import { FlowInteractionProvider } from "./context/FlowInteraction/Provider"
import useFlowControls from "./hooks/useFlowControls"
import useFlowEvents from "./hooks/useFlowEvents"
import useNodeClick from "./hooks/useNodeClick"
import useTargetToErrorNode from "./hooks/useTargetToErrorNode"
import { useNodes } from "@/DelightfulFlow/context/NodesContext/useNodes"
import useFlowCommand from "./hooks/useFlowCommands"
// Import new leaf components
import FlowControls from "./components/sections/FlowControls"
import FlowSelectionPanel from "./components/sections/FlowSelectionPanel"
import FlowMiniMap from "./components/sections/FlowMiniMap"
import ReactFlowComponent from "./components/sections/ReactFlowComponent"

// Create a stable child component to avoid ReactFlowComponent re-rendering due to child changes
const StableFlowChildren = memo(
	({ showMinMap, controlItemGroups }: { showMinMap: boolean; controlItemGroups: any }) => {
		return (
			<>
				<FlowControls controlItemGroups={controlItemGroups} />
				<FlowBackground />
				<FlowMiniMap showMinMap={showMinMap} />
			</>
		)
	},
)

// Use memo to wrap FlowDesign component to avoid unnecessary re-rendering
const FlowDesign = memo(function FlowDesign() {
	// Use more granular hooks instead of full useFlow to reduce unnecessary re-rendering
	const { onEdgesChange, onConnect, edges } = useFlowEdges()
	const { flowInstance } = useFlowUI()
	const { nodes, onNodesChange } = useNodes()

	// When resolution < 15% | full rendering, disable params rendering
	const [showParamsComp, setShowParamsComp] = useState(true)

	const { onNodeClick, onPanelClick } = useNodeClick()

	const {
		showSelectionTools,
		setShowSelectionTools,
		selectionNodes,
		selectionEdges,
		onSelectionChange,
		onSelectionEnd,
		onCopy,
	} = useSelections({
		flowInstance,
	})

	const {
		controlItemGroups,
		resetLastLayoutData,
		resetCanLayout,
		layout,
		showMinMap,
		currentZoom,
		onMove,
		interaction,
		onInteractionChange,
		onFitView,
		onZoomIn,
		onZoomOut,
		onEdgeTypeChange,
		onLock,
		helperLinesEnabled,
	} = useFlowControls({
		setShowParamsComp,
		flowInstance,
	})

	const {
		onNodeDragStop,
		onDrop,
		onDragOver,
		reactFlowWrapper,
		onReactFlowClick,
		onNodeDragStart,
		onNodeDrag,
		isDragging,
		onNodesDelete,
		onEdgeClick,
		onEdgesDelete,
		onAddItem,
		onlyRenderVisibleElements,
	} = useFlowEvents({
		resetLastLayoutData,
		resetCanLayout,
		currentZoom,
		setShowParamsComp,
	})

	/** External params have highest priority */
	const { onlyRenderVisibleElements: externalOnlyRenderVisibleElements } = useExternalConfig()

	/** Locate to error node when run fails */
	useTargetToErrorNode()

	useFlowCommand({
		layout,
		onInteractionChange,
		onFitView,
		onZoomIn,
		onZoomOut,
		onEdgeTypeChange,
		onLock,
		// @ts-ignore
		onNodesDelete,
		onEdgesDelete,
		onAddItem,
	})

	// Use useMemo to optimize complex calculations or object creation
	const visibleElements = useMemo(
		() => externalOnlyRenderVisibleElements || onlyRenderVisibleElements,
		[externalOnlyRenderVisibleElements, onlyRenderVisibleElements],
	)

	// 使用useMemo包装FlowInteractionProvider的props
	const interactionProviderProps = useMemo(
		() => ({
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
		}),
		[
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
		],
	)

	// 使用useMemo包装ReactFlowComponent的props
	const reactFlowProps = useMemo(
		() => ({
			nodeTypes: nodeModels,
			edgeTypes: edgeModels,
			nodes,
			edges,
			onNodesChange,
			onEdgesChange,
			onConnect,
			onNodeClick,
			onEdgeClick,
			onNodeDragStart,
			onNodeDrag,
			onNodeDragStop,
			onDrop,
			onDragOver,
			onClick: onReactFlowClick,
			onNodesDelete,
			onEdgesDelete,
			onPaneClick: onPanelClick,
			onMove,
			onSelectionChange,
			onSelectionEnd,
			interaction,
			flowInstance,
			onlyRenderVisibleElements: visibleElements,
			helperLinesEnabled,
		}),
		[
			nodes,
			edges,
			onNodesChange,
			onEdgesChange,
			onConnect,
			onNodeClick,
			onEdgeClick,
			onNodeDragStart,
			onNodeDrag,
			onNodeDragStop,
			onDrop,
			onDragOver,
			onReactFlowClick,
			onNodesDelete,
			onEdgesDelete,
			onPanelClick,
			onMove,
			onSelectionChange,
			onSelectionEnd,
			interaction,
			flowInstance,
			visibleElements,
			helperLinesEnabled,
		],
	)

	// 使用useMemo包装FlowSelectionPanel的props
	const selectionPanelProps = useMemo(
		() => ({
			showSelectionTools,
			setShowSelectionTools,
			selectionNodes,
			selectionEdges,
			onCopy,
		}),
		[showSelectionTools, setShowSelectionTools, selectionNodes, selectionEdges, onCopy],
	)

	// 稳定ReactFlowComponent的children引用
	const stableChildren = useMemo(
		() => <StableFlowChildren showMinMap={showMinMap} controlItemGroups={controlItemGroups} />,
		[showMinMap, controlItemGroups],
	)

	return (
		<div className={styles.flowDesign} ref={reactFlowWrapper}>
			<FlowInteractionProvider {...interactionProviderProps}>
				{/* 将FlowSelectionPanel移出ReactFlowComponent，成为兄弟component */}
				<FlowSelectionPanel {...selectionPanelProps} />
				<ReactFlowComponent {...reactFlowProps}>{stableChildren}</ReactFlowComponent>
			</FlowInteractionProvider>
		</div>
	)
})

export default FlowDesign

