// @ts-nocheck
import React, { useState, useCallback, useEffect, useMemo } from "react"
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
} from "reactflow"
import "reactflow/dist/style.css"

/**
 * Performance test component - test ReactFlow performance
 */
const PerformanceTest: React.FC = () => {
	// performance metrics state
	const [renderTime, setRenderTime] = useState<number>(0)
	const [fps, setFps] = useState<number>(0)
	const [memoryUsage, setMemoryUsage] = useState<number | null>(null)
	const [nodeCount, setNodeCount] = useState<number>(0)

	// Generate large number of nodes
	const generateNodes = useCallback((count: number): Node[] => {
		const nodes: Node[] = []
		const gridSize = Math.ceil(Math.sqrt(count))
		const spacing = 150

		for (let i = 0; i < count; i++) {
			const row = Math.floor(i / gridSize)
			const col = i % gridSize

			nodes.push({
				id: `node-${i}`,
				data: { label: `node ${i}` },
				position: { x: col * spacing, y: row * spacing },
				style: {
					background: "#fff",
					border: "1px solid #ddd",
					borderRadius: "4px",
					padding: "10px",
					width: 120,
					fontSize: "12px",
				},
			})
		}

		return nodes
	}, [])

	// Generate connections between nodes
	const generateEdges = useCallback((nodeCount: number, edgesPerNode: number = 2): Edge[] => {
		const edges: Edge[] = []

		for (let i = 0; i < nodeCount; i++) {
			for (let j = 0; j < edgesPerNode; j++) {
				// Connect to random target node, avoid self-loop
				let target
				do {
					target = Math.floor(Math.random() * nodeCount)
				} while (target === i)

				edges.push({
					id: `edge-${i}-${target}-${j}`,
					source: `node-${i}`,
					target: `node-${target}`,
					style: { stroke: "#aaa" },
				})
			}
		}

		return edges
	}, [])

	const [nodes, setNodes, onNodesChange] = useNodesState([])
	const [edges, setEdges, onEdgesChange] = useEdgesState([])

	// Callback for adding edges
	const onConnect = useCallback(
		(params: Edge | Connection) => setEdges((eds) => addEdge(params, eds)),
		[setEdges],
	)

	// Monitor FPS
	useEffect(() => {
		let frameCount = 0
		let lastTime = performance.now()

		const calculateFps = () => {
			frameCount++
			const currentTime = performance.now()

			if (currentTime - lastTime >= 1000) {
				setFps(Math.round((frameCount * 1000) / (currentTime - lastTime)))
				frameCount = 0
				lastTime = currentTime

				// Collect memory usage if browser supports it
				if (window.performance && (performance as any).memory) {
					setMemoryUsage((performance as any).memory.usedJSHeapSize / (1024 * 1024))
				}
			}

			requestAnimationFrame(calculateFps)
		}

		const frameId = requestAnimationFrame(calculateFps)

		return () => {
			cancelAnimationFrame(frameId)
		}
	}, [])

	// Function to measure rendering time
	const measureRenderTime = useCallback(
		(count: number) => {
			const startTime = performance.now()

			const newNodes = generateNodes(count)
			const newEdges = generateEdges(count)

			setNodes(newNodes)
			setEdges(newEdges)
			setNodeCount(count)

			// Wait for next frame render to complete before measuring time
			requestAnimationFrame(() => {
				const endTime = performance.now()
				setRenderTime(endTime - startTime)
			})
		},
		[generateNodes, generateEdges, setNodes, setEdges],
	)

	// Create test button
	const testButtons = useMemo(() => {
		return [10, 50, 100, 500, 1000, 2000].map((count) => (
			<button
				key={count}
				onClick={() => measureRenderTime(count)}
				style={{ margin: "0 5px" }}
			>
				{count} nodes
			</button>
		))
	}, [measureRenderTime])

	return (
		<div style={{ width: "100%", height: "100vh" }}>
			<ReactFlow
				nodes={nodes}
				edges={edges}
				onNodesChange={onNodesChange}
				onEdgesChange={onEdgesChange}
				onConnect={onConnect}
				fitView
				onlyRenderVisibleElements
			>
				<Controls />
				<MiniMap />
				<Background />

				<Panel
					position="top-left"
					style={{ background: "white", padding: "10px", borderRadius: "5px" }}
				>
					<h3>ReactFlow Performance Test</h3>
					<div>
						<strong>Node Count:</strong> {nodeCount}
					</div>
					<div>
						<strong>Render Time:</strong> {renderTime.toFixed(2)} ms
					</div>
					<div>
						<strong>FPS:</strong> {fps}
					</div>
					{memoryUsage !== null && (
						<div>
							<strong>Memory Usage:</strong> {memoryUsage.toFixed(2)} MB
						</div>
					)}
					<div style={{ marginTop: "10px" }}>{testButtons}</div>
					<div style={{ marginTop: "10px" }}>
						<button onClick={() => setNodes([])}>clear</button>
					</div>
				</Panel>
			</ReactFlow>
		</div>
	)
}

export default PerformanceTest

