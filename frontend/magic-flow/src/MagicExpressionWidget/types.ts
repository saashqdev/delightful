/* eslint-disable @typescript-eslint/naming-convention */

import { DataSourceOption } from "@/common/BaseUI/DropdownRenderer/Reference";
import type { ExpressionMode } from "./constant"
import { ReactElement, ReactNode } from "react";
import { MemberSelectProps } from "./components/nodes/LabelMember/MemberSelect/Select";
import { MultipleSelectProps } from "./components/nodes/LabelMultiple/MultipleSelect/Select";
import { TimeSelectProps } from "./components/nodes/LabelDatetime/TimeSelect/type";
import { CheckboxSelectProps } from "./components/nodes/LabelCheckbox/ExpressionCheckbox/types";
import { SingleSelectProps } from "./components/nodes/LabelSelect/SingleSelect/SingleSelect";
import { Department } from "./components/nodes/LabelDepartmentNames/LabelDepartmentNames";
import { NameValue } from "./components/nodes/LabelNames/LabelNames";

// Parameter value types
export const enum VALUE_TYPE {
    CONST = 'const',
    EXPRESSION = 'expression',
}

	// Field names corresponding to parameter value types
export const FIELDS_NAME = {
    [VALUE_TYPE.CONST]: 'const_value',
    [VALUE_TYPE.EXPRESSION]: 'expression_value',
};

export interface CommonField {
	label: string
	value: string
}
export interface ExpressionFields extends CommonField {
	desc?: string
	children?: CommonField[]
}
export interface MethodArgsItem {
	name?: string
	type?: string
	desc?: string
}
export interface MethodsItem extends CommonField {
	return_type?: string
	desc?: string
	arg?: MethodArgsItem[]
}
export interface ExpressionMethods extends CommonField {
	desc: string
	children: MethodsItem[]
}
export declare type ExpressionSourceItem = ExpressionFields & ExpressionMethods
export declare type ExpressionSource = DataSourceOption[]

export const enum ExpressionItemType {
	normal = "fields",
	input = "input",
	func = "methods",
}

export interface EXPRESSION_ITEM {
	type: LabelTypeMap
	value: string
	uniqueId: string
	name?: string
	schemaType?: string
	trans?: string
	[key: string]: any
}

export interface EXPRESSION_VALUE extends EXPRESSION_ITEM {
	args?: InputExpressionValue[]
}

/** Expression value type */
export type InputExpressionValue = {
	type: VALUE_TYPE
	const_value: EXPRESSION_VALUE[]
	expression_value: EXPRESSION_VALUE[]
    [key: string]: any
}

export type NodeBaseInfo = {
    icon: ReactNode,
    name: string
    id: string
}

export type BaseRenderConfig = {
    showExpressionSource?: boolean
}

export interface MemberRenderConfig extends BaseRenderConfig {
	type: LabelTypeMap.LabelMember
	props: MemberSelectProps
}

export interface MultipleRenderConfig extends BaseRenderConfig {
	type: LabelTypeMap.LabelMultiple
	props: MultipleSelectProps
}

export interface DateTimeRenderConfig extends BaseRenderConfig {
	type: LabelTypeMap.LabelDateTime
	props: TimeSelectProps
}

export interface CheckboxRenderConfig extends BaseRenderConfig {
	type: LabelTypeMap.LabelCheckbox
	props: CheckboxSelectProps
}


export interface SelectRenderConfig extends BaseRenderConfig {
	type: LabelTypeMap.LabelSelect
	props: SingleSelectProps
}


export interface DepartmentNamesRenderConfig extends BaseRenderConfig {
	type: LabelTypeMap.LabelDepartmentNames
	props: {
		editComponent: React.FC<{
			isOpen: boolean
			closeModal: () => void
			onChange: (departmentNames: Department[]) => void
            [key: string]: any
		}>
	}
}

export interface NamesRenderConfig extends BaseRenderConfig  {
    type: LabelTypeMap.LabelNames
    props: {
        suffix?: React.FC<NameValue>
        options: MultipleSelectProps['options']
        value: NameValue[]
    }
}


export type RenderConfig = MemberRenderConfig | MultipleRenderConfig | DateTimeRenderConfig | CheckboxRenderConfig | SelectRenderConfig | DepartmentNamesRenderConfig | NamesRenderConfig


/** InputExpression component props */
export interface InputExpressionProps {
	onChange?: (value: InputExpressionValue) => void
	disabled?: boolean
	dataSource?: ExpressionSource
	/** Data source for constants */
	constantDataSource?: ExpressionSource
	/** Highest priority placeholder */
	placeholder?: string
	/** Placeholder when using a fixed value */
	inputPlaceholder?: string
	/** Placeholder when using an expression */
	referencePlaceholder?: string
	bordered?: boolean
	allowExpression?: boolean
	value?: InputExpressionValue
	mode?: ExpressionMode
	allowModifyField?: boolean

	/** Business components can specify the value type */
	pointedValueType?: "const_value" | "expression_value",

	/** Whether only expression mode is allowed */
	onlyExpression?: boolean

	/** Whether multiple selection is supported */
	multiple?: boolean

	/** Minimum height */
	minHeight?: string
	/** Maximum height */
	maxHeight?: string

	/** Whether to include schema type info for options (string, array, boolean, etc.) */
	withSchemaType?: boolean

	/** Class name for the outer wrapper */
	wrapperClassName?: string

	/** Whether popup editing is allowed */
	allowOpenModal?: boolean

	/** Whether to display multiple lines */
	showMultipleLine?: boolean

	renderConfig?: RenderConfig

	/** Whether encryption is required; used with MagicJSONSchemaEditor */
	encryption?: boolean
	hasEncryptionValue?: boolean

	/** Whether to show the expand view button */
	showExpand?: boolean

	/** Whether the expression component is used inside a flow */
	isInFlow?: boolean
}

export enum LabelTypeMap {
	LabelNode = "fields",
	LabelFunc = "methods",
	LabelText = "input",
	LabelMember = "member", // Member
	LabelDateTime = 'datetime', // Date/time
	LabelMultiple = 'multiple', // Multi-select
	LabelSelect = "select", // Single-select
	LabelCheckbox = "checkbox", // Checkbox
	LabelPassword = "password", // Password
	LabelDepartmentNames = "department_names",
	LabelNames = "names", // Generic text block type
}

export interface ChangeRef {
	handleChange: (value: any) => void
	hiddenPlaceholder: () => void
	getDataSource: () => any
}

// Mouse cursor reference
export interface CursorRef {
	id: string
	type: string
	offset: number
	prevId?: string
	nextId?: string
}

// Editor reference
export interface EditRef {
	updateDisplayValue: (val: any) => void
	getDisplayValue: () => any
	getCursor: () => CursorRef
	setCursor: (value: CursorRef) => void
	setCurrentNode: (uniqueId: string) => void
	getCurrentNode: () => any
}

/** InputExpression ref */
export interface InputExpressionInstance {}

// Form item types
export enum FormItemType {
	// String
	String= 'string',
	// Number
	Number= 'number',
	// Integer
	Integer = 'integer',
	// Array
	Array = 'array',
	// Object
	Object = 'object',
	// Boolean
	Boolean = 'boolean',
}

export type MethodOption = {
	label: string
	value: string
	desc: string
	children: MethodOption[]
	arg: MethodArgsItem[]
    return_type: string
}

export type WithReference<T> = EXPRESSION_ITEM | T