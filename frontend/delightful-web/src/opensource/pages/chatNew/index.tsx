// React related
import { observer } from "mobx-react-lite"

// Components
import EmptyState from "./components/EmptyState"
import ChatContainer from "./components/ChatContainer"

// Store and services
import conversationStore from "@/opensource/stores/chatNew/conversation"

// Hooks and utilities
import useNavigateConversationByAgentIdInSearchQuery from "./hooks/navigateConversationByAgentId"
import { usePanelSizes } from "./hooks/usePanelSizes"

const ChatNew = observer(() => {
	useNavigateConversationByAgentIdInSearchQuery()

	const { sizes, totalWidth, mainMinWidth, handleSiderResize, handleInputResize } =
		usePanelSizes()

	if (!conversationStore.currentConversation) {
		return <EmptyState />
	}

	return (
		<ChatContainer
			sizes={sizes}
			totalWidth={totalWidth}
			mainMinWidth={mainMinWidth}
			onSiderResize={handleSiderResize}
			onInputResize={handleInputResize}
		/>
	)
})

export default ChatNew
