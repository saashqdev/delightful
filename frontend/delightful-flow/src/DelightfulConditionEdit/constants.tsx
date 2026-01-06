import { defaultExpressionValue } from "@/DelightfulExpressionWidget/constant"
import i18next from "i18next"
import { Expression } from "./types/expression"

export const ConditionKey = "condition"

export const RightOperandsKey = "right_operands"

export const posSeparator = "-"

// Condition mapping for the editor
export const enum RELATION_LOGICS_MAP {
	AND = "AND",
	OR = "OR",
}

// Component types for individual condition items
export const enum RELATION_COMP_TYPE {
	COMPARE = "compare",
	OPERATION = "operation",
}

// Special conditions where the right-side control is hidden
export const SpecialConditionValues = ["empty", "not_empty", "valuable", "no_valuable"]

// Static list of condition options
export const CONDITION_OPTIONS = [
	{
		label: i18next.t("common.equals", { ns: "magicFlow" }),
		value: "equals",
	},
	{
		label: i18next.t("common.notEquals", { ns: "magicFlow" }),
		value: "no_equals",
	},
	{
		label: i18next.t("common.contains", { ns: "magicFlow" }),
		value: "contains",
	},
	{
		label: i18next.t("common.notContains", { ns: "magicFlow" }),
		value: "no_contains",
	},
	{
		label: i18next.t("common.greaterThen", { ns: "magicFlow" }),
		value: "gt",
	},
	{
		label: i18next.t("common.lessThen", { ns: "magicFlow" }),
		value: "lt",
	},
	{
		label: i18next.t("common.greaterOrEqualsTo", { ns: "magicFlow" }),
		value: "gte",
	},
	{
		label: i18next.t("common.lessOrEqualsTo", { ns: "magicFlow" }),
		value: "lte",
	},
	{
		label: i18next.t("common.noValue", { ns: "magicFlow" }),
		value: "empty",
	},
	{
		label: i18next.t("common.hasValue", { ns: "magicFlow" }),
		value: "not_empty",
	},
	{
		label: i18next.t("common.empty", { ns: "magicFlow" }),
		value: "valuable",
	},
	{
		label: i18next.t("common.notEmpty", { ns: "magicFlow" }),
		value: "no_valuable",
	},
]

// Default condition expression item (for new entries)
export const DEFAULT_CONDITION_FIELD: Expression.CompareNode = {
	type: RELATION_COMP_TYPE.COMPARE,
	right_operands: defaultExpressionValue,
	left_operands: defaultExpressionValue,
	condition: "equals",
}

// Converted condition expression item (for transforms)
export const DEFAULT_CONVERT_FIELD: Expression.OperationNode = {
	type: RELATION_COMP_TYPE.OPERATION,
	operands: defaultExpressionValue,
}

// Default condition expression value
export const DEFAULT_CONDITION_DATA: Expression.LogicNode = {
	ops: RELATION_LOGICS_MAP.AND,
	children: [
		{
			type: RELATION_COMP_TYPE.COMPARE,
			left_operands: defaultExpressionValue,
			condition: "equals",
			right_operands: defaultExpressionValue,
		},
	],
}
