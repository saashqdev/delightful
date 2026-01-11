import { IconPlus } from "@douyinfe/semi-icons"
import { Popover } from "antd"
import { useUpdateEffect } from "ahooks"
import clsx from "clsx"
import React, { memo, useCallback, useMemo } from "react"
import { EdgeProps, getBezierPath } from "reactflow"
import FlowPopup from "../components/FlowPopup"
import { prefix } from "../constants"
import styles from "./index.module.less"
import useEdgeSelected from "../hooks/useEdgeSelected"

// Custom edge component prop types
export interface CustomEdgeProps extends EdgeProps {
	data?: {
		allowAddOnLine?: boolean
		[key: string]: any
	}
}

// Edge component implementation
function CustomEdgeComponent({
	id,
	sourceX,
	sourceY,
	targetX,
	targetY,
	sourcePosition,
	targetPosition,
	markerEnd,
	source,
	sourceHandleId,
	target,
	style,
	data,
}: CustomEdgeProps) {
	// Track whether the edge is selected
	const { isSelected } = useEdgeSelected(id)
	const [popupOpen, setPopupOpen] = React.useState(false)
	const [isHovered, setIsHovered] = React.useState(false)

	// Get edge style and path
	const allowAddOnLine = data?.allowAddOnLine

	// Use useMemo to cache calculated edge path
	const edgePath = useMemo(() => {
		const [path] = getBezierPath({
			sourceX: sourceX + 5,
			sourceY,
			sourcePosition,
			targetX: targetX - 5,
			targetY,
			targetPosition,
		})
		return path
	}, [sourceX, sourceY, sourcePosition, targetX, targetY, targetPosition])

	// Position for adding icon
	const iconPosition = useMemo(
		() => ({
			x: (sourceX + targetX) / 2 - 12,
			y: (sourceY + targetY) / 2 - 12,
		}),
		[sourceX, targetX, sourceY, targetY],
	)

	// Close popup when edge changes from selected to unselected
	useUpdateEffect(() => {
		if (!isSelected) {
			setPopupOpen(false)
		}
	}, [isSelected])

	// Optimize event handler functions
	const handleMouseEnter = useCallback(() => {
		setIsHovered(true)
	}, [])

	const handleMouseLeave = useCallback(() => {
		setIsHovered(false)
	}, [])

	const togglePopup = useCallback(() => {
		setPopupOpen((prev) => !prev)
	}, [])

	return (
		<>
			{/* Primary edge path */}
			<path
				id={id}
				d={edgePath}
				className="react-flow__edge-path"
				markerEnd={markerEnd}
				fillRule="evenodd"
				style={{ ...style }}
			/>

			{/* Interaction area - wider transparent path for better click/hover experience */}
			<path
				style={{ ...style, stroke: "transparent", strokeWidth: 48 }}
				d={edgePath}
				className="react-flow__edge-path-selector"
				markerEnd={undefined}
				fillRule="evenodd"
				onMouseEnter={handleMouseEnter}
				onMouseLeave={handleMouseLeave}
			/>

			{/* Icon to add a node */}
			{allowAddOnLine && (
				<foreignObject
					x={iconPosition.x}
					y={iconPosition.y}
					width={24}
					height={24}
					onMouseEnter={handleMouseEnter}
					className={clsx(styles.addIconWrapper)}
					onMouseLeave={handleMouseLeave}
					style={{ display: isHovered ? "block" : "none" }}
				>
					<Popover
						content={
							<FlowPopup
								source={source}
								target={target}
								edgeId={id}
								// @ts-ignore
								sourceHandle={sourceHandleId}
							/>
						}
						placement="right"
						showArrow={false}
						overlayClassName={clsx(styles.popup, `${prefix}popup`)}
						open={popupOpen}
					>
						<IconPlus
							className={clsx(styles.addIcon, `${prefix}add-icon`)}
							style={{
								background: style?.stroke,
							}}
							onClick={togglePopup}
						/>
					</Popover>
				</foreignObject>
			)}
		</>
	)
}

// Custom comparator controlling when the edge rerenders
const propsAreEqual = (prevProps: CustomEdgeProps, nextProps: CustomEdgeProps) => {
	// Rerender when edge id changes
	if (prevProps.id !== nextProps.id) return false

	// Rerender when position changes
	if (
		prevProps.sourceX !== nextProps.sourceX ||
		prevProps.sourceY !== nextProps.sourceY ||
		prevProps.targetX !== nextProps.targetX ||
		prevProps.targetY !== nextProps.targetY ||
		prevProps.sourcePosition !== nextProps.sourcePosition ||
		prevProps.targetPosition !== nextProps.targetPosition
	)
		return false

	// Rerender when connected nodes or handles change
	if (
		prevProps.source !== nextProps.source ||
		prevProps.target !== nextProps.target ||
		prevProps.sourceHandleId !== nextProps.sourceHandleId
	)
		return false

	// Rerender when style or markerEnd changes
	if (
		prevProps.markerEnd !== nextProps.markerEnd ||
		JSON.stringify(prevProps.style) !== JSON.stringify(nextProps.style)
	)
		return false

	// Check allowAddOnLine in data
	if (prevProps.data?.allowAddOnLine !== nextProps.data?.allowAddOnLine) return false

	// Otherwise no rerender needed
	return true
}

// Wrap with memo to avoid unnecessary rerenders
const CustomEdge = memo(CustomEdgeComponent, propsAreEqual)

export default CustomEdge

