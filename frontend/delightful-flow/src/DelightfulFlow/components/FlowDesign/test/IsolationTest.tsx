// @ts-nocheck
import React, { useState, useCallback, useEffect, useRef, useMemo } from "react"
import ReactFlow, {
	MiniMap,
	Controls,
	Background,
	useNodesState,
	useEdgesState,
	addEdge,
	Panel,
	Connection,
	Edge,
	Node,
	NodeTypes,
	NodeProps,
} from "reactflow"
import "reactflow/dist/style.css"

// Custom simple node for isolation testing
const SimpleNode = ({ data }: NodeProps) => {
	return (
		<div
			style={{
				padding: "8px",
				borderRadius: "3px",
				background: "#f0f0f0",
				border: "1px solid #ddd",
				width: "120px",
				height: "40px",
				display: "flex",
				alignItems: "center",
				justifyContent: "center",
			}}
		>
			{data.label}
		</div>
	)
}

// Custom complex node for testing performance impact of more complex components
const ComplexNode = ({ data }: NodeProps) => {
	return (
		<div
			style={{
				padding: "10px",
				borderRadius: "5px",
				background: "white",
				border: "1px solid #ddd",
				width: "180px",
				boxShadow: "0 2px 5px rgba(0,0,0,0.1)",
			}}
		>
			<div style={{ fontWeight: "bold", marginBottom: "5px" }}>{data.label}</div>
			<div style={{ fontSize: "11px", color: "#666" }}>ID: {data.id}</div>
			<div style={{ marginTop: "5px", display: "flex", justifyContent: "space-between" }}>
				<div
					style={{
						fontSize: "11px",
						background: "#eee",
						padding: "2px 5px",
						borderRadius: "3px",
					}}
				>
					Input: {data.inputs || 0}
				</div>
				<div
					style={{
						fontSize: "11px",
						background: "#eee",
						padding: "2px 5px",
						borderRadius: "3px",
					}}
				>
					Output: {data.outputs || 0}
				</div>
			</div>
		</div>
	)
}

// Register node types
const nodeTypes: NodeTypes = {
	simple: SimpleNode,
	complex: ComplexNode,
}

/**
 * ReactFlow isolation test component - for diagnosing performance issues
 */
const IsolationTest: React.FC = () => {
	// Performance metric state
	const [renderTime, setRenderTime] = useState<number>(0)
	const [fps, setFps] = useState<number>(0)
	const [memoryUsage, setMemoryUsage] = useState<string>("Not measured")
	const [nodeCount, setNodeCount] = useState<number>(0)
	const [edgeCount, setEdgeCount] = useState<number>(0)
	const [nodeType, setNodeType] = useState<"simple" | "complex">("simple")
	const [currentTest, setCurrentTest] = useState<string>("")

	// flowgraphstatus
	const [nodes, setNodes, onNodesChange] = useNodesState([])
	const [edges, setEdges, onEdgesChange] = useEdgesState([])

	// FPS measurement
	const fpsRef = useRef<number>(0)
	const frameCountRef = useRef<number>(0)
	const lastTimeRef = useRef<number>(performance.now())
	const animationFrameIdRef = useRef<number | null>(null)

	// Callback for connecting edges
	const onConnect = useCallback(
		(params: Edge | Connection) => setEdges((eds) => addEdge(params, eds)),
		[setEdges],
	)

	// Generate specified number of nodes
	const generateNodes = useCallback((count: number, type: "simple" | "complex"): Node[] => {
		const gridSize = Math.ceil(Math.sqrt(count))
		const spacing = 200

		return Array.from({ length: count }).map((_, i) => {
			const row = Math.floor(i / gridSize)
			const col = i % gridSize

			return {
				id: `node-${i}`,
				type: type,
				data: {
					label: `node ${i}`,
					id: `ID-${i}`,
					inputs: Math.floor(Math.random() * 3) + 1,
					outputs: Math.floor(Math.random() * 3) + 1,
				},
				position: {
					x: col * spacing,
					y: row * spacing,
				},
			}
		})
	}, [])

	// Generate specified number of edges
	const generateEdges = useCallback((nodeCount: number, edgeCount: number): Edge[] => {
		const edges: Edge[] = []

		// Ensureedge count not exceeding max possible connections
		const maxPossibleEdges = nodeCount * (nodeCount - 1)
		const actualEdgeCount = Math.min(edgeCount, maxPossibleEdges)

		// createa simple tree structure，ensure allnodeall connected
		for (let i = 1; i < nodeCount; i++) {
			const parentId = Math.floor((i - 1) / 2)
			edges.push({
				id: `edge-${parentId}-${i}`,
				source: `node-${parentId}`,
				target: `node-${i}`,
				type: "smoothstep",
			})
		}

		// Addextra random edges，until reaching specified count
		if (edges.length < actualEdgeCount) {
			const remainingEdges = actualEdgeCount - edges.length

			for (let i = 0; i < remainingEdges; i++) {
				const source = Math.floor(Math.random() * nodeCount)
				let target = Math.floor(Math.random() * nodeCount)

				// Ensurenot a self loop
				while (target === source) {
					target = Math.floor(Math.random() * nodeCount)
				}

				// Check if this edge already exists
				const edgeExists = edges.some(
					(edge) => edge.source === `node-${source}` && edge.target === `node-${target}`,
				)

				if (!edgeExists) {
					edges.push({
						id: `edge-random-${i}`,
						source: `node-${source}`,
						target: `node-${target}`,
						type: "smoothstep",
					})
				}
			}
		}

		return edges
	}, [])

	// measureFPS
	const monitorFPS = useCallback(() => {
		frameCountRef.current += 1
		const now = performance.now()
		const elapsed = now - lastTimeRef.current

		if (elapsed >= 1000) {
			fpsRef.current = Math.round((frameCountRef.current * 1000) / elapsed)
			setFps(fpsRef.current)
			frameCountRef.current = 0
			lastTimeRef.current = now
		}

		animationFrameIdRef.current = requestAnimationFrame(monitorFPS)
	}, [])

	// Measure memory usage
	const measureMemory = useCallback(async () => {
		if ("memory" in performance) {
			try {
				const memory = (performance as any).memory
				if (memory) {
					const usedHeapSize = memory.usedJSHeapSize
					const formattedMemory = (usedHeapSize / (1024 * 1024)).toFixed(2)
					setMemoryUsage(`${formattedMemory} MB`)
				}
			} catch (error) {
				console.error("Unable to measure memory usage:", error)
				setMemoryUsage("Browser not supported")
			}
		} else {
			setMemoryUsage("browser not supported")
		}
	}, [])

	// start/stopperformancemonitor
	useEffect(() => {
		// startmonitorFPS
		animationFrameIdRef.current = requestAnimationFrame(monitorFPS)

		// measure memory periodically
		const memoryInterval = setInterval(measureMemory, 1000)

		return () => {
			// cleanup
			if (animationFrameIdRef.current !== null) {
				cancelAnimationFrame(animationFrameIdRef.current)
			}
			clearInterval(memoryInterval)
		}
	}, [monitorFPS, measureMemory])

	// loadtestdata
	const loadTestData = useCallback(
		(count: number) => {
			setCurrentTest(`load ${count} item${nodeType === "simple" ? "simple" : "complex"}nodetest`)
			const start = performance.now()

			// Generatenodeand edges
			const edgeCount = count * 2 // edge count isnodeof2times
			const newNodes = generateNodes(count, nodeType)
			const newEdges = generateEdges(count, edgeCount)

			setNodes(newNodes)
			setEdges(newEdges)
			setNodeCount(count)
			setEdgeCount(edgeCount)

			// usesetTimeoutto ensure renderedcomplete
			setTimeout(() => {
				const end = performance.now()
				setRenderTime(end - start)
			}, 100)
		},
		[generateNodes, generateEdges, setNodes, setEdges, nodeType],
	)

	// cleartestdata
	const clearTestData = useCallback(() => {
		setNodes([])
		setEdges([])
		setNodeCount(0)
		setEdgeCount(0)
		setRenderTime(0)
		setCurrentTest("")
	}, [setNodes, setEdges])

	// toggleNode type
	const toggleNodeType = useCallback(() => {
		setNodeType((prev) => (prev === "simple" ? "complex" : "simple"))
	}, [])

	// Performance test button list
	const testButtons = useMemo(
		() => [
			{ count: 10, label: "10 node" },
			{ count: 50, label: "50 node" },
			{ count: 100, label: "100 node" },
			{ count: 500, label: "500 node" },
			{ count: 1000, label: "1000 node" },
			{ count: 2000, label: "2000 node" },
		],
		[],
	)

	return (
		<div style={{ width: "100%", height: "100vh" }}>
			<ReactFlow
				nodes={nodes}
				edges={edges}
				onNodesChange={onNodesChange}
				onEdgesChange={onEdgesChange}
				onConnect={onConnect}
				nodeTypes={nodeTypes}
				fitView
			>
				<Controls />
				<MiniMap />
				<Background variant="dots" />

				<Panel
					position="top-left"
					style={{
						background: "white",
						padding: "10px",
						borderRadius: "5px",
						boxShadow: "0 2px 5px rgba(0,0,0,0.1)",
					}}
				>
					<h3 style={{ marginTop: 0 }}>ReactFlow isolationtest</h3>

					<div style={{ marginBottom: "15px" }}>
						<div>
							<strong>currenttest:</strong> {currentTest || "none"}
						</div>
						<div>
							<strong>Node type:</strong>{" "}
							{nodeType === "simple" ? "simplenode" : "complexnode"}
						</div>
						<div>
							<strong>nodecount:</strong> {nodeCount}
						</div>
						<div>
							<strong>edge count:</strong> {edgeCount}
						</div>
						<div>
							<strong>rendertime:</strong> {renderTime.toFixed(2)} ms
						</div>
						<div>
							<strong>FPS:</strong> {fps}
						</div>
						<div>
							<strong>memory usage:</strong> {memoryUsage}
						</div>
					</div>

					<div style={{ display: "flex", flexDirection: "column", gap: "10px" }}>
						<div style={{ display: "flex", flexWrap: "wrap", gap: "8px" }}>
							{testButtons.map((btn) => (
								<button
									key={btn.count}
									onClick={() => loadTestData(btn.count)}
									style={{
										padding: "5px 10px",
										cursor: "pointer",
										background: "#f0f0f0",
										border: "1px solid #ddd",
										borderRadius: "3px",
									}}
								>
									{btn.label}
								</button>
							))}
						</div>

						<div style={{ display: "flex", gap: "8px" }}>
							<button
								onClick={toggleNodeType}
								style={{
									padding: "5px 10px",
									cursor: "pointer",
									background: "#e6f7ff",
									border: "1px solid #91d5ff",
									borderRadius: "3px",
									flex: 1,
								}}
							>
								switch to{nodeType === "simple" ? "complex" : "simple"}node
							</button>

							<button
								onClick={clearTestData}
								style={{
									padding: "5px 10px",
									cursor: "pointer",
									background: "#fff1f0",
									border: "1px solid #ffa39e",
									borderRadius: "3px",
									flex: 1,
								}}
							>
								cleartest
							</button>
						</div>
					</div>
				</Panel>
			</ReactFlow>
		</div>
	)
}

export default IsolationTest

