import { Common } from "@/MagicConditionEdit/types/common"
import Schema from "@/MagicJsonSchemaEditor/types/Schema"
import { ComponentType } from "../constants"

export function toCamelCase(input: string): string {
	return input
		.split("-")
		.map((word) => {
			return word.charAt(0).toUpperCase() + word.slice(1)
		})
		.join("")
}

/**
 * Generate default component data
 * @param componentType
 * @returns
 */
export function genDefaultComponent<ResultType extends Common.ComponentTypes>(
	componentType: ComponentType,
	structure: any = null,
): ResultType {
	const uniqueId = null
	const result = {
		id: uniqueId,
		type: componentType,
		version: "1",
		structure,
	}

	// @ts-ignore
	return result
}

/** Generate a Widget component */
export function genWidgetComponent(defaultWidget: Common.WidgetSchema | null = null) {
	return genDefaultComponent(ComponentType.Widget, defaultWidget) as Common.WidgetComponent
}

/** Generate a form component */
export function genFormComponent(defaultForm: Schema | null = null) {
	return genDefaultComponent(ComponentType.Form, defaultForm) as Common.FormComponent
}

/** Generate a condition component */
export function genConditionComponent() {
	return genDefaultComponent(ComponentType.Condition) as Common.ConditionComponent
}

/** Generate an API component */
export function genApiComponent(defaultWidget: Common.API_SETTINGS | null = null) {
	return genDefaultComponent(ComponentType.Api, defaultWidget) as Common.ApiComponent
}
