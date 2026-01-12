import DelightfulMarkmap from "@/opensource/components/base/DelightfulMarkmap"
import DelightfulMindMap from "@/opensource/components/base/DelightfulMindmap"
import type { AggregateAISearchCardMindMapChildren } from "@/types/chat/conversation_message"
import { Flex } from "antd"
import { memo } from "react"
import useStyles from "../styles"

interface MindMapProps {
	data?: AggregateAISearchCardMindMapChildren | string | null
	pptData?: string | null
	className?: string
}

/**
 * Check if content contains level 1 heading or above
 * @param content Content
 * @returns Whether it contains level 1 heading or above
 */
const checkMarkmapContent = (content: string) => {
	// Only consider true if contains level 1 heading or above, using regex matching
	return /^(#+)\s/.test(content)
}

const MindMap = memo(({ data, pptData, className }: MindMapProps) => {
	const { styles, cx } = useStyles()

	if (typeof data === "string") {
		if (!data || !checkMarkmapContent(data)) return null

		return (
			<Flex vertical className={styles.mindmap}>
				<DelightfulMarkmap data={data} pptData={pptData} />
			</Flex>
		)
	}
	return (
		<Flex vertical className={cx(styles.mindmap, className)}>
			<DelightfulMindMap data-testid="mindmap" data={data} />
		</Flex>
	)
})

export default MindMap
