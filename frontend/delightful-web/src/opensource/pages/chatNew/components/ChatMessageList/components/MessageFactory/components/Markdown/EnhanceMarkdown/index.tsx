import Markdown from "markdown-to-jsx"
import { memo, useMemo, useRef } from "react"
import { nanoid } from "nanoid"
import MessageRenderProvider from "@/opensource/components/business/MessageRenderProvider"
import { useFontSize } from "@/opensource/providers/AppearanceProvider/hooks"
import { useStyles as useMarkdownStyles } from "./styles/markdown.style"
import type { MarkdownProps } from "./types"
import { useMarkdownConfig, useClassName } from "./hooks"
import { cx } from "antd-style"
import { useTyping } from "@/opensource/hooks/useTyping"
import { useUpdateEffect } from "ahooks"
import useStreamCursor from "./hooks/useStreamCursor"

/**
 * EnhanceMarkdown - 增强的Markdown渲染器
 * 支持流式渲染、代码高亮、数学公式等功能
 * based on markdown-to-jsx implement
 */
const EnhanceMarkdown = memo(
	function EnhanceMarkdown(props: MarkdownProps) {
		const {
			content,
			allowHtml = true,
			enableLatex = true,
			className,
			isSelf,
			isStreaming = false,
			hiddenDetail = false,
			components,
		} = props

		const { fontSize } = useFontSize()
		const classNameRef = useRef<string>(`markdown-${nanoid(10)}`)

		const { content: typedContent, typing, add, start, done } = useTyping(content as string)

		const lastContentRef = useRef(content ?? "")

		useUpdateEffect(() => {
			if (content) {
				add(content.substring(lastContentRef.current.length))
				lastContentRef.current = content
				if (!typing) {
					start()
				}
			}
		}, [content])

		useUpdateEffect(() => {
			if (!isStreaming) {
				done()
			}
		}, [isStreaming])

		const markdownRef = useRef<HTMLDivElement>(null)

		useStreamCursor(isStreaming || typing, typedContent ?? "", markdownRef)

		// Use styles hook
		const { styles: mdStyles } = useMarkdownStyles(
			useMemo(
				() => ({ fontSize: hiddenDetail ? 12 : fontSize, isSelf, hiddenDetail }),
				[fontSize, isSelf, hiddenDetail],
			),
		)

		// Use Markdown configuration hook
		const { options, preprocess } = useMarkdownConfig(
			useMemo(
				() => ({
					allowHtml: allowHtml && !hiddenDetail,
					enableLatex,
					components,
				}),
				[allowHtml, hiddenDetail, enableLatex, components],
			),
		)

		// Use class name handler hook
		const combinedClassName = useClassName({
			mdStyles,
			className: className || "",
			classNameRef,
		})

		const blocks = useMemo(
			() => preprocess(isStreaming || typing ? typedContent : content || ""),
			[isStreaming, typing, typedContent, content, preprocess],
		)

		// If no content, don't render
		if (blocks.length === 0) return null

		return (
			<MessageRenderProvider hiddenDetail={hiddenDetail}>
				<div className={cx(combinedClassName)} ref={markdownRef}>
					{blocks.map((block, index) => {
						const key = `${block}-${index}`
						return (
							<Markdown key={key} className="markdown-content" options={options}>
								{block}
							</Markdown>
						)
					})}
				</div>
			</MessageRenderProvider>
		)
	},
	(prevProps, nextProps) => {
		// Enhance memo comparison logic, consider more props that may affect rendering
		return (
			prevProps.content === nextProps.content &&
			prevProps.hiddenDetail === nextProps.hiddenDetail &&
			prevProps.isStreaming === nextProps.isStreaming &&
			prevProps.isSelf === nextProps.isSelf
		)
	},
)

export default EnhanceMarkdown
