// @ts-nocheck
import React from "react"
import { BrowserRouter as Router, Routes, Route, Link } from "react-router-dom"
import PerformanceTest from "./PerformanceTest"
import CanvasRenderer from "./CanvasRenderer"
import ComparisonTest from "./ComparisonTest"

/**
 * Test route component - provides navigation for various test pages
 */
const TestRoute: React.FC = () => {
	return (
		<Router>
			<div
				style={{ width: "100%", height: "100vh", display: "flex", flexDirection: "column" }}
			>
				<nav
					style={{
						padding: "15px",
						borderBottom: "1px solid #ddd",
						background: "#f5f5f5",
					}}
				>
					<h2 style={{ margin: "0 0 15px 0" }}>ReactFlow Performance Test</h2>
					<div style={{ display: "flex", gap: "20px" }}>
						<Link to="/" style={linkStyle}>
							Home
						</Link>
						<Link to="/react-flow" style={linkStyle}>
							ReactFlow Test
						</Link>
						<Link to="/canvas" style={linkStyle}>
							Canvas Test
						</Link>
						<Link to="/comparison" style={linkStyle}>
							Performance Comparison
						</Link>
					</div>
				</nav>

				<div style={{ flex: 1 }}>
					<Routes>
						<Route path="/" element={<HomePage />} />
						<Route path="/react-flow" element={<PerformanceTest />} />
						<Route path="/canvas" element={<CanvasRenderer nodes={[]} edges={[]} />} />
						<Route path="/comparison" element={<ComparisonTest />} />
					</Routes>
				</div>
			</div>
		</Router>
	)
}

// Home page component
const HomePage: React.FC = () => {
	return (
		<div style={{ padding: "20px", maxWidth: "800px", margin: "0 auto" }}>
			<h1>ReactFlow Performance Test Tool</h1>

			<div style={{ marginTop: "20px" }}>
				<h2>Test Directory</h2>
				<p>
					This tool is intended to help developers diagnose and compare ReactFlow in different rendering modes and performance differences.
					Through comparing DOM rendering and Canvas rendering performance, we can target optimization for large-scale flow graphs to improve rendering efficiency.
				</p>
			</div>

			<div style={{ marginTop: "20px" }}>
				<h2>Available Tests</h2>
				<div
					style={{
						display: "flex",
						flexDirection: "column",
						gap: "15px",
						marginTop: "10px",
					}}
				>
					<TestCard
					title="ReactFlow DOM Render Test"
					description="Test native ReactFlow DOM rendering performance; supports dynamic adjustment of node count and monitor FPS."
						link="/react-flow"
					/>

					<TestCard
					title="Canvas Render Test"
					description="Use Canvas implementation for flow graph rendering to avoid creating large amounts of DOM nodes; optimizes panning and zooming performance."
						link="/canvas"
					/>

					<TestCard
					title="Performance Comparison Test"
					description="Show ReactFlow and Canvas rendering methods side by side to intuitively compare performance differences."
						link="/comparison"
					/>
				</div>
			</div>

			<div style={{ marginTop: "30px" }}>
				<h2>Test Recommendations</h2>
				<ul>
					<li>Start with a small number of nodes, gradually increase node count until performance noticeably decreases</li>
					<li>Test with frequent panning and zooming operations; observe FPS changes</li>
					<li>Compare the two rendering methods in handling large amounts of nodes and performance differences</li>
					<li>Try to identify critical points where performance bottlenecks appear (node count threshold)</li>
				</ul>
			</div>
		</div>
	)
}

// Test card component
const TestCard: React.FC<{
	title: string
	description: string
	link: string
}> = ({ title, description, link }) => {
	return (
		<div
			style={{
				border: "1px solid #ddd",
				borderRadius: "8px",
				padding: "15px",
				background: "#fff",
				boxShadow: "0 2px 4px rgba(0,0,0,0.05)",
			}}
		>
			<h3 style={{ margin: "0 0 10px 0" }}>{title}</h3>
			<p style={{ margin: "0 0 15px 0", color: "#666" }}>{description}</p>
			<Link
				to={link}
				style={{
					display: "inline-block",
					padding: "8px 16px",
					background: "#4a90e2",
					color: "white",
					borderRadius: "4px",
					textDecoration: "none",
					fontWeight: "bold",
				}}
			>
				Start Test
			</Link>
		</div>
	)
}

// Link style
const linkStyle = {
	color: "#4a90e2",
	textDecoration: "none",
	fontWeight: "bold",
	padding: "5px 10px",
	borderRadius: "4px",
	transition: "background 0.2s",
	":hover": {
		background: "rgba(74, 144, 226, 0.1)",
	},
}

export default TestRoute

