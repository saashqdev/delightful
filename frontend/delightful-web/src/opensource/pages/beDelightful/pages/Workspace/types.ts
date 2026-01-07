// Workspace related types
import type { Key, ReactNode } from "react"

export interface Thread {
	chat_topic_id: any
	id: string
	topic_name: string
	sort: string
	history?: any[]
	attachments?: any[]
	topics?: any[]
	task_status?: "waiting" | "running" | "finished" | "error"
	is_archived?: number
}

export interface Workspace {
	id: string
	name: string
	sort: number
	topics: Thread[]
	expanded?: boolean
	conversation_id: string
	status?: "waiting" | "running" | "finished" | "error" // This field doesn't actually exist, but added for compatibility with legacy data
}

// Message related types
export interface Message {
	id: string
	content: string
	timestamp: string
	sender?: string
	type: string
	attachments?: FileItem[] | []
}

// Message detail related types
export interface Step {
	id: string
	title: string
	content: string
	timestamp: string
}

export interface MessageDetail {
	id: string
	content: string
	timestamp: string
	sender: string
	steps: {
		title: string
		content: string
	}[]
}

export interface FileItem {
	file_id: Key | null | undefined
	file_size: number | undefined
	file_name: string
	file_key: string | undefined
	key?: string
	name?: string
	size?: number
	path?: string
	filename?: string
	url?: string
	type?: string
	icon?: ReactNode
	color?: string
}

// Task list related types
export interface TaskProcess {
	id: string
	title: string
	status: "waiting" | "running" | "finished" | "error"
	[key: string]: any
}

export interface TaskData {
	process: TaskProcess[]
	topic_id: string
}
