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
 * Check if content contains heading level 1 or above
 * @param content Content to check
 * @returns Whether content contains heading level 1 or above
 */
const checkMarkmapContent = (content: string) => {
	// Only return true if contains heading level 1 or above, using regex match
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
