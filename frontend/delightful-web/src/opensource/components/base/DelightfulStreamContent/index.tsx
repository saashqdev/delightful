import { useTyping } from "@/hooks"
import { useUpdateEffect } from "ahooks"
import type { HTMLAttributes, ReactNode } from "react"
import { memo } from "react"

interface DelightfulStreamContentProps extends Omit<HTMLAttributes<HTMLDivElement>, "children"> {
	content: string
	children?: (content: string) => ReactNode
}

const DelightfulStreamContent = memo(
	({ content, children, ...props }: DelightfulStreamContentProps) => {
		const { content: typingContent, add } = useTyping(content)

		useUpdateEffect(() => {
			add(content)
		}, [content, add])

		return <div {...props}>{children ? children(typingContent) : typingContent}</div>
	},
)

export default DelightfulStreamContent
