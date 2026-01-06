import ConversationDotsService from "./ConversationDotsService"
import OrganizationDotsService from "./OrganizationDotsService"

const DotsService = {
	// Increase conversation unread counts
	addConversationUnreadDots(
		organizationId: string,
		conversationId: string,
		topicId: string,
		seqId: string,
		dots: number,
	) {
		ConversationDotsService.addUnreadDots(conversationId, topicId, dots)
		OrganizationDotsService.addOrganizationDot(organizationId, seqId, dots)
	},
	// Decrease topic unread counts
	reduceTopicUnreadDots(
		organizationId: string,
		conversationId: string,
		topicId: string,
		dots: number,
	) {
		ConversationDotsService.reduceUnreadDots(conversationId, topicId, dots)
		OrganizationDotsService.reduceOrganizationDot(organizationId, dots)
	},
}

export default DotsService
