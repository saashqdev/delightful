import { Tooltip } from "antd"
import type { HTMLAttributes } from "react"
import { useRef, useState } from "react"

export type DelightfulEllipseWithTooltipProps = HTMLAttributes<HTMLDivElement> & {
	text: string
	maxWidth: string // Maximum text width; overflow is truncated with ellipsis
}

function DelightfulEllipseWithTooltip({ text, maxWidth, ...props }: DelightfulEllipseWithTooltipProps) {
	const textRef = useRef<HTMLDivElement>(null)
	const [isOverflowed, setIsOverflowed] = useState(false)

	// Check whether the text overflows
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
