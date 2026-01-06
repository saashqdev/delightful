import clsx from "clsx"
import React, { memo } from "react"
import { prefix } from "@/DelightfulFlow/constants"
import styles from "../index.module.less"
import DebuggerComp from "../../common/components/DebuggerComp"
import NodeTestingHeader from "../../common/NodeTestingHeader"
import _ from "lodash"

interface NodeWrapperProps {
	id: string
	isSelected: boolean
	onNodeWrapperClick: (e: React.MouseEvent) => void
	defaultStyle: React.CSSProperties
	commonStyle: React.CSSProperties | undefined
	nodeStyleMap: any
	type: string
	onDragLeave: (e: React.DragEvent) => void
	onDragOver: (e: React.DragEvent) => void
	onDrop: (e: React.DragEvent) => void
	children: React.ReactNode
}

// Internal implementation component
const NodeWrapperImpl = ({
	id,
	isSelected,
	onNodeWrapperClick,
	defaultStyle,
	commonStyle,
	nodeStyleMap,
	type,
	onDragLeave,
	onDragOver,
	onDrop,
	children,
}: NodeWrapperProps) => {
	return (
		<div
			className={clsx(styles.baseNodeWrapper, `${prefix}base-node-wrapper`, {
				[styles.isSelected]: isSelected,
				selected: isSelected,
			})}
			onClick={onNodeWrapperClick}
			style={{
				...defaultStyle,
				...(commonStyle || {}),
				...(nodeStyleMap?.[type] || {}),
			}}
			onDragLeave={onDragLeave}
			onDragOver={onDragOver}
			onDrop={onDrop}
		>
			<DebuggerComp id={id} />
			<NodeTestingHeader />
			{children}
		</div>
	)
}

// Custom comparator ignoring children changes
const arePropsEqual = (prevProps: NodeWrapperProps, nextProps: NodeWrapperProps) => {
	// Extract props other than children
	const { ...prevRest } = prevProps
	const { ...nextRest } = nextProps

	// Check basic prop changes; prioritize common ones for performance
	if (
		prevRest.id !== nextRest.id ||
		prevRest.isSelected !== nextRest.isSelected ||
		prevRest.type !== nextRest.type
	) {
		return false
	}

	// Check event handlersideally wrapped with useCallback so compare by reference
	// Exclude them from comparison for safety
	const {
		onNodeWrapperClick: prevClick,
		onDragLeave: prevLeave,
		onDragOver: prevOver,
		onDrop: prevDrop,
		...prevRestWithoutHandlers
	} = prevRest

	const {
		onNodeWrapperClick: nextClick,
		onDragLeave: nextLeave,
		onDragOver: nextOver,
		onDrop: nextDrop,
		...nextRestWithoutHandlers
	} = nextRest

	// Deep-compare remaining props
	return _.isEqual(prevRestWithoutHandlers, nextRestWithoutHandlers)
}

// Wrap component with memo using a custom comparator
const NodeWrapper = memo(NodeWrapperImpl, arePropsEqual)

export default NodeWrapper

