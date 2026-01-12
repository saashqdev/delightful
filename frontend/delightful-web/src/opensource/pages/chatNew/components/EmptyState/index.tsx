import { Flex } from "antd"
import DelightfulSplitter from "@/opensource/components/base/DelightfulSplitter"
import ChatSubSider from "../ChatSubSider"
import EmptyConversationFallback from "../EmptyFallback"
import { ChatDomId } from "../../constants"
import { useStyles } from "../../styles"

/**
 * Empty state component
 * Default page displayed when there is no current conversation
 */
function EmptyState() {
	const { styles } = useStyles()

	return (
		<Flex flex={1} className={styles.chat} id={ChatDomId.ChatContainer}>
			<DelightfulSplitter className={styles.splitter}>
				<DelightfulSplitter.Panel min={200} defaultSize={240} max={300}>
					<ChatSubSider />
				</DelightfulSplitter.Panel>
				<DelightfulSplitter.Panel>
					<EmptyConversationFallback />
				</DelightfulSplitter.Panel>
			</DelightfulSplitter>
		</Flex>
	)
}

export default EmptyState
