import type { WidgetValue } from "../../common/Output"

/**
 * Selected tool data structure
 */
export type ToolSelectedItem = {
	tool_id: string
	tool_set_id: string
	async: boolean
	custom_system_input: WidgetValue["value"]
	name?: string
	description?: string
}
