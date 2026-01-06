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
		label: i18next.t("common.equals", { ns: "delightfulFlow" }),
		value: "equals",
	},
	{
		label: i18next.t("common.notEquals", { ns: "delightfulFlow" }),
		value: "no_equals",
	},
	{
		label: i18next.t("common.contains", { ns: "delightfulFlow" }),
		value: "contains",
	},
	{
		label: i18next.t("common.notContains", { ns: "delightfulFlow" }),
		value: "no_contains",
	},
	{
		label: i18next.t("common.greaterThen", { ns: "delightfulFlow" }),
		value: "gt",
	},
	{
		label: i18next.t("common.lessThen", { ns: "delightfulFlow" }),
		value: "lt",
	},
	{
		label: i18next.t("common.greaterOrEqualsTo", { ns: "delightfulFlow" }),
		value: "gte",
	},
	{
		label: i18next.t("common.lessOrEqualsTo", { ns: "delightfulFlow" }),
		value: "lte",
	},
	{
		label: i18next.t("common.noValue", { ns: "delightfulFlow" }),
		value: "empty",
	},
	{
		label: i18next.t("common.hasValue", { ns: "delightfulFlow" }),
		value: "not_empty",
	},
	{
		label: i18next.t("common.empty", { ns: "delightfulFlow" }),
		value: "valuable",
	},
	{
		label: i18next.t("common.notEmpty", { ns: "delightfulFlow" }),
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

