import { useDeepCompareEffect } from "ahooks"
import { cx } from "antd-style"
import { throttle } from "lodash-es"
import { memo, useRef } from "react"
import LoadingMessage from "../LoadingMessage"
import Empty from "./components/Empty"
import Node from "./components/Node"
import { useStyles } from "./style"

interface ManusViewerProps {
	data: Array<any>
	setSelectedDetail?: (detail: any) => void
	className?: string
	isEmptyStatus?: boolean
	selectedThreadInfo: any
	handlePullMoreMessage?: (selectedThreadInfo: any) => void
	showLoading?: boolean
}

const MessageList = ({
	data,
	setSelectedDetail,
	selectedThreadInfo,
	className,
	isEmptyStatus = false,
	handlePullMoreMessage,
	showLoading,
}: ManusViewerProps) => {
	const nodesPanelRef = useRef<HTMLDivElement | null>(null)
	const { styles } = useStyles()
	const scrollPercentRef = useRef<number>(0)
	const isProgrammaticScrollRef = useRef<boolean>(false)
	const lastScrollTopRef = useRef<number>(0)
	// Define minimum distance to keep at top, in pixels
	const MIN_TOP_DISTANCE = 200

	// Save scroll position function - using percentage
	const saveScrollPosition = () => {
		if (!nodesPanelRef.current) return
		const element = nodesPanelRef.current
		// Calculate scroll percentage: current scroll position / total scrollable height
		const scrollableHeight = element.scrollHeight - element.clientHeight
		scrollPercentRef.current = scrollableHeight > 0 ? element.scrollTop / scrollableHeight : 0
	}

	// Optimize scroll logic, use RAF to ensure smooth scrolling, and decide whether to maintain position based on scrollbar position
	useDeepCompareEffect(() => {
		if (!nodesPanelRef.current || !data || data.length === 0) return

		const element = nodesPanelRef.current
		const isAtBottom = element.scrollHeight - element.scrollTop - element.clientHeight <= 100

		if (isAtBottom) {
			isProgrammaticScrollRef.current = true
			// If at bottom, auto-scroll to new content
			saveScrollPosition()
			requestAnimationFrame(() => {
				setTimeout(() => {
					element.scrollTop = element.scrollHeight
					// element.scrollTo({
					// 	top: element.scrollHeight,
					// 	behavior: "smooth", // optional
					// })

					setTimeout(() => {
						isProgrammaticScrollRef.current = false
					}, 100)
				}, 100)
			})
		} else {
			// When not at bottom, restore scroll position based on previously saved percentage
			isProgrammaticScrollRef.current = true
			saveScrollPosition() // Save current percentage for later restoration
			requestAnimationFrame(() => {
				setTimeout(() => {
					// Set scroll position based on percentage
					const scrollableHeight = element.scrollHeight - element.clientHeight
					const calculatedScrollTop = scrollPercentRef.current * scrollableHeight

					// If calculated scroll position is very close to top, preserve minimum distance
					const isNearTop = calculatedScrollTop < MIN_TOP_DISTANCE
					// If near top, preserve minimum distance; otherwise calculate based on normal percentage
					element.scrollTop = isNearTop ? MIN_TOP_DISTANCE : calculatedScrollTop

					setTimeout(() => {
						isProgrammaticScrollRef.current = false
					}, 100)
				}, 100)
			})
		}
	}, [data])

	// When selectedNodeId changes, scroll to bottom
	useDeepCompareEffect(() => {
		if (!nodesPanelRef.current || !selectedThreadInfo?.id) return undefined

		const element = nodesPanelRef.current
		isProgrammaticScrollRef.current = true
		setTimeout(() => {
			setTimeout(() => {
				saveScrollPosition()
				element.scrollTop = element.scrollHeight
				// element.scrollTo({
				// 	top: element.scrollHeight,
				// 	behavior: "smooth", // optional
				// })
				setTimeout(() => {
					isProgrammaticScrollRef.current = false
				}, 100)
			}, 100)
		}, 300)

		return () => {
			scrollPercentRef.current = 0
			isProgrammaticScrollRef.current = true
		}
	}, [selectedThreadInfo])

	// Add scroll listener to trigger handlePullMoreMessage when scrolling to top
	useDeepCompareEffect(() => {
		const handleScroll = throttle(() => {
			if (!nodesPanelRef.current) return
			const element = nodesPanelRef.current

			// Get current scroll position
			const currentScrollTop = element.scrollTop

			// Determine scroll direction: true for scrolling up, false for scrolling down
			const isScrollingUp = currentScrollTop < lastScrollTopRef.current

			// If not scrolling up, return directly without executing subsequent logic
			if (!isScrollingUp) {
				// Still update last scroll position
				lastScrollTopRef.current = currentScrollTop
				return
			}

			// Save current scroll position for next comparison
			lastScrollTopRef.current = currentScrollTop

			// Save position after user manually scrolls
			saveScrollPosition()

			// Only trigger load more messages when scrolled to a certain position
			if (
				element.scrollTop <= element.scrollHeight * 0.7 &&
				handlePullMoreMessage &&
				!isProgrammaticScrollRef.current
			) {
				handlePullMoreMessage(selectedThreadInfo)
			}
		}, 500)

		const element = nodesPanelRef.current
		if (element && handlePullMoreMessage) {
			// Initialize last scroll position
			lastScrollTopRef.current = element.scrollTop
			element.addEventListener("scroll", handleScroll)
		}

		return () => {
			if (element && handlePullMoreMessage) {
				element.removeEventListener("scroll", handleScroll)
			}
		}
	}, [selectedThreadInfo])
	return (
		<div className={cx(styles.container, className)}>
			<div className={styles.nodesPanel} ref={nodesPanelRef}>
				{data.length > 0 || !isEmptyStatus ? (
					data.map((node: any, index: number) => {
						return (
							<Node
								key={`${node.seq_id || "default-key"}`}
								node={node}
								prevNode={index > 0 ? data[index - 1] : undefined}
								onSelectDetail={setSelectedDetail}
								isSelected={node.topic_id === selectedThreadInfo?.id}
							/>
						)
					})
				) : (
					<Empty />
				)}
				{(data?.length === 1 || showLoading) && <LoadingMessage />}
			</div>
		</div>
	)
}

// Use memo and provide comparison function
const MemoizedMessageList = memo(MessageList)

export default MemoizedMessageList
