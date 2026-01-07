import { Tooltip } from "antd"
import type { HTMLAttributes } from "react"
import { useRef, useState } from "react"

interface EllipsisWithTooltipProps extends HTMLAttributes<HTMLDivElement> {
	text: string
	maxWidth: string // Maximum text width, excess will be replaced with ellipsis
}

const DelightfulEllipseWithTooltip = ({ text, maxWidth, ...props }: EllipsisWithTooltipProps) => {
	const textRef = useRef<HTMLDivElement>(null)
	const [isOverflowed, setIsOverflowed] = useState(false)

	// Check if text is overflowed
	const checkOverflow = () => {
		if (textRef.current) {
			const isOverflow = textRef.current.scrollWidth > textRef.current.clientWidth
			setIsOverflowed(isOverflow)
		}
	}

	return (
		<Tooltip title={isOverflowed ? text : ""} placement="top" arrow>
			<div
				{...props}
				ref={textRef}
				style={{
					whiteSpace: "nowrap",
					overflow: "hidden",
					textOverflow: "ellipsis",
					maxWidth,
				}}
				onMouseEnter={checkOverflow}
			>
				{text}
			</div>
		</Tooltip>
	)
}

export default DelightfulEllipseWithTooltip
