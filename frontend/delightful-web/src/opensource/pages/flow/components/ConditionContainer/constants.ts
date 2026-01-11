import { Schema } from "@/types/sheet"
import { cloneDeep } from "lodash-es"
import { defaultExpressionValue } from "@bedelightful/delightful-flow/dist/DelightfulExpressionWidget/constant"
import { VALUE_TYPE } from "@bedelightful/delightful-flow/dist/DelightfulExpressionWidget/types"
import i18next from "i18next"
import { Operators, Time, type AutomateFlowField } from "./types"
import { generateSnowFlake } from "../../utils/helpers"

/**
 * Column ID of Row ID Column
 * This is for workflows. In automation workflow filtering conditions, the Row ID field is selectable by default, even if this column doesn't exist.
 * So we need to define a fixed column ID for logic processing and echoing.
 */
export const ROW_ID_COLUMN_ID = "ROW_ID_COLUMN_ID"

export const getDefaultConstValue = () => {
	return {
		id: generateSnowFlake(),
		version: "1",
		type: "value",
		// @ts-ignore
		structure: {
			...cloneDeep(defaultExpressionValue),
			// @ts-ignore
			type: VALUE_TYPE.CONST,
		},
	}
}
// @ts-ignore
export const AutomateFlowFieldGroup: { [key in Schema]: AutomateFlowField } = {
	[Schema.SELECT]: {
		icon: "ts-checkbox-radio",
		title: i18next.t("common.radio", { ns: "flow" }),
		id: Schema.SELECT,
		conditions: [
			{ id: Operators.EQUAL, label: i18next.t("common.equals", { ns: "flow" }) },
			{ id: Operators.NOT_EQUAL, label: i18next.t("common.notEquals", { ns: "flow" }) },
			// { id: Operators.CONTAIN, label: "Contains" },
			// { id: Operators.NOT_CONTAIN, label: "Not Contains" },
			{ id: Operators.EMPTY, label: i18next.t("common.empty", { ns: "flow" }) },
			{ id: Operators.NOT_EMPTY, label: i18next.t("common.notEmpty", { ns: "flow" }) },
		],
		valueOptions: [],
	},
	[Schema.NUMBER]: {
		icon: "ts-number",
		title: i18next.t("common.number", { ns: "flow" }),
		id: Schema.NUMBER,
		extraInfo: {
			showNumberFormat: true, // Whether display number option box format
		},
		conditions: [
			{ id: Operators.EQUAL, label: "=" },
			{ id: Operators.NOT_EQUAL, label: "≠" },
			{ id: Operators.GREATER_THAN, label: ">" },
			{ id: Operators.LESS_THAN, label: "<" },
			{ id: Operators.LESS_THAN_OR_EQUAL, label: "≤" },
			{ id: Operators.GREATER_THAN_OR_EQUAL, label: "≥" },
			{ id: Operators.EMPTY, label: i18next.t("common.empty", { ns: "flow" }) },
			{ id: Operators.NOT_EMPTY, label: i18next.t("common.notEmpty", { ns: "flow" }) },
		],
		valueOptions: [],
	},
	[Schema.TEXT]: {
		icon: "ts-multiline-text",
		title: i18next.t("common.text", { ns: "flow" }),
		id: Schema.TEXT,
		extraInfo: {},
		conditions: [
			{ id: Operators.EQUAL, label: i18next.t("common.equals", { ns: "flow" }) },
			{ id: Operators.NOT_EQUAL, label: i18next.t("common.notEquals", { ns: "flow" }) },
			// { id: Operators.CONTAIN, label: "Contains" },
			// { id: Operators.NOT_CONTAIN, label: "Not Contains" },
			{ id: Operators.EMPTY, label: i18next.t("common.empty", { ns: "flow" }) },
			{ id: Operators.NOT_EMPTY, label: i18next.t("common.notEmpty", { ns: "flow" }) },
		],
		valueOptions: [],
	},
	[Schema.MULTIPLE]: {
		icon: "ts-multiple-choice",
		title: i18next.t("common.multiple", { ns: "flow" }),
		id: Schema.MULTIPLE,
		extraInfo: {
			showMultAddOption: true,
		},
		conditions: [
			{ id: Operators.EQUAL, label: i18next.t("common.equals", { ns: "flow" }) },
			{ id: Operators.NOT_EQUAL, label: i18next.t("common.notEquals", { ns: "flow" }) },
			// { id: Operators.CONTAIN, label: "Contains" },
			// { id: Operators.NOT_CONTAIN, label: "Not Contains" },
			{ id: Operators.EMPTY, label: i18next.t("common.empty", { ns: "flow" }) },
			{ id: Operators.NOT_EMPTY, label: i18next.t("common.notEmpty", { ns: "flow" }) },
		],
		valueOptions: [],
	},
	[Schema.DATE]: {
		icon: "ts-date",
		title: i18next.t("common.date", { ns: "flow" }),
		id: Schema.DATE,
		extraInfo: {
			showDate: true,
		},
		conditions: [
			{ id: Operators.EQUAL, label: i18next.t("common.equals", { ns: "flow" }) },
			{ id: Operators.GREATER_THAN, label: i18next.t("common.later", { ns: "flow" }) },
			{ id: Operators.LESS_THAN, label: i18next.t("common.earlier", { ns: "flow" }) },
			{ id: Operators.EMPTY, label: i18next.t("common.empty", { ns: "flow" }) },
			{ id: Operators.NOT_EMPTY, label: i18next.t("common.notEmpty", { ns: "flow" }) },
		],
		valueOptions: [
			{ id: Time.TODAY, label: i18next.t("common.today", { ns: "flow" }) },
			{ id: Time.TOMORROW, label: i18next.t("common.tomorrow", { ns: "flow" }) },
			{ id: Time.YESTERDAY, label: i18next.t("common.yesterday", { ns: "flow" }) },
			{ id: Time.THIS_WEEK, label: i18next.t("common.thisWeek", { ns: "flow" }) },
			{ id: Time.LAST_WEEK, label: i18next.t("common.lastWeek", { ns: "flow" }) },
			{ id: Time.THIS_MONTH, label: i18next.t("common.thisMonth", { ns: "flow" }) },
			{ id: Time.LAST_MONTH, label: i18next.t("common.lastMonth", { ns: "flow" }) },
			{ id: Time.PAST_SEVEN_DAYS, label: i18next.t("common.pastSevenDay", { ns: "flow" }) },
			{ id: Time.NEXT_SEVEN_DAYS, label: i18next.t("common.nextSevenDay", { ns: "flow" }) },
			{ id: Time.PAST_THIRTY_DAYS, label: i18next.t("common.pastThirtyDay", { ns: "flow" }) },
			{ id: Time.NEXT_THIRTY_DAYS, label: i18next.t("common.nextThirtyDay", { ns: "flow" }) },
		],
	},
	[Schema.CHECKBOX]: {
		icon: "ts-checkbox",
		title: i18next.t("common.checkbox", { ns: "flow" }),
		id: Schema.CHECKBOX,
		extraInfo: {
			showCheckboxOption: true,
		},
		conditions: [{ id: Operators.EQUAL, label: i18next.t("common.equals", { ns: "flow" }) }],
		valueOptions: [],
	},
	[Schema.LINK]: {
		icon: "ts-link",
		title: i18next.t("common.link", { ns: "flow" }),
		id: Schema.LINK,
		extraInfo: {},
		conditions: [
			{ id: Operators.EQUAL, label: i18next.t("common.equals", { ns: "flow" }) },
			{ id: Operators.NOT_EQUAL, label: i18next.t("common.notEquals", { ns: "flow" }) },
			// { id: Operators.CONTAIN, label: "Contains" },
			// { id: Operators.NOT_CONTAIN, label: "Not Contains" },
			{ id: Operators.EMPTY, label: i18next.t("common.empty", { ns: "flow" }) },
			{ id: Operators.NOT_EMPTY, label: i18next.t("common.notEmpty", { ns: "flow" }) },
		],
		valueOptions: [],
	},
	[Schema.ATTACHMENT]: {
		icon: "ts-attachment",
		title: i18next.t("common.attachment", { ns: "flow" }),
		id: Schema.ATTACHMENT,
		extraInfo: {},
		conditions: [
			{ id: Operators.EMPTY, label: i18next.t("common.empty", { ns: "flow" }) },
			{ id: Operators.NOT_EMPTY, label: i18next.t("common.notEmpty", { ns: "flow" }) },
		],
		valueOptions: [],
	},
	[Schema.CREATE_AT]: {
		icon: "ts-create-time",
		title: i18next.t("common.createAt", { ns: "flow" }),
		id: Schema.CREATE_AT,
		extraInfo: {
			showCreateTime: true,
		},
		conditions: [
			{ id: Operators.EQUAL, label: i18next.t("common.equals", { ns: "flow" }) },
			{ id: Operators.NOT_EQUAL, label: i18next.t("common.notEquals", { ns: "flow" }) },
			{ id: Operators.LESS_THAN, label: i18next.t("common.earlier", { ns: "flow" }) },
			{ id: Operators.EMPTY, label: i18next.t("common.empty", { ns: "flow" }) },
			{ id: Operators.NOT_EMPTY, label: i18next.t("common.notEmpty", { ns: "flow" }) },
		],
		valueOptions: [
			{ id: Time.TODAY, label: i18next.t("common.today", { ns: "flow" }) },
			{ id: Time.TOMORROW, label: i18next.t("common.tomorrow", { ns: "flow" }) },
			{ id: Time.YESTERDAY, label: i18next.t("common.yesterday", { ns: "flow" }) },
			{ id: Time.THIS_WEEK, label: i18next.t("common.thisWeek", { ns: "flow" }) },
			{ id: Time.LAST_WEEK, label: i18next.t("common.lastWeek", { ns: "flow" }) },
			{ id: Time.THIS_MONTH, label: i18next.t("common.thisMonth", { ns: "flow" }) },
			{ id: Time.LAST_MONTH, label: i18next.t("common.lastMonth", { ns: "flow" }) },
			{ id: Time.PAST_SEVEN_DAYS, label: i18next.t("common.pastSevenDay", { ns: "flow" }) },
			{ id: Time.NEXT_SEVEN_DAYS, label: i18next.t("common.nextSevenDay", { ns: "flow" }) },
			{ id: Time.PAST_THIRTY_DAYS, label: i18next.t("common.pastThirtyDay", { ns: "flow" }) },
			{ id: Time.NEXT_THIRTY_DAYS, label: i18next.t("common.nextThirtyDay", { ns: "flow" }) },
		],
	},
	[Schema.UPDATE_AT]: {
		icon: "ts-modify-time",
		title: i18next.t("common.updateAt", { ns: "flow" }),
		id: Schema.UPDATE_AT,
		extraInfo: {
			showUpdateTime: true,
		},
		conditions: [
			{ id: Operators.EQUAL, label: i18next.t("common.equals", { ns: "flow" }) },
			{ id: Operators.NOT_EQUAL, label: i18next.t("common.notEquals", { ns: "flow" }) },
			{ id: Operators.GREATER_THAN, label: i18next.t("common.later", { ns: "flow" }) },
			{ id: Operators.LESS_THAN, label: i18next.t("common.earlier", { ns: "flow" }) },
			{ id: Operators.EMPTY, label: i18next.t("common.empty", { ns: "flow" }) },
			{ id: Operators.NOT_EMPTY, label: i18next.t("common.notEmpty", { ns: "flow" }) },
		],
		valueOptions: [
			{ id: Time.TODAY, label: i18next.t("common.today", { ns: "flow" }) },
			{ id: Time.TOMORROW, label: i18next.t("common.tomorrow", { ns: "flow" }) },
			{ id: Time.YESTERDAY, label: i18next.t("common.yesterday", { ns: "flow" }) },
			{ id: Time.THIS_WEEK, label: i18next.t("common.thisWeek", { ns: "flow" }) },
			{ id: Time.LAST_WEEK, label: i18next.t("common.lastWeek", { ns: "flow" }) },
			{ id: Time.THIS_MONTH, label: i18next.t("common.thisMonth", { ns: "flow" }) },
			{ id: Time.LAST_MONTH, label: i18next.t("common.lastMonth", { ns: "flow" }) },
			{ id: Time.PAST_SEVEN_DAYS, label: i18next.t("common.pastSevenDay", { ns: "flow" }) },
			{ id: Time.NEXT_SEVEN_DAYS, label: i18next.t("common.nextSevenDay", { ns: "flow" }) },
			{ id: Time.PAST_THIRTY_DAYS, label: i18next.t("common.pastThirtyDay", { ns: "flow" }) },
			{ id: Time.NEXT_THIRTY_DAYS, label: i18next.t("common.nextThirtyDay", { ns: "flow" }) },
		],
	},
	[Schema.CREATED]: {
		icon: "ts-created-by",
		title: i18next.t("common.createAt", { ns: "flow" }),
		id: Schema.CREATED,
		conditions: [
			{ id: Operators.EQUAL, label: i18next.t("common.equals", { ns: "flow" }) },
			{ id: Operators.NOT_EQUAL, label: i18next.t("common.notEquals", { ns: "flow" }) },
			{ id: Operators.GREATER_THAN, label: i18next.t("common.later", { ns: "flow" }) },
			{ id: Operators.EMPTY, label: i18next.t("common.empty", { ns: "flow" }) },
			{ id: Operators.NOT_EMPTY, label: i18next.t("common.notEmpty", { ns: "flow" }) },
		],
		valueOptions: [],
	},
	[Schema.UPDATED]: {
		icon: "ts-modified-by",
		title: i18next.t("common.updateAt", { ns: "flow" }),
		id: Schema.UPDATED,
		conditions: [
			{ id: Operators.EQUAL, label: i18next.t("common.equals", { ns: "flow" }) },
			{ id: Operators.NOT_EQUAL, label: i18next.t("common.notEquals", { ns: "flow" }) },
			// { id: Operators.CONTAIN, label: "Contains" },
			// { id: Operators.NOT_CONTAIN, label: "Not Contains" },
			{ id: Operators.EMPTY, label: i18next.t("common.empty", { ns: "flow" }) },
			{ id: Operators.NOT_EMPTY, label: i18next.t("common.notEmpty", { ns: "flow" }) },
		],
		valueOptions: [],
	},
	[Schema.MEMBER]: {
		icon: "ts-user",
		title: i18next.t("common.member", { ns: "flow" }),
		id: Schema.MEMBER,
		conditions: [
			{ id: Operators.EQUAL, label: i18next.t("common.equals", { ns: "flow" }) },
			{ id: Operators.NOT_EQUAL, label: i18next.t("common.notEquals", { ns: "flow" }) },
			// { id: Operators.CONTAIN, label: "Contains" },
			// { id: Operators.NOT_CONTAIN, label: "Not Contains" },
			{ id: Operators.EMPTY, label: i18next.t("common.empty", { ns: "flow" }) },
			{ id: Operators.NOT_EMPTY, label: i18next.t("common.notEmpty", { ns: "flow" }) },
		],
		valueOptions: [],
	},
	[Schema.TODO_STATUS]: {
		icon: "ts-checkbox",
		title: i18next.t("common.todoStatus", { ns: "flow" }),
		id: Schema.TODO_STATUS,
		conditions: [{ id: Operators.EQUAL, label: i18next.t("common.equals", { ns: "flow" }) }],
		valueOptions: [],
	},
	[Schema.TODO_FINISHED_AT]: {
		icon: "ts-completion-time",
		title: i18next.t("common.todoFinishedAt", { ns: "flow" }),
		id: Schema.DATE,
		extraInfo: {
			showDate: true,
		},
		conditions: [
			{ id: Operators.EQUAL, label: i18next.t("common.equals", { ns: "flow" }) },
			{ id: Operators.NOT_EQUAL, label: i18next.t("common.notEquals", { ns: "flow" }) },
			{ id: Operators.GREATER_THAN, label: i18next.t("common.later", { ns: "flow" }) },
			{ id: Operators.LESS_THAN, label: i18next.t("common.earlier", { ns: "flow" }) },
			{ id: Operators.EMPTY, label: i18next.t("common.empty", { ns: "flow" }) },
			{ id: Operators.NOT_EMPTY, label: i18next.t("common.notEmpty", { ns: "flow" }) },
		],
		valueOptions: [
			{ id: Time.TODAY, label: i18next.t("common.today", { ns: "flow" }) },
			{ id: Time.TOMORROW, label: i18next.t("common.tomorrow", { ns: "flow" }) },
			{ id: Time.YESTERDAY, label: i18next.t("common.yesterday", { ns: "flow" }) },
			{ id: Time.THIS_WEEK, label: i18next.t("common.thisWeek", { ns: "flow" }) },
			{ id: Time.LAST_WEEK, label: i18next.t("common.lastWeek", { ns: "flow" }) },
			{ id: Time.THIS_MONTH, label: i18next.t("common.thisMonth", { ns: "flow" }) },
			{ id: Time.LAST_MONTH, label: i18next.t("common.lastMonth", { ns: "flow" }) },
			{ id: Time.PAST_SEVEN_DAYS, label: i18next.t("common.pastSevenDay", { ns: "flow" }) },
			{ id: Time.NEXT_SEVEN_DAYS, label: i18next.t("common.nextSevenDay", { ns: "flow" }) },
			{ id: Time.PAST_THIRTY_DAYS, label: i18next.t("common.pastThirtyDay", { ns: "flow" }) },
			{ id: Time.NEXT_THIRTY_DAYS, label: i18next.t("common.nextThirtyDay", { ns: "flow" }) },
		],
	},
	[Schema.LOOKUP]: {
		icon: "ts-lookup",
		title: i18next.t("common.lookUp", { ns: "flow" }),
		id: Schema.LOOKUP,
		conditions: [],
		valueOptions: [],
	},
	[Schema.QUOTE_RELATION]: {
		icon: "ts-one-way-link",
		title: i18next.t("common.quoteRelation", { ns: "flow" }),
		id: Schema.QUOTE_RELATION,
		conditions: [
			{ id: Operators.EQUAL, label: i18next.t("common.equals", { ns: "flow" }) },
			{ id: Operators.NOT_EQUAL, label: i18next.t("common.notEquals", { ns: "flow" }) },
			// { id: Operators.CONTAIN, label: "Contains" },
			// { id: Operators.NOT_CONTAIN, label: "Not Contains" },
			{ id: Operators.EMPTY, label: i18next.t("common.empty", { ns: "flow" }) },
			{ id: Operators.NOT_EMPTY, label: i18next.t("common.notEmpty", { ns: "flow" }) },
		],
		valueOptions: [],
	},
	[Schema.MUTUAL_RELATION]: {
		icon: "ts-two-wat-link",
		title: i18next.t("common.mutualRelation", { ns: "flow" }),
		id: Schema.MUTUAL_RELATION,
		conditions: [
			{ id: Operators.EQUAL, label: i18next.t("common.equals", { ns: "flow" }) },
			{ id: Operators.NOT_EQUAL, label: i18next.t("common.notEquals", { ns: "flow" }) },
			// { id: Operators.CONTAIN, label: "Contains" },
			// { id: Operators.NOT_CONTAIN, label: "Not Contains" },
			{ id: Operators.EMPTY, label: i18next.t("common.empty", { ns: "flow" }) },
			{ id: Operators.NOT_EMPTY, label: i18next.t("common.notEmpty", { ns: "flow" }) },
		],
		valueOptions: [],
	},
	[Schema.ROW_ID]: {
		icon: "ts-ID",
		title: i18next.t("common.rowId", { ns: "flow" }),
		id: Schema.ROW_ID,
		conditions: [
			{ id: Operators.EQUAL, label: i18next.t("common.equals", { ns: "flow" }) },
			{ id: Operators.NOT_EQUAL, label: i18next.t("common.notEquals", { ns: "flow" }) },
		],
		valueOptions: [],
	},
	[Schema.FORMULA]: {
		icon: "ts-formula-line",
		title: i18next.t("common.formula", { ns: "flow" }),
		id: Schema.FORMULA,
		conditions: [],
		valueOptions: [],
	},
}







