import React, { memo, useCallback, useMemo } from "react"
import { SmoothStepEdge, getSmoothStepPath } from "reactflow"
import { useUpdateEffect } from "ahooks"
import { IconPlus } from "@douyinfe/semi-icons"
import { Popover } from "antd"
import clsx from "clsx"
import { prefix } from "../constants"
import styles from "./index.module.less"
import useEdgeSelected from "../hooks/useEdgeSelected"
import { CustomEdgeProps } from "./CustomEdge"
import FlowPopup from "../components/FlowPopup"

function CustomSmoothEdgeComponent({
	sourceX,
	sourceY,
	targetX,
	targetY,
	sourcePosition,
	targetPosition,
	id,
	source,
	target,
	style,
	data,
	markerEnd,
	sourceHandleId,
}: CustomEdgeProps) {
	// Use hook to get edge selection state
	const { isSelected } = useEdgeSelected(id)
	const [popupOpen, setPopupOpen] = React.useState(false)
	const [isHovered, setIsHovered] = React.useState(false)

	// Access edge styling and path data
	const allowAddOnLine = data?.allowAddOnLine

	// Compute edge path via getSmoothStepPath
	const edgePath = useMemo(() => {
		const [path] = getSmoothStepPath({
			sourceX,
			sourceY,
			sourcePosition,
			targetX,
			targetY,
			targetPosition,
		})
		return path
	}, [sourceX, sourceY, sourcePosition, targetX, targetY, targetPosition])

	// Position of the add icon
	const iconPosition = useMemo(
		() => ({
			x: (sourceX + targetX) / 2 - 12,
			y: (sourceY + targetY) / 2 - 12,
		}),
		[sourceX, targetX, sourceY, targetY],
	)

	// Close popup when edge loses selection
	useUpdateEffect(() => {
		if (!isSelected) {
			setPopupOpen(false)
		}
	}, [isSelected])

	// Lightweight handlers
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
			{/* Use SmoothStepEdge as the base edge */}
			<SmoothStepEdge
				id={id}
				source={source}
				target={target}
				sourceX={sourceX}
				sourceY={sourceY}
				targetX={targetX}
				targetY={targetY}
				sourcePosition={sourcePosition}
				targetPosition={targetPosition}
				style={style}
				markerEnd={markerEnd}
			/>

			{/* Transparent interaction layer for better hover */}
			<path
				id={`${id}-selector`}
				d={edgePath}
				className="react-flow__edge-path-selector"
				style={{ stroke: "transparent", strokeWidth: 48 }}
				onMouseEnter={handleMouseEnter}
				onMouseLeave={handleMouseLeave}
			/>

			{/* Icon for adding a node on the edge */}
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

// Custom comparator to control when the edge rerenders
const propsAreEqual = (prevProps: CustomEdgeProps, nextProps: CustomEdgeProps) => {
	// Rerender when edge ID changes
	if (prevProps.id !== nextProps.id) return false

	// Rerender when position info changes
	if (
		prevProps.sourceX !== nextProps.sourceX ||
		prevProps.sourceY !== nextProps.sourceY ||
		prevProps.targetX !== nextProps.targetX ||
		prevProps.targetY !== nextProps.targetY ||
		prevProps.sourcePosition !== nextProps.sourcePosition ||
		prevProps.targetPosition !== nextProps.targetPosition
	)
		return false

	// Rerender when source/target or handle changes
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

	// Check allowAddOnLine flag in data
	if (prevProps.data?.allowAddOnLine !== nextProps.data?.allowAddOnLine) return false

	// Otherwise no rerender needed
	return true
}

// Wrap with memo to avoid unnecessary renders
const CustomSmoothEdge = memo(CustomSmoothEdgeComponent, propsAreEqual)

export default CustomSmoothEdge

