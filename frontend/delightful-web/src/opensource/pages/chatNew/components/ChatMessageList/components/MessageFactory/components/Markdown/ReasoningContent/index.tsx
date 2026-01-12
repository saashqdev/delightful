import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { IconBrain, IconChevronUp } from "@tabler/icons-react"
import { useUpdateEffect } from "ahooks"
import { Flex } from "antd"
import { useState, useRef, useEffect } from "react"
import { useTranslation } from "react-i18next"
import { useStyles } from "./useStyles"
import EnhanceMarkdown from "../EnhanceMarkdown"

/**
 * Reasoning content
 * @param content Reasoning content
 * @returns Reasoning content
 */
const ReasoningContent = ({
	content,
	isStreaming = false,
	className,
}: {
	content?: string
	isStreaming?: boolean
	className?: string
}) => {
	const { t } = useTranslation("interface")
	const [isCollapse, setIsCollapse] = useState(!isStreaming)
	const { styles, cx } = useStyles()
	const contentRef = useRef<HTMLDivElement>(null)
	const [contentVisible, setContentVisible] = useState(false)

	// Set content height variable
	useEffect(() => {
		if (!isCollapse && contentRef.current) {
			const height = contentRef.current.scrollHeight
			contentRef.current.style.setProperty("--content-max-height", `${height}px`)

			// Use double requestAnimationFrame instead of fixed delay
			let raf2: number
			const raf1 = requestAnimationFrame(() => {
				raf2 = requestAnimationFrame(() => {
					setContentVisible(true)
				})
			})

			return () => {
				cancelAnimationFrame(raf1)
				if (raf2) cancelAnimationFrame(raf2)
			}
		}
		setContentVisible(false)
		return undefined
	}, [isCollapse, content])

	useUpdateEffect(() => {
		if (!isStreaming && !isCollapse) {
			setIsCollapse(true)
		}
	}, [isStreaming])

	if (!content) return null

	// Collapsed state: only render button
	if (isCollapse) {
		return (
			<div className={cx(styles.buttonWrapper, className)}>
				<Flex
					className={styles.collapseTitle}
					align="center"
					gap={2}
					onClick={() => setIsCollapse(false)}
				>
					<DelightfulIcon component={IconBrain} color="currentColor" size={14} />
					{t("chat.message.thought_process")}
					<DelightfulIcon
						component={IconChevronUp}
						size={14}
						color="currentColor"
						style={{
							transform: "rotate(180deg)",
							transition: "transform 0.3s ease-in-out",
						}}
					/>
				</Flex>
			</div>
		)
	}

	// Expanded state: render button and content
	return (
		<div className={cx(styles.expandedWrapper, className)}>
			<Flex
				className={styles.collapseTitle}
				align="center"
				gap={2}
				onClick={() => setIsCollapse(true)}
			>
				<DelightfulIcon component={IconBrain} color="currentColor" size={14} />
				{t("chat.message.thought_process")}
				<DelightfulIcon
					component={IconChevronUp}
					size={14}
					color="currentColor"
					style={{
						transform: "rotate(0deg)",
						transition: "transform 0.3s ease-in-out",
					}}
				/>
			</Flex>
			<div
				ref={contentRef}
				className={cx(styles.contentContainer, contentVisible && "visible")}
			>
				<EnhanceMarkdown
					content={content}
					className={styles.markdown}
					isStreaming={isStreaming}
					allowHtml={false}
				/>
			</div>
		</div>
	)
}

export default ReasoningContent
