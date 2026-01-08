import ConversationStore from "@/opensource/stores/chatNew/conversation"

class ConversationDsipatchService {
	/**
	 * Pin conversation
	 */
	setTopConversationStatus(conversationId: string, isTop: 0 | 1) {
		// Update conversation state (includes group move logic)
		ConversationStore.updateTopStatus(conversationId, isTop)
	}

	/**
	 * Set conversation do not disturb status
	 */
	setNotDisturbStatus(conversationId: string, isNotDisturb: 0 | 1) {
		ConversationStore.updateConversationDisturbStatus(conversationId, isNotDisturb)
	}
}

export default new ConversationDsipatchService()
