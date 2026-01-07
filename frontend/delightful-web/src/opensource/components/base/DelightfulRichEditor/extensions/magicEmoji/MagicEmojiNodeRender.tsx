import type { NodeViewProps } from "@tiptap/core"
import { NodeViewWrapper } from "@tiptap/react"
import { memo } from "react"

const DelightfulEmojiNodeRender = memo((props: NodeViewProps) => {
	const {
		node: {
			attrs: { code, ns, suffix = ".png", size },
		},
		extension: {
			options: { basePath, HTMLAttributes },
		},
	} = props

	return (
		<NodeViewWrapper
			as="img"
			data-type="delightful-emoji"
			src={`${basePath}/${ns}${code}${suffix}`}
			draggable="false"
			width={size}
			onMouseDown={(e: MouseEvent) => {
				e.preventDefault()
				e.stopPropagation()
				return false
			}}
			{...HTMLAttributes}
		/>
	)
})

DelightfulEmojiNodeRender.displayName = "DelightfulEmojiNodeRender"
export default DelightfulEmojiNodeRender
