import { FormItemType } from "@/DelightfulExpressionWidget/types"
import {
	Icon123,
	IconAbc,
	IconArrowsJoin2,
	IconBraces,
	IconBrackets,
	IconCalculator,
	IconCircle,
	IconJson,
	IconToggleLeftFilled,
} from "@tabler/icons-react"
import i18next from "i18next"
import _ from "lodash"
import React from "react"
import { StepOption } from "."

const getAllMethodMap: () => Record<FormItemType, StepOption[]> = () => ({
	[FormItemType.String]: [
		{
			value: "toNumber",
			label: i18next.t("expression.toNumber", { ns: "delightfulFlow" }),
			icon: <Icon123 />,
			type: FormItemType.Number,
		},
		{
			value: "toBoolean",
			label: i18next.t("expression.toBoolean", { ns: "delightfulFlow" }),
			icon: <IconToggleLeftFilled />,
			type: FormItemType.Boolean,
		},
		{
			value: "toArray",
			label: i18next.t("expression.toArray", { ns: "delightfulFlow" }),
			icon: <IconBrackets />,
			type: FormItemType.Array,
		},
		{
			value: "toObject",
			label: i18next.t("expression.toObject", { ns: "delightfulFlow" }),
			icon: <IconBraces />,
			type: FormItemType.Object,
		},
	],
	[FormItemType.Number]: [
		{
			value: "toString",
			label: i18next.t("expression.toString", { ns: "delightfulFlow" }),
			icon: <IconAbc />,
			type: FormItemType.String,
		},
		{
			value: "toBoolean",
			label: i18next.t("expression.toBoolean", { ns: "delightfulFlow" }),
			icon: <IconToggleLeftFilled />,
			type: FormItemType.Boolean,
		},
		{
			value: "toArray",
			label: i18next.t("expression.toArray", { ns: "delightfulFlow" }),
			icon: <IconBrackets />,
			type: FormItemType.Array,
		},
	],
	[FormItemType.Boolean]: [
		{
			value: "toString",
			label: i18next.t("expression.toString", { ns: "delightfulFlow" }),
			icon: <IconAbc />,
			type: FormItemType.String,
		},
		{
			value: "toNumber",
			label: i18next.t("expression.toNumber", { ns: "delightfulFlow" }),
			icon: <Icon123 />,
			type: FormItemType.Number,
		},
		{
			value: "toArray",
			label: i18next.t("expression.toArray", { ns: "delightfulFlow" }),
			icon: <IconBrackets />,
			type: FormItemType.Array,
		},
	],
	[FormItemType.Array]: [
		{
			value: "toString",
			label: i18next.t("expression.toString", { ns: "delightfulFlow" }),
			icon: <IconAbc />,
			type: FormItemType.String,
		},
		{
			value: "toArray",
			label: i18next.t("expression.toArray", { ns: "delightfulFlow" }),
			icon: <IconBrackets />,
			type: FormItemType.Array,
		},
		{
			value: "toJson",
			label: i18next.t("expression.toJSON", { ns: "delightfulFlow" }),
			icon: <IconJson />,
			type: FormItemType.String,
		},
		{
			value: "count",
			label: i18next.t("expression.count", { ns: "delightfulFlow" }),
			icon: <IconCalculator />,
			type: FormItemType.Number,
		},
		{
			value: "empty",
			label: i18next.t("expression.isEmpty", { ns: "delightfulFlow" }),
			icon: <IconCircle />,
			type: FormItemType.Boolean,
		},
		{
			value: "join",
			label: i18next.t("expression.join", { ns: "delightfulFlow" }),
			icon: <IconArrowsJoin2 />,
			type: FormItemType.String,
			withArguments: true,
			arguments: ",",
		},
	],
	[FormItemType.Object]: [
		{
			value: "toString",
			label: i18next.t("expression.toString", { ns: "delightfulFlow" }),
			icon: <IconAbc />,
			type: FormItemType.String,
		},
		{
			value: "toArray",
			label: i18next.t("expression.toArray", { ns: "delightfulFlow" }),
			icon: <IconBrackets />,
			type: FormItemType.Array,
		},
		{
			value: "toJson",
			label: i18next.t("expression.toJSON", { ns: "delightfulFlow" }),
			icon: <IconJson />,
			type: FormItemType.String,
		},
		{
			value: "empty",
			label: i18next.t("expression.isEmpty", { ns: "delightfulFlow" }),
			icon: <IconCircle />,
			type: FormItemType.Boolean,
		},
	],
	[FormItemType.Integer]: [],
})

/** Generate transformable functions based on field type */
export const generateStepOptions = (type: string): StepOption[] => {
	return _.cloneDeep(getAllMethodMap()[type as FormItemType])
}

function getUniqueOptions(allMethodMap: Record<FormItemType, StepOption[]>): StepOption[] {
	const allOptions = Object.values(allMethodMap).flat()

	// Use a Map to deduplicate based on the "value" field
	const uniqueOptionsMap = new Map<string, StepOption>()

	allOptions.forEach((option) => {
		if (!uniqueOptionsMap.has(option.value)) {
			uniqueOptionsMap.set(option.value, option)
		}
	})

	return Array.from(uniqueOptionsMap.values())
}

export const allMethodOptions = getUniqueOptions(getAllMethodMap())

// Convert list to target string => join(',').toArray().toJson()
export function generateString(list: StepOption[]): string {
	return list
		.map((item) => {
			// Append arguments when provided
			if (item.arguments) {
				return `${item.value}('${item.arguments}')`
			}
			return `${item.value}()`
		})
		.join(".")
}

// Convert the target string back into the original list
export function reverseStringToList(input: string): StepOption[] {
	const functionRegex = /(\w+)\(([^)]*)\)/g // Match functions and their arguments
	const list: StepOption[] = []

	let match: RegExpExecArray | null

	while ((match = functionRegex.exec(input)) !== null) {
		const [fullMatch, value, args] = match
		list.push({
			value,
			arguments: args || undefined,
			label: `${i18next.t("expression.transformText", { ns: "delightfulFlow" })}: ${value}`, // Map value back to label if needed
			type: i18next.t("expression.transformType", { ns: "delightfulFlow" }), // Map value back to type if needed
		})
	}

	return list
}

// Derive the latest type from a string like join(',').toArray().toJson()
export function getCurrentTypeFromString(input: string): string | undefined {
	// Split the string and take the last segment
	const parts = input.split(".")
	const lastPart = parts[parts.length - 1]

	// Extract the function name with regex
	const valueMatch = lastPart.match(/(\w+)(\((.*)\))?/)
	if (valueMatch) {
		const value = valueMatch[1]
		// Find the matching item in the list
		const foundItem = allMethodOptions.find((item) => item.value === value)
		return foundItem ? foundItem.type : undefined // Return the matched type if found
	}
	return undefined // Return undefined when no match exists
}

