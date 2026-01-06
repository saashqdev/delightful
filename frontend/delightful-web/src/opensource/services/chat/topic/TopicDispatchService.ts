import Topic from "@/opensource/models/chat/topic"
import TopicService from "@/opensource/services/chat/topic/class"
import topicStore from "@/opensource/stores/chatNew/topic"
import ConversationStore from "@/opensource/stores/chatNew/conversation"

class TopicDispatchService {
	// Create topic
	createTopic(conversationId: string, topic: Topic) {
		if (ConversationStore.currentConversation?.id === conversationId) {
			topicStore.unshiftTopic(topic)
		}
	}

	// Update topic
	updateTopic(conversationId: string, topicId: string, updates: Partial<Topic>) {
		if (ConversationStore.currentConversation?.id === conversationId) {
			topicStore.updateTopic(topicId, updates)
		}
	}
}

export default new TopicDispatchService()
