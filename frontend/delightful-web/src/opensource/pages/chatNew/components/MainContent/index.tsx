import { Flex } from "antd"
import { Suspense, lazy, type CSSProperties } from "react"
import { observer } from "mobx-react-lite"
import DelightfulSplitter from "@/opensource/components/base/DelightfulSplitter"
import ChatMessageList from "../ChatMessageList"
import Header from "../ChatHeader"
import DragFileSendTip from "../ChatMessageList/components/DragFileSendTip"
import AiImageStartPage from "../AiImageStartPage"
import MessageEditor from "../MessageEditor"
import conversationStore from "@/opensource/stores/chatNew/conversation"
import ConversationBotDataService from "@/opensource/services/chat/conversation/ConversationBotDataService"
import { interfaceStore } from "@/opensource/stores/interface"
import { useStyles } from "../../styles"

// lazy load components
const TopicExtraSection = lazy(() => import("../topic/ExtraSection"))
const SettingExtraSection = lazy(() => import("../setting"))

interface MainContentProps {
	onInputResize: (size: number[]) => void
	style?: CSSProperties
}

/**
 * Chat main content area component
 * Includes header, message list, input field and extra sidebar
 */
const MainContent = observer(function MainContent({ onInputResize, style }: MainContentProps) {
	const { styles } = useStyles()
	const showExtra = conversationStore.topicOpen

	// if start page is enabled, display start page
	if (ConversationBotDataService.startPage && interfaceStore.isShowStartPage) {
		return <AiImageStartPage />
	}

	return (
		<Flex style={style}>
			<DelightfulSplitter layout="vertical" className={styles.main} onResizeEnd={onInputResize}>
				<DelightfulSplitter.Panel min={60} defaultSize={60} max={60}>
					<Header />
				</DelightfulSplitter.Panel>
				<DelightfulSplitter.Panel>
					<div className={styles.chatList}>
						<DragFileSendTip>
							<ChatMessageList />
						</DragFileSendTip>
					</div>
				</DelightfulSplitter.Panel>
				<DelightfulSplitter.Panel
					min={200}
					defaultSize={interfaceStore.chatInputDefaultHeight}
					max="50%"
				>
					<div className={styles.editor}>
						<MessageEditor visible sendWhenEnter />
					</div>
				</DelightfulSplitter.Panel>
			</DelightfulSplitter>
			{showExtra && (
				<div className={styles.extra}>
					<Suspense fallback={null}>
						{conversationStore.topicOpen && <TopicExtraSection />}
					</Suspense>
				</div>
			)}
			{conversationStore.settingOpen && (
				<Suspense fallback={null}>
					<SettingExtraSection />
				</Suspense>
			)}
		</Flex>
	)
})

export default MainContent
