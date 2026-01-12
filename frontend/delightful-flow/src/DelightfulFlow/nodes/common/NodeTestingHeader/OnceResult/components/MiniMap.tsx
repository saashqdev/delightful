import { NodeTestConfig } from "@/DelightfulFlow/context/NodeTesingContext/Context"
import clsx from "clsx"
import React, { useEffect, useRef, useState } from "react"
import styles from "../../index.module.less"
import { TestingResultRow } from "../../useTesting"
import { isComplexValue } from "../utils"

// Mini-map navigation component
export const MiniMap = ({
	contentRef,
	inputList,
	outputList,
	debugLogs,
	allowDebug,
	testingResult,
}: {
	contentRef: React.RefObject<HTMLDivElement>
	inputList: TestingResultRow[]
	outputList: TestingResultRow[]
	debugLogs?: TestingResultRow[]
	allowDebug: boolean
	testingResult?: NodeTestConfig
}) => {
	const [scrollPosition, setScrollPosition] = useState(0)
	const [visibleRatio, setVisibleRatio] = useState(0.3) // Ratio of visible area to total content
	const [isDragging, setIsDragging] = useState(false)
	const [isHovered, setIsHovered] = useState(false)
	const [itemPositions, setItemPositions] = useState<{ [key: string]: number }>({})
	const miniMapRef = useRef<HTMLDivElement>(null)

	// Calculate relative position of each item in scroll container
	useEffect(() => {
		const content = contentRef.current
		if (!content) return

		// Wait for content rendering to complete
		setTimeout(() => {
			// Get position information for each item in scroll container
			const items = content.querySelectorAll("[data-item-key]")
			const contentHeight = content.scrollHeight
			const positions: { [key: string]: number } = {}

			items.forEach((item) => {
				const key = item.getAttribute("data-item-key")
				if (key) {
				// Calculate item top position as percentage of total container height
					const rect = item.getBoundingClientRect()
					const contentRect = content.getBoundingClientRect()
					const offsetTop = rect.top - contentRect.top + content.scrollTop
					positions[key] = offsetTop / contentHeight
				}
			})

			setItemPositions(positions)
		}, 50)
	}, [contentRef, inputList, outputList, debugLogs])

	// Listen to scroll event, update indicator position on mini-map
	useEffect(() => {
		const content = contentRef.current
		if (!content) return

		const handleScroll = () => {
			const { scrollTop, scrollHeight, clientHeight } = content
			const ratio = clientHeight / scrollHeight
			// Fix: Use precise percentage position instead of relying on scrollbar position
			const position = scrollTop / scrollHeight

			setScrollPosition(position)
			setVisibleRatio(ratio)
		}

		// Initial calculation
		handleScroll()

		content.addEventListener("scroll", handleScroll)
		return () => {
			content.removeEventListener("scroll", handleScroll)
		}
	}, [contentRef])

	// Handle clicks and drags on mini-map
	const handleMiniMapInteraction = (e: React.MouseEvent | React.TouchEvent) => {
		const miniMap = miniMapRef.current
		const content = contentRef.current
		if (!miniMap || !content) return

		// Get mouse/touch position on mini-map
		let clientY = 0
		if ("clientY" in e) {
			clientY = e.clientY // Mouse event
		} else {
			clientY = e.touches[0].clientY // Touch event
		}

		const { top, height } = miniMap.getBoundingClientRect()
		const relativePosition = (clientY - top) / height

		// Fix: Directly set to corresponding percentage position in content
		content.scrollTop = relativePosition * content.scrollHeight
	}

	// Jump to specified key position
	const jumpToKey = (key: string) => {
		const content = contentRef.current
		if (!content || !itemPositions[key]) return

		const position = itemPositions[key]
		const scrollPosition = position * content.scrollHeight
		content.scrollTop = scrollPosition
	}

	const handleMouseDown = (e: React.MouseEvent) => {
		setIsDragging(true)
		handleMiniMapInteraction(e)

		// Prevent text selection
		e.preventDefault()
	}

	const handleMouseMove = (e: React.MouseEvent) => {
		if (isDragging) {
			handleMiniMapInteraction(e)
		}
	}

	const handleMouseUp = () => {
		setIsDragging(false)
	}

	// Add global event listeners on component mount
	useEffect(() => {
		if (isDragging) {
			document.addEventListener("mousemove", handleMouseMove as any)
			document.addEventListener("mouseup", handleMouseUp)
		}

		return () => {
			document.removeEventListener("mousemove", handleMouseMove as any)
			document.removeEventListener("mouseup", handleMouseUp)
		}
	}, [isDragging])

	// Render data items, highlight complex structures
	const renderItems = (items: TestingResultRow[], isError: boolean = false, section: string) => {
		return items?.map?.((item, index) => {
			const isComplex = isComplexValue(item.value)
			const itemKey = `${section}-${item.key}-${index}`
			// Calculate relative position
			const position = itemPositions[itemKey] || 0

			return (
				<div
					key={index}
					className={clsx(styles.miniItem, {
						[styles.miniError]: isError,
						[styles.miniComplex]: isComplex,
					})}
					title={item.key}
					style={{
						top: `${position * 100}%`,
						position: "absolute",
						width: "calc(100% - 8px)",
						left: "4px",
					}}
					onClick={(e) => {
						e.stopPropagation()
						jumpToKey(itemKey)
					}}
				>
					{/* Display all key names, not just complex structures */}
					<span
						className={clsx(styles.miniItemKey, {
							[styles.miniItemKeyComplex]: isComplex,
						})}
						onClick={(e) => {
							e.stopPropagation()
							jumpToKey(itemKey)
						}}
					>
						{item.key}
					</span>
				</div>
			)
		})
	}

	// Create content thumbnail preview elements
	const renderMiniContent = () => {
		// Calculate item count for each section
		const inputCount = inputList?.length || 0
		const outputCount = outputList?.length || 0
		const debugCount = debugLogs?.length || 0

		// Calculate height ratio for each section
		const totalItems = inputCount + outputCount + debugCount
		const inputRatio = totalItems > 0 ? inputCount / totalItems : 0
		const outputRatio = totalItems > 0 ? outputCount / totalItems : 0
		const debugRatio = totalItems > 0 ? debugCount / totalItems : 0

		return (
			<div className={styles.miniMapContent}>
				{/* Input section thumbnail, no title displayed */}
				<div className={styles.miniSection} style={{ height: `${inputRatio * 100}%` }}>
					<div className={styles.miniItems}>{renderItems(inputList, false, "input")}</div>
				</div>

				{/* Output section thumbnail, no title displayed */}
				<div className={styles.miniSection} style={{ height: `${outputRatio * 100}%` }}>
					<div className={styles.miniItems}>
						{renderItems(outputList, !testingResult?.success, "output")}
					</div>
				</div>

				{/* Debug log section thumbnail, no title displayed */}
				{allowDebug && debugLogs && debugLogs.length > 0 && (
					<div className={styles.miniSection} style={{ height: `${debugRatio * 100}%` }}>
						<div className={styles.miniItems}>
							{renderItems(debugLogs, false, "debug")}
						</div>
					</div>
				)}
			</div>
		)
	}

	return (
		<div
			className={clsx(styles.miniMap, {
				[styles.miniMapHovered]: isHovered || isDragging,
			})}
			ref={miniMapRef}
			onMouseDown={handleMouseDown}
			onTouchStart={handleMiniMapInteraction as any}
			onMouseEnter={() => setIsHovered(true)}
			onMouseLeave={() => setIsHovered(false)}
		>
			{/* Show content thumbnail */}
			{renderMiniContent()}

			{/* Indicator for visible area */}
			<div
				className={styles.miniMapIndicator}
				style={{
					top: `${scrollPosition * 100}%`,
					height: `${visibleRatio * 100}%`,
					opacity: isDragging ? 0.8 : 0.5,
				}}
			/>
		</div>
	)
}

