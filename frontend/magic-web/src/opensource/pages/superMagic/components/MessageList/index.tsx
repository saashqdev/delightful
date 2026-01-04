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
	// 定义顶部保留的最小距离，单位像素
	const MIN_TOP_DISTANCE = 200

	// 保存滚动位置的函数 - 使用百分比
	const saveScrollPosition = () => {
		if (!nodesPanelRef.current) return
		const element = nodesPanelRef.current
		// 计算滚动百分比：当前滚动位置 / 可滚动的总高度
		const scrollableHeight = element.scrollHeight - element.clientHeight
		scrollPercentRef.current = scrollableHeight > 0 ? element.scrollTop / scrollableHeight : 0
	}

	// 优化滚动逻辑，使用 RAF 确保平滑滚动，并根据滚动条位置决定是否保持位置
	useDeepCompareEffect(() => {
		if (!nodesPanelRef.current || !data || data.length === 0) return

		const element = nodesPanelRef.current
		const isAtBottom = element.scrollHeight - element.scrollTop - element.clientHeight <= 100

		if (isAtBottom) {
			isProgrammaticScrollRef.current = true
			// 如果在底部，则自动滚动到新内容
			saveScrollPosition()
			requestAnimationFrame(() => {
				setTimeout(() => {
					element.scrollTop = element.scrollHeight
					// element.scrollTo({
					// 	top: element.scrollHeight,
					// 	behavior: "smooth", // 可选
					// })

					setTimeout(() => {
						isProgrammaticScrollRef.current = false
					}, 100)
				}, 100)
			})
		} else {
			// 不在底部时，根据之前保存的百分比恢复滚动位置
			isProgrammaticScrollRef.current = true
			saveScrollPosition() // 保存当前百分比，以便之后恢复
			requestAnimationFrame(() => {
				setTimeout(() => {
					// 根据百分比设置滚动位置
					const scrollableHeight = element.scrollHeight - element.clientHeight
					const calculatedScrollTop = scrollPercentRef.current * scrollableHeight

					// 如果计算出的滚动位置非常接近顶部，则保留最小距离
					const isNearTop = calculatedScrollTop < MIN_TOP_DISTANCE
					// 如果接近顶部，保留最小距离；否则按照正常百分比计算
					element.scrollTop = isNearTop ? MIN_TOP_DISTANCE : calculatedScrollTop

					setTimeout(() => {
						isProgrammaticScrollRef.current = false
					}, 100)
				}, 100)
			})
		}
	}, [data])

	// 当selectedNodeId变化时，滚动到底部
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
				// 	behavior: "smooth", // 可选
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

	// 添加滚动监听，当滚动到顶部时触发handlePullMoreMessage
	useDeepCompareEffect(() => {
		const handleScroll = throttle(() => {
			if (!nodesPanelRef.current) return
			const element = nodesPanelRef.current

			// 获取当前滚动位置
			const currentScrollTop = element.scrollTop

			// 判断滚动方向：向上滚动为true，向下滚动为false
			const isScrollingUp = currentScrollTop < lastScrollTopRef.current

			// 如果不是向上滚动，直接返回，不执行后续逻辑
			if (!isScrollingUp) {
				// 仍然更新上次滚动位置
				lastScrollTopRef.current = currentScrollTop
				return
			}

			// 保存当前滚动位置用于下次比较
			lastScrollTopRef.current = currentScrollTop

			// 保存用户手动滚动后的位置
			saveScrollPosition()

			// 只有在滚动到一定位置时才触发加载更多消息
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
			// 初始化上一次滚动位置
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

// 使用 memo 并提供比较函数
const MemoizedMessageList = memo(MessageList)

export default MemoizedMessageList
