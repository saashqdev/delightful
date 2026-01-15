import { BotApi } from "@/apis"
import { RoutePath } from "@/const/routes"
import useNavigate from "@/opensource/hooks/useNavigate"
import ConversationService from "@/opensource/services/chat/conversation/ConversationService"
import { MessageReceiveType } from "@/types/chat"
import { useMount } from "ahooks"

/**
 * Auto-create conversation when agent_id parameter is in URL
 */
const AgentIdKey = "agent_id"
const UserIdKey = "user_id"

/**
 * Auto-create conversation when agent_id parameter is in URL
 */
const useNavigateConversationByAgentIdInSearchQuery = () => {
	const navigate = useNavigate()
	useMount(() => {
		const agentId = window.sessionStorage.getItem(AgentIdKey)
		if (agentId) {
			BotApi.registerAndAddFriend(agentId)
				.then((res) => {
					return ConversationService.createConversation(
						MessageReceiveType.Ai,
						res.user_id,
					)
				})
				.then((res) => {
					if (res?.id) {
						ConversationService.switchConversation(res)
						window.sessionStorage.removeItem(AgentIdKey)
						// Navigate to Chat page
						navigate(RoutePath.Chat)
					}
				})
				.catch((err) => {
					console.error("err", err)
				})
		}
		const userId = window.sessionStorage.getItem(UserIdKey)
		if (userId) {
			ConversationService.createConversation(MessageReceiveType.User, userId)
				.then((res) => {
					if (res?.id) {
						ConversationService.switchConversation(res)
						window.sessionStorage.removeItem(UserIdKey)
						// Navigate to Chat page
						navigate(RoutePath.Chat)
					}
				})
				.catch((err) => {
					console.error("err", err)
				})
		}
	})
}

/**
 * Record conversation receiver id to sessionStorage for auto-creating conversation when agent_id or user_id is in URL
 */
export const recordConversationReceiveIdInSessionStorage = () => {
	const url = new URL(window.location.href)
	const { searchParams } = url
	const agentId = searchParams.get(AgentIdKey)
	if (agentId) {
		window.sessionStorage.setItem(AgentIdKey, agentId)
		searchParams.delete(AgentIdKey)
	}
	const userId = searchParams.get(UserIdKey)
	if (userId) {
		window.sessionStorage.setItem(UserIdKey, userId)
		searchParams.delete(UserIdKey)
	}

	// Clear agent_id and user_id from URL
	window.history.replaceState({}, "", url.toString())
}

export default useNavigateConversationByAgentIdInSearchQuery
