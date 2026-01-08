
import type { CreateTaskParams, UserTask } from "@/types/chat/task"
import conversationStore from "@/opensource/stores/chatNew/conversation"
import { ChatApi } from "@/apis"

class ConversationTaskService {
	private agentId: string | undefined

	switchAgent(agentId: string) {
		this.agentId = agentId
		this.getTaskList()
	}

	/**
	 * Get task list
	 * @param callback Callback function
	 * @returns Task list
	 */
	getTaskList() {
		if (!this.agentId) return Promise.resolve()

		return ChatApi
			.getTaskList({ agent_id: this.agentId, page: 1, page_size: 100 })
			.then((res) => {
				conversationStore.setConversationTaskList(res.list)
			})
	}

	/**
	 * Create task
	 * @param data Task data
	 * @param callback Callback function
	 * @returns Created task data
	 */
	createTask(data: CreateTaskParams, callback?: () => void) {
		return ChatApi.createTask(data).then(() => {
			this.getTaskList()
			callback?.()
		})
	}

	/**
	 * Update task
	 * @param data Task data
	 * @param callback Callback function
	 * @returns Updated task data
	 */
	updateTask(data: UserTask, callback?: () => void) {
		return ChatApi.updateTask(data).then(() => {
			this.getTaskList()
			callback?.()
		})
	}

	/**
	 * Delete task
	 * @param taskId Task ID
	 * @param callback Callback function
	 * @returns Deleted task data
	 */
	deleteTask(taskId: string, callback?: () => void) {
		return ChatApi.deleteTask(taskId).then(() => {
			this.getTaskList()
			callback?.()
		})
	}

	/**
	 * Clear agent information
	 */
	clearAgentInfo() {
		conversationStore.setConversationTaskList([])
		this.agentId = undefined
	}
}

export default new ConversationTaskService()
