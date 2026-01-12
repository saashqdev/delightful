// @ts-nocheck
import React, { useState, useCallback } from "react"
import ReactFlow, {
	Node,
	Edge,
	Controls,
	MiniMap,
	Background,
	useNodesState,
	useEdgesState,
	addEdge,
	Connection,
	ReactFlowProvider,
	Panel,
	NodeTypes,
} from "reactflow"
import "reactflow/dist/style.css"

// customnode
const CustomNode = ({ data }: { data: { label: string } }) => {
	return (
		<div
			style={{
				padding: "10px",
				borderRadius: "5px",
				background: "#f0f0f0",
				border: "1px solid #ddd",
				width: "150px",
				textAlign: "center",
			}}
		>
			{data.label}
		</div>
	)
}

// Node typedefine
const nodeTypes: NodeTypes = {
	custom: CustomNode,
}

/**
 * ReactFlowbasic functionalitytestcomponent
 * fortestReactFlowbasic interaction and functionality
 */
const ReactFlowBasicTest: React.FC = () => {
	// initialnode
	const initialNodes = [
		{
			id: "1",
			type: "custom",
			data: { label: "node 1" },
			position: { x: 250, y: 5 },
		},
		{
			id: "2",
			type: "custom",
			data: { label: "node 2" },
			position: { x: 100, y: 100 },
		},
		{
			id: "3",
			type: "custom",
			data: { label: "node 3" },
			position: { x: 400, y: 100 },
		},
		{
			id: "4",
			type: "custom",
			data: { label: "node 4" },
			position: { x: 400, y: 200 },
		},
	]

	// initial edges
	const initialEdges = [
		{ id: "e1-2", source: "1", target: "2", animated: true },
		{ id: "e1-3", source: "1", target: "3" },
	]

	// statusmanage
	const [nodes, setNodes, onNodesChange] = useNodesState(initialNodes)
	const [edges, setEdges, onEdgesChange] = useEdgesState(initialEdges)
	const [selectedNode, setSelectedNode] = useState<string | null>(null)
	const [nodeDragEnabled, setNodeDragEnabled] = useState<boolean>(true)
	const [snapToGrid, setSnapToGrid] = useState<boolean>(false)

	// edge connection callback
	const onConnect = useCallback(
		(params: Edge | Connection) => setEdges((eds) => addEdge(params, eds)),
		[setEdges],
	)

	// nodeclick callback
	const onNodeClick = useCallback((event: React.MouseEvent, node: any) => {
		setSelectedNode(node.id)
		console.log("selectnode:", node)
	}, [])

	// Addnewnode
	const addNode = useCallback(() => {
		const newNode = {
			id: `${nodes.length + 1}`,
			type: "custom",
			data: { label: `node ${nodes.length + 1}` },
			position: {
				x: Math.random() * 300 + 50,
				y: Math.random() * 300 + 50,
			},
		}

		setNodes((nds) => nds.concat(newNode))
	}, [nodes, setNodes])

	return (
		<div style={{ width: "100%", height: "100vh" }}>
			<ReactFlowProvider>
				<ReactFlow
					nodes={nodes}
					edges={edges}
					onNodesChange={onNodesChange}
					onEdgesChange={onEdgesChange}
					onConnect={onConnect}
					onNodeClick={onNodeClick}
					nodeTypes={nodeTypes}
					snapToGrid={snapToGrid}
					nodeDragable={nodeDragEnabled}
					fitView
				>
					<Controls />
					<MiniMap />
					<Background variant={snapToGrid ? "dots" : "lines"} />

					{/* control panel */}
					<Panel
						position="top-left"
						style={{
							background: "white",
							padding: "10px",
							borderRadius: "5px",
							boxShadow: "0 0 10px rgba(0,0,0,0.15)",
						}}
					>
						<div>
							<h3 style={{ marginTop: 0 }}>ReactFlow basictest</h3>

							<div style={{ marginBottom: "10px" }}>
								<button onClick={addNode} style={{ marginRight: "10px" }}>
									addnode
								</button>
								<button
									onClick={() => setNodeDragEnabled(!nodeDragEnabled)}
									style={{ marginRight: "10px" }}
								>
									{nodeDragEnabled ? "disable" : "enable"}nodedrag
								</button>
								<button onClick={() => setSnapToGrid(!snapToGrid)}>
									{snapToGrid ? "close" : "enable"}grid snap
								</button>
							</div>

							{selectedNode && (
								<div>
									<strong>selectednode:</strong> {selectedNode}
								</div>
							)}
						</div>
					</Panel>
				</ReactFlow>
			</ReactFlowProvider>
		</div>
	)
}

export default ReactFlowBasicTest

