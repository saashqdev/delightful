import React from "react"
import { ViewportTransform } from "./types"

interface HelperLinesProps {
	horizontalLines: number[]
	verticalLines: number[]
	transform: ViewportTransform
	color?: string
	lineWidth?: number
	zIndex?: number
}

/**
 * Helper lines used to show alignment guides in React Flow
 */
export const HelperLines: React.FC<HelperLinesProps> = ({
	horizontalLines,
	verticalLines,
	transform,
	color = "#315cec",
	lineWidth = 1,
	zIndex = 9999,
}) => {
	return (
		<div
			className="helper-lines"
			style={{
				position: "absolute",
				top: 0,
				left: 0,
				right: 0,
				bottom: 0,
				pointerEvents: "none",
			}}
		>
			{/* Horizontal guide lines */}
			{horizontalLines.map((y, i) => (
				<div
					key={`h-${i}`}
					style={{
						position: "absolute",
						left: 0,
						right: 0,
						top: y * transform.zoom + transform.y,
						height: lineWidth,
						backgroundColor: color,
						zIndex,
						pointerEvents: "none",
					}}
				/>
			))}

			{/* Vertical guide lines */}
			{verticalLines.map((x, i) => (
				<div
					key={`v-${i}`}
					style={{
						position: "absolute",
						top: 0,
						bottom: 0,
						left: x * transform.zoom + transform.x,
						width: lineWidth,
						backgroundColor: color,
						zIndex,
						pointerEvents: "none",
					}}
				/>
			))}
		</div>
	)
}

export default HelperLines

