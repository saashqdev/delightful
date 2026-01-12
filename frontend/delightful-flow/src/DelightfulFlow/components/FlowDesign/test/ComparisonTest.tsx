// @ts-nocheck
import React, { useState, useMemo, useCallback } from "react"
import PerformanceTest from "./PerformanceTest"
import CanvasRenderer from "./CanvasRenderer"
import { Node, Edge } from "reactflow"

/**
 * Comparison test component - displays performance differences between ReactFlow and Canvas rendering simultaneously
 */
const ComparisonTest: React.FC = () => {
	const [nodeCount, setNodeCount] = useState(20)
	const [renderMode, setRenderMode] = useState<"react-flow" | "canvas" | "both">("both")

	// Generate test nodes
	const nodes: Node[] = useMemo(
		() =>
			Array.from({ length: nodeCount }, (_, i) => ({
				id: `node-${i}`,
				data: { label: `Node ${i}` },
				position: { x: (i % 10) * 200, y: Math.floor(i / 10) * 100 },
				type: "default",
			})),
		[nodeCount],
	)

	// Generate test edges
	const edges: Edge[] = useMemo(() => {
		const result: Edge[] = []
		for (let i = 0; i < nodeCount - 1; i++) {
			if (i % 10 < 9) {
				// Connect nodes in the same row
				result.push({
					id: `edge-${i}-${i + 1}`,
					source: `node-${i}`,
					target: `node-${i + 1}`,
					type: "default",
				})
			}
			if (i + 10 < nodeCount) {
				// Connect nodes in adjacent rows
				result.push({
					id: `edge-${i}-${i + 10}`,
					source: `node-${i}`,
					target: `node-${i + 10}`,
					type: "default",
				})
			}
		}
		return result
	}, [nodeCount])

	// Increase node count
	const addNodes = useCallback(() => {
		setNodeCount((prev) => prev + 10)
	}, [])

	// Decrease node count
	const removeNodes = useCallback(() => {
		setNodeCount((prev) => Math.max(10, prev - 10))
	}, [])

	return (
		<div style={{ width: "100%", height: "100vh", display: "flex", flexDirection: "column" }}>
			{/* Control panel */}
			<div
				style={{
					padding: "10px",
					borderBottom: "1px solid #ddd",
					display: "flex",
					alignItems: "center",
					gap: "20px",
				}}
			>
				<div>
					<strong>Current node count: </strong> {nodeCount}
					<div style={{ marginTop: "8px" }}>
						<button onClick={addNodes} style={{ marginRight: "5px" }}>
							Add 10 nodes
						</button>
						<button onClick={removeNodes}>Remove 10 nodes</button>
					</div>
				</div>

				<div>
					<strong>Rendering mode: </strong>
					<div style={{ marginTop: "8px" }}>
						<label style={{ marginRight: "10px" }}>
							<input
								type="radio"
								checked={renderMode === "both"}
								onChange={() => setRenderMode("both")}
							/>
							Comparison mode
						</label>
						<label style={{ marginRight: "10px" }}>
							<input
								type="radio"
								checked={renderMode === "react-flow"}
								onChange={() => setRenderMode("react-flow")}
							/>
							ReactFlow only
						</label>
						<label>
							<input
								type="radio"
								checked={renderMode === "canvas"}
								onChange={() => setRenderMode("canvas")}
							/>
							Canvas only
						</label>
					</div>
				</div>

				<div>
					<strong>Performance test instructions:</strong>
					<div style={{ marginTop: "8px", fontSize: "12px" }}>
						1. Observe FPS changes during panning and zooming
						<br />
						2. Increase node count until performance degrades
						<br />
						3. Compare performance differences between the two rendering modes
					</div>
				</div>
			</div>

			{/* Rendering area */}
			<div
				style={{
					flex: 1,
					display: "flex",
					flexDirection: renderMode === "both" ? "row" : "column",
				}}
			>
				{(renderMode === "react-flow" || renderMode === "both") && (
					<div
						style={{
							flex: 1,
							height: renderMode === "both" ? "100%" : "100vh",
							border: renderMode === "both" ? "1px solid #ddd" : "none",
							position: "relative",
						}}
					>
						{renderMode === "both" && (
							<div
								style={{
									position: "absolute",
									top: 0,
									left: 0,
									padding: "5px 10px",
									background: "#f0f0f0",
									borderRadius: "0 0 5px 0",
									zIndex: 5,
								}}
							>
								<strong>ReactFlow DOM rendering</strong>
							</div>
						)}
						<PerformanceTest />
					</div>
				)}

				{(renderMode === "canvas" || renderMode === "both") && (
					<div
						style={{
							flex: 1,
							height: renderMode === "both" ? "100%" : "100vh",
							border: renderMode === "both" ? "1px solid #ddd" : "none",
							position: "relative",
						}}
					>
						{renderMode === "both" && (
							<div
								style={{
									position: "absolute",
									top: 0,
									left: 0,
									padding: "5px 10px",
									background: "#f0f0f0",
									borderRadius: "0 0 5px 0",
									zIndex: 5,
								}}
							>
								<strong>Canvas rendering</strong>
							</div>
						)}
						<CanvasRenderer nodes={nodes} edges={edges} />
					</div>
				)}
			</div>
		</div>
	)
}

export default ComparisonTest

