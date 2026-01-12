import React, { memo } from "react"
import ReactFlow, { SelectionMode, useViewport } from "reactflow"
import { ConnectionLine } from "@/DelightfulFlow/edges/ConnectionLine"
import { Interactions } from "../InteractionSelect"
import { HelperLines, useHelperLines } from "@/DelightfulFlow/components/HelperLines"

// Threshold: how close nodes need to be to show alignment lines
const SNAP_THRESHOLD = 5

interface ReactFlowComponentProps {
	nodeTypes: any
	edgeTypes: any
	nodes: any[]
	edges: any[]
	onNodesChange: any
	onEdgesChange: any
	onConnect: any
	onNodeClick: any
	onEdgeClick: any
	onNodeDragStart: any
	onNodeDrag: any
	onNodeDragStop: any
	onDrop: any
	onDragOver: any
	onClick: any
	onNodesDelete: any
	onEdgesDelete: any
	onPaneClick: any
	onMove: any
	onSelectionChange: any
	onSelectionEnd: any
	interaction: string
	flowInstance: any
	onlyRenderVisibleElements: boolean
	/** Whether to enable helper lines feature */
	helperLinesEnabled?: boolean
	children: React.ReactNode
}

const ReactFlowComponent = memo(
	({
		nodeTypes,
		edgeTypes,
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
		onClick,
		onNodesDelete,
		onEdgesDelete,
		onPaneClick,
		onMove,
		onSelectionChange,
		onSelectionEnd,
		interaction,
		flowInstance,
		helperLinesEnabled = false,
		children,
	}: ReactFlowComponentProps) => {
		// Get viewport information (zoom, pan, etc.)
		const { x, y, zoom } = useViewport()

		// Use helper lines hook
		const {
			horizontalLines,
			verticalLines,
			handleNodeDragStart,
			handleNodeDrag,
			handleNodeDragStop,
			hasHelperLines,
		} = useHelperLines({
			nodes,
			onNodeDragStart,
			onNodeDrag,
			onNodeDragStop,
			onNodesChange, // Add onNodesChange parameter
			enabled: helperLinesEnabled, // Add enabled parameter
			options: {
				// Configuration parameters
				threshold: 5, // Alignment threshold
				color: "#315cec", // Helper line color
				lineWidth: 1, // Helper line width
				zIndex: 9999, // Helper line z-index
				enableSnap: true, // Enable node snapping
			},
		})

		return (
			<>
				<ReactFlow
					//@ts-ignore
					nodeTypes={nodeTypes}
					edgeTypes={edgeTypes}
					//@ts-ignore
					nodes={nodes}
					edges={edges}
					onNodesChange={onNodesChange}
					onEdgesChange={onEdgesChange}
					onConnect={onConnect}
					onNodeClick={onNodeClick}
					onEdgeClick={onEdgeClick}
					onNodeDragStart={handleNodeDragStart}
					onNodeDrag={handleNodeDrag}
					onNodeDragStop={handleNodeDragStop}
					onDrop={onDrop}
					onDragOver={onDragOver}
					onClick={onClick}
					onNodesDelete={onNodesDelete}
					onEdgesDelete={onEdgesDelete}
					minZoom={0.01}
					maxZoom={8}
					connectionLineComponent={ConnectionLine}
					panOnScroll={interaction === Interactions.TouchPad}
					zoomOnScroll={interaction === Interactions.Mouse}
					panOnDrag={interaction === Interactions.Mouse}
					selectionOnDrag
					ref={flowInstance}
					onPaneClick={onPaneClick}
					zoomOnDoubleClick={false}
					// @ts-ignore
					onMove={onMove}
					selectionKeyCode={null}
					onSelectionChange={onSelectionChange}
					onSelectionEnd={onSelectionEnd}
				// Partial selection counts as selected
					selectionMode={SelectionMode.Partial}
				>
					{children}
					{/* Render helper lines */}
					{hasHelperLines && (
						<HelperLines
							horizontalLines={horizontalLines}
							verticalLines={verticalLines}
							transform={{ x, y, zoom }}
						/>
					)}
				</ReactFlow>
			</>
		)
	},
)

export default ReactFlowComponent

