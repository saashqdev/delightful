/**
 * Message context menu keys
 */
export const enum MessageContextMenuKey {
	Copy = "copy",
	Forward = "forward",
	Reply = "reply",
	Remove = "remove",
	Collect = "collect",
	Revoke = "revoke",
	Schedule = "Schedule",
	ToDo = "ToDo",
	Task = "Task",
	Translate = "Translate",
	Mulitiple = "Mulitiple",
	Urgent = "Urgent",
	CopyMessageId = "copyMessageId",
	Edit = "edit",
}

/**
 * Menu item
 */
export interface MenuItem {
	icon?: {
		color: string
		component: React.ElementType
		size: number
	}
	danger?: boolean
	label?: string
	key: MessageContextMenuKey | string
	type?: string
	id?: string
}
