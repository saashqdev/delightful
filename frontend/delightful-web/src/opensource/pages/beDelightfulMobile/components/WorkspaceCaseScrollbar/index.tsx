import { useSize } from "ahooks"
import { createStyles, cx } from "antd-style"
import { memo, useMemo, useRef } from "react"

interface WorkspaceCaseScrollbarProps {
	className?: string
	style?: React.CSSProperties
	scrollLeft?: number
	contentContainerRef?: React.RefObject<HTMLDivElement>
	contentRef?: React.RefObject<HTMLDivElement>
}

const useStyles = createStyles(({ token }) => ({
	scrollbar: {
		height: 5,
		width: 200,
		backgroundColor: token.delightfulColorUsages.fill[2],
		borderRadius: 1000,
		position: "relative",
	},
	bar: {
		position: "absolute",
		top: 0,
		left: 0,
		borderRadius: 1000,
		height: "100%",
		background: "linear-gradient(135deg, #FFAFC8 0%, #E08AFF 50%, #9FC3FF 100%)",
	},
}))

export default memo(function WorkspaceCaseScrollbar(props: WorkspaceCaseScrollbarProps) {
	const { className, style, scrollLeft = 0, contentContainerRef, contentRef } = props
	const { styles } = useStyles()

	const scrollBarContainerRef = useRef<HTMLDivElement>(null)
	const containerSize = useSize(contentContainerRef)
	const contentSize = useSize(contentRef)
	const scrollBarContainerSize = useSize(scrollBarContainerRef)

	const scrollBarSize = useMemo(() => {
		if (!contentSize || !containerSize || !scrollBarContainerSize) {
			return 0
		}
		return (containerSize.width / contentSize.width) * scrollBarContainerSize.width
	}, [containerSize, contentSize, scrollBarContainerSize])

	// Scrollbar position
	const scrollBarLeft = useMemo(() => {
		if (!contentSize || !containerSize || !scrollBarContainerSize) {
			return 0
		}
		// Calculate the scroll ratio
		const scrollRatio = scrollLeft / (contentSize.width - containerSize.width)
		// Calculate the maximum scrollbar movement distance
		const maxScrollBarMove = scrollBarContainerSize.width - scrollBarSize
		// Calculate the scrollbar movement distance based on scroll ratio
		return scrollRatio * maxScrollBarMove
	}, [containerSize, contentSize, scrollBarContainerSize, scrollBarSize, scrollLeft])

	// useEffect(() => {
	// 	console.log({
	// 		containerSize: containerSize?.width,
	// 		contentSize: contentSize?.width,
	// 		scrollBarContainerSize: scrollBarContainerSize?.width,
	// 		scrollBarSize,
	// 		scrollBarLeft,
	// 	})
	// }, [
	// 	containerSize?.width,
	// 	contentSize?.width,
	// 	scrollBarContainerSize?.width,
	// 	scrollBarLeft,
	// 	scrollBarSize,
	// ])

	if (!contentSize || !containerSize || contentSize.width <= containerSize.width) {
		return null
	}

	return (
		<div className={cx(styles.scrollbar, className)} style={style} ref={scrollBarContainerRef}>
			<div className={styles.bar} style={{ width: scrollBarSize, left: scrollBarLeft }} />
		</div>
	)
})
