import { Flex } from "antd"
import { Suspense, lazy } from "react"
import { observer } from "mobx-react-lite"
import DelightfulSplitter from "@/opensource/components/base/DelightfulSplitter"
import ChatSubSider from "../ChatSubSider"
import MainContent from "../MainContent"
import ChatImagePreviewModal from "../ChatImagePreviewModal"
import conversationStore from "@/opensource/stores/chatNew/conversation"
import { interfaceStore } from "@/opensource/stores/interface"
import MessageFilePreviewStore from "@/opensource/stores/chatNew/messagePreview/FilePreviewStore"
import { ChatDomId } from "../../constants"
import { useStyles } from "../../styles"

// 懒加载组件
const ChatFilePreviewPanel = lazy(() => import("../ChatFilePreviewPanel"))
const GroupSeenPanel = lazy(() => import("../GroupSeenPanel"))

interface ChatContainerProps {
	sizes: (number | undefined)[]
	totalWidth: number
	mainMinWidth: number
	onSiderResize: (size: number[]) => void
	onInputResize: (size: number[]) => void
}

/**
 * 聊天容器组件
 * 包含完整的聊天界面布局
 */
const ChatContainer = observer(function ChatContainer({
	sizes,
	totalWidth,
	mainMinWidth,
	onSiderResize,
	onInputResize,
}: ChatContainerProps) {
	const { styles } = useStyles()

	return (
		<Flex flex={1} className={styles.chat} id={ChatDomId.ChatContainer}>
			<DelightfulSplitter onResize={onSiderResize}>
				<DelightfulSplitter.Panel
					min={200}
					defaultSize={interfaceStore.chatSiderDefaultWidth}
					size={sizes[0]}
					max={300}
				>
					<ChatSubSider />
				</DelightfulSplitter.Panel>
				<DelightfulSplitter.Panel size={sizes[1]}>
					<MainContent onInputResize={onInputResize} style={{ height: "100%" }} />
				</DelightfulSplitter.Panel>
				{MessageFilePreviewStore.open && (
					<DelightfulSplitter.Panel
						max={totalWidth - sizes[0]! - mainMinWidth}
						min="20%"
						size={sizes[2]}
					>
						<Suspense fallback={null}>
							<ChatFilePreviewPanel />
						</Suspense>
					</DelightfulSplitter.Panel>
				)}
			</DelightfulSplitter>
			<ChatImagePreviewModal />
			{conversationStore.currentConversation?.isGroupConversation && (
				<Suspense fallback={null}>
					<GroupSeenPanel />
				</Suspense>
			)}
		</Flex>
	)
})

export default ChatContainer
