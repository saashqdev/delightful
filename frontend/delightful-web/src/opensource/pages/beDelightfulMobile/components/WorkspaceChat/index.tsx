import { useDeepCompareEffect } from "ahooks"
import { throttle } from "lodash-es"
import { memo, useRef, useState } from "react"
import MessageList from "@/opensource/pages/beDelightful/components/MessageList/index"
import type { MessagePanelProps } from "../MessagePanel"
import MessagePanel from "@/opensource/pages/beDelightful/components/MessagePanel/MessagePanel"
import { useStyles } from "./styles"

interface WorkspaceChatProps extends MessagePanelProps {
	data?: any
	selectedThreadInfo?: any
	handlePullMoreMessage?: () => void
	showLoading?: boolean
	onSelectDetail?: (detail: any) => void
	isEmptyStatus?: boolean
}

export default memo(function WorkspaceChat(props: WorkspaceChatProps) {
	const {
		data,
		selectedThreadInfo,
		handlePullMoreMessage,
		showLoading,
		onSelectDetail,
		isEmptyStatus,
		setFileList,
		...messagePanelProps
	} = props
	const { styles, cx } = useStyles()
	const footerRef = useRef<HTMLDivElement>(null)
	const nodesPanelRef = useRef<HTMLDivElement | null>(null)
	const [isProgrammaticScroll, setIsProgrammaticScroll] = useState(false)
	const scrollHeightRef = useRef<number>(0)
	const scrollTopRef = useRef<number>(0)

	// Function to save scroll position
	const saveScrollPosition = () => {
		if (!nodesPanelRef.current) return
		const element = nodesPanelRef.current
		scrollHeightRef.current = element.scrollHeight
		scrollTopRef.current = element.scrollTop
	}

	// Optimize scroll logic, use RAF to ensure smooth scrolling, and decide whether to maintain position based on scrollbar position
	useDeepCompareEffect(() => {
		if (!nodesPanelRef.current || !data || data.length === 0) return

		const element = nodesPanelRef.current
		// Check if scrollbar is at the bottom or near the bottom (within 50px from bottom)
		const isAtBottom = element.scrollHeight - element.scrollTop - element.clientHeight <= 50

		if (isAtBottom) {
			// If at bottom, auto-scroll to new content
			setIsProgrammaticScroll(true)
			requestAnimationFrame(() => {
				element.scrollTo({
					top: element.scrollHeight,
					behavior: "smooth", // optional
				})
				setIsProgrammaticScroll(false)
				saveScrollPosition()
			})
		} else if (scrollHeightRef.current > 0) {
			// If not at bottom and has previous scroll height record
			// Calculate scroll position offset to maintain relative position
			const heightDiff = element.scrollHeight - scrollHeightRef.current
			if (heightDiff > 0) {
				setIsProgrammaticScroll(true)
				requestAnimationFrame(() => {
					element.scrollTo({
						top: scrollTopRef.current + heightDiff,
						behavior: "auto", // optional
					})
					setIsProgrammaticScroll(false)
					saveScrollPosition()
					// setTimeout(() => {

					// }, 100)
				})
			}
		}

		// Save scroll position on initial load
		if (scrollHeightRef.current === 0) {
			saveScrollPosition()
		}
	}, [data.length])

	// When selectedNodeId changes, scroll to bottom
	useDeepCompareEffect(() => {
		if (!nodesPanelRef.current || !selectedThreadInfo?.id) return

		const element = nodesPanelRef.current
		setIsProgrammaticScroll(true)
		setTimeout(() => {
			element.scrollTo({
				top: element.scrollHeight,
				behavior: "smooth", // optional
			})
			setTimeout(() => {
				setIsProgrammaticScroll(false)
				saveScrollPosition()
			}, 100)
		}, 300)
	}, [selectedThreadInfo])

	// Add scroll listener to trigger handlePullMoreMessage when scrolled to top
	useDeepCompareEffect(() => {
		const handleScroll = throttle(() => {
			console.log("handleScroll triggered", nodesPanelRef.current?.scrollTop)
			if (!nodesPanelRef.current || isProgrammaticScroll) return

			// Save scroll position after user manual scroll
			saveScrollPosition()

			if (nodesPanelRef.current.scrollTop <= 300 && handlePullMoreMessage) {
				handlePullMoreMessage()
			}
		}, 500)

		const element = nodesPanelRef.current
		if (element && handlePullMoreMessage) {
			element.addEventListener("scroll", handleScroll)
		}

		return () => {
			if (element && handlePullMoreMessage) {
				element.removeEventListener("scroll", handleScroll)
			}
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [isProgrammaticScroll, nodesPanelRef])
	return (
		<div className={styles.container}>
			<div className={styles.body} ref={nodesPanelRef}>
				<MessageList
					data={data}
					setSelectedDetail={onSelectDetail}
					selectedThreadInfo={selectedThreadInfo}
					className={cx(isEmptyStatus && styles.emptyMessageWelcome)}
					handlePullMoreMessage={handlePullMoreMessage}
					showLoading={showLoading}
				/>
			</div>
			<div ref={footerRef} className={styles.footer}>
				<div className={styles.messagePanel}>
					<MessagePanel
						// {...messagePanelProps}
						showLoading={showLoading}
						selectedThreadInfo={selectedThreadInfo}
						setFileList={setFileList}
						onSendMessage={messagePanelProps.onSubmit}
						fileList={messagePanelProps.fileList}
						taskData={messagePanelProps.taskData}
						topicModeInfo={messagePanelProps.topicModeInfo}
						// textAreaWrapperClassName={messagePanelProps.textAreaWrapperClassName}
					/>
				</div>
			</div>
		</div>
	)
})
