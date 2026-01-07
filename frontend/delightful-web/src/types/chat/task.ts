import type {
	RepeatTypeMap,
	Units,
} from "@/opensource/pages/chatNew/components/MessageEditor/components/TimedTaskButton/components/CreateTask/constant"

export interface ListData<T> {
	total: number
	list: T[]
}

/**
 * User task list params
 */
export interface TaskListParams {
	agent_id: string
	page?: number
	page_size?: number
}

/**
 * User task
 */
export interface UserTask {
	id: string
	// Assistant ID
	agent_id: string
	// Conversation ID
	conversation_id: string
	// Topic ID
	topic_id: string
	// Task name
	name: string
	// Schedule type
	type: RepeatTypeMap
	// Date
	day: string
	// Time
	time: string | null
	// Custom
	value: {
		// Interval
		interval: number
		// Unit
		unit: Units
		// Values
		values: string[]
		// Deadline
		deadline: string
		// Month
		month?: string
	}
}

export type CreateTaskParams = Omit<UserTask, "id">
