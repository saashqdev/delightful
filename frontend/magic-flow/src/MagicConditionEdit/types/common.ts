
import { ComponentType } from "@/common/constants"
import type { Expression } from "./expression"
import { EXPRESSION_VALUE, InputExpressionValue } from "@/MagicExpressionWidget/types"
import Schema from "@/MagicJsonSchemaEditor/types/Schema"

export namespace Common {
	/**
	 * Structure for a single component
	 * Form component -------- Schema
	 * Widget component -------- ContainerField[]
	 * Condition component -------- Expression.LogicNode
	 * API component -------- COMMON_TYPES.API_SETTINGS
	 */
	export type Component<
		CompType extends ComponentType,
		CompContent extends
			| Schema
			| Expression.LogicNode
			| WidgetSchema
			| API_SETTINGS
	> = {
		id: string
		type: CompType // Component type
		version: string // Version
		structure: CompContent
	}

	/** Form component type */
	export type FormComponent = Component<ComponentType.Form, Schema>

	/** Widget component type */
	export type WidgetComponent = Component<ComponentType.Widget, WidgetSchema>

	/** Condition component type */
	export type ConditionComponent = Component<ComponentType.Condition, Expression.LogicNode>

	/** API component type */
	export type ApiComponent = Component<ComponentType.Api, API_SETTINGS>

	export type ComponentTypes = FormComponent | WidgetComponent | ConditionComponent | ApiComponent

	/** Extra config for number inputs */
	export type NumberExtraConfig = {
		max: number
		min: number
		step: number
	}

	/** Extra config for switch */
	export type SwitchExtraConfig = {
		checked_text: string
		unchecked_text: string
	}

	export type Option = {
		label: string
		value: string
		children?: Option[]
	}

	/** Extra config for dynamic fields */
	export type DataSourceConfig = {
		dynamic_fields: boolean
		data_source?: Option[]
		data_source_api?: ApiComponent
		multiple?: boolean
	}

	/** Extra config for time picker */
	export type TimePickerConfig = {
		format: string
	}

	/** Widget component carrying a sentinel */
	export interface WidgetSchema {
		key: string
		type: string
		sort?: number
		properties: {
			[key: string]: BaseWidgetSchema
		}
	}

	/** Schema for a widget component */
	export interface BaseWidgetSchema extends Schema {
		key: string
		type: string
		sort?: number
		initial_value: InputExpressionValue | null
		display_config: {
			label: string
			tooltips: string
			required: boolean
			visible: boolean
			allow_expression: boolean
			disabled: boolean
			extra?:
				| NumberExtraConfig
				| SwitchExtraConfig
				| DataSourceConfig
				| TimePickerConfig
				| object

			web_config?: {
				/** Special data for linkage mode */
				linkage_data?: {
					label: string
					required: boolean
					/** Key that triggers linkage */
					trigger_key: string
					/** Key that is linked */
					action_key: string
				}
				// Whether the current formItemType can be changed
				changeable?: boolean
				/** Container props */
				wrapper_props?: {
					title?: string
				}
				before_widget?: BaseWidgetSchema
				before_form_type?: string
				/** Linkage dependencies */
				dependencies?: string[]
				/** Condition widget special flag: hide the third field when the second field needs special handling */
				linkage_right_operands?: boolean
			}
		}
		properties?: {
			[key: string]: BaseWidgetSchema
		}
		items?: BaseWidgetSchema
	}

	// Request parameter configuration (depends on API settings)
	interface ARGS_SETTINGS {
		params_query: Common.FormComponent
		params_path: Common.FormComponent
		body_type: string
		body: Common.FormComponent
		headers: Common.FormComponent
	}

	// API configuration
	export interface API_SETTINGS {
		method: string
		domain: string
		uri: EXPRESSION_VALUE
		url: string
		path: string
		request: ARGS_SETTINGS
		response_check: {}
		response_form: {}
	}

	export type Options = Array<Option>

}
