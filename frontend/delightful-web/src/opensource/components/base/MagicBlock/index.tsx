import type { HTMLAttributes, PropsWithChildren } from "react"
import { useRef } from "react"

function DelightfulEditBlock({ children, ...props }: PropsWithChildren<HTMLAttributes<HTMLDivElement>>) {
	const ref = useRef<HTMLDivElement>(null)

	return (
		<div ref={ref} contentEditable {...props}>
			{children}
		</div>
	)
}

export default DelightfulEditBlock
