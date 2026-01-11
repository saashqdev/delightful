import { Switch } from "antd"
import type { DataSourceOption } from "@delightful/delightful-flow/dist/common/BaseUI/DropdownRenderer/Reference"
import { Common } from "@delightful/delightful-flow/dist/DelightfulConditionEdit/types/common"
import DelightfulInput from "@delightful/delightful-flow/dist/common/BaseUI/Input"
import type { EXPRESSION_VALUE } from "@delightful/delightful-flow/dist/DelightfulExpressionWidget/types"
import { FormItemType, LabelTypeMap } from "@delightful/delightful-flow/dist/DelightfulExpressionWidget/types"
import type { DelightfulFlow } from "@delightful/delightful-flow/dist/DelightfulFlow/types/flow"
import type JSONSchema from "@delightful/delightful-flow/dist/DelightfulJsonSchemaEditor/types/Schema"
import { flowStore } from "@delightful/delightful-flow/dist/DelightfulFlow/store/index"
import type { NodeSchema } from "@delightful/delightful-flow/dist/DelightfulFlow"

import type { Sheet } from "@/types/sheet"
import { Schema } from "@/types/sheet"
import { get, last, isEmpty, isObject, isArray, cloneDeep, uniqBy, set, omitBy } from "lodash-es"
// @ts-ignore
import SnowFlakeId from "snowflake-id"
import i18next from "i18next"
import { useFlowStore } from "@/opensource/stores/flow"
import type { ComponentTypes, UseableToolSet } from "@/types/flow"
import { getLatestNodeVersion } from "@delightful/delightful-flow/dist/DelightfulFlow/utils"
import { customNodeType } from "../constants"
import { shadow, unshadow } from "./shadow"
import { JsonSchemaEditorProps } from "@delightful/delightful-flow/dist/DelightfulJsonSchemaEditor"
import { getDefaultSchema } from "@delightful/delightful-flow/dist/DelightfulJsonSchemaEditor/utils/SchemaUtils"
import { ContactApi } from "@/apis"
import { UserType } from "@/types/user"

// Snowflake ID generation
const snowflake = new SnowFlakeId({
	mid: Math.floor(Math.random() * 1e10),
	offset: (2021 - 1970) * 365 * 24 * 3600 * 1000,
})

/** Get node schema for a specific node type */
export const getNodeSchema = (nodeType: string | number): NodeSchema => {
	const { nodeVersionSchema } = flowStore.getState()
	const version = getLatestNodeVersion(nodeType) as string
	return get(nodeVersionSchema, [nodeType, version, "schema"])
}

export const findFieldInDataSource = (
	fieldKeys: string[],
	dataSourceOptions: DataSourceOption[],
): DataSourceOption => {
	const [curKey, ...restKeys] = [...fieldKeys]
	const foundField = dataSourceOptions.find((option) => {
		// Get the last key after splitting, use as match value
		const lastKey = last((option?.key as string)?.split?.("."))
		return lastKey === curKey
	})
	if (restKeys.length === 0) {
		return foundField as DataSourceOption
	}
	return findFieldInDataSource(restKeys, foundField?.children as DataSourceOption[])
}

export function generateSnowFlake() {
	return snowflake.generate()
}

/** Check if inside a loop */
export const checkIsInLoop = (node: DelightfulFlow.Node) => {
	return node?.meta?.parent_id
}

export const getComponent = (type: string) => {
	const componentMap = {
		[FormItemType.Number]: <DelightfulInput type="number" />,
		[FormItemType.String]: <DelightfulInput.TextArea />,
		[FormItemType.Boolean]: <Switch />,
		[FormItemType.Integer]: <DelightfulInput type="number" />,
		[FormItemType.Array]: <div>Array selection not supported yet</div>,
		[FormItemType.Object]: <div>Object selection not supported yet</div>,
	}
	return componentMap?.[type as FormItemType]
}

/**
 * Convert schema to dynamic form item
 * @param schema json schema
 * @param namePrefix
 * @returns
 */
// export const generateFormItems = (schema?: Schema, namePrefix: string = "") => {
// 	if (!schema) return null

// 	// @ts-ignore
// 	const { type, properties, items, title, key: schemaKey } = schema

// 	const formTitle = title || schemaKey

// 	if (type === FormItemType.Object && properties) {
// 		const objectLabel = namePrefix ? "" : formTitle
// 		// Recursively process object types, nested objects will also be handled correctly
// 		return (
// 			<Form.Item label={objectLabel} className="object-wrapper" name={schemaKey}>
// 				{Object.keys(properties).map((key) => {
// 					const propertySchema = properties[key]
// 					const fieldName = namePrefix ? `${namePrefix}.${key}` : `${schemaKey}.${key}`
// 					return (
// 						<Form.Item label={key} key={fieldName} name={fieldName.split(".")}>
// 							{generateFormItems(propertySchema, fieldName)}
// 						</Form.Item>
// 					)
// 				})}
// 			</Form.Item>
// 		)
// 	}

// 	if (type === FormItemType.Array && items) {
// 		// Use Form.List to handle arrays, array elements can be objects or arrays
// 		return (
// 			<Form.Item label={formTitle} className="array-wrapper">
// 				<Form.List name={namePrefix || schemaKey}>
// 					{(fields, { add, remove }) => {
// 						return (
// 							<div className="array-item">
// 								{fields.map(({ key, name }, index) => (
// 									<Flex
// 										justify="space-between"
// 										align="center"
// 										gap={10}
// 									>
// 										<Form.Item
// 											key={key}
// 											name={name}
// 											label={`${index}`}
// 											style={{ flex: 1 }}
// 										>
// 											{/* Recursively process array items */}
// 											{generateFormItems(items, `${name}`)}
// 										</Form.Item>
// 										<IconTrash
// 											className="icon-trash"
// 											width={20}
// 											onClick={() => remove(name)}
// 										/>
// 									</Flex>
// 								))}
// 								<div className="add-btn" onClick={() => add()}>
// 									<IconPlus />
// 									<span className="text">Add one item</span>
// 								</div>
// 							</div>
// 						)}
// 					}}
// 				</Form.List>
// 			</Form.Item>
// 		)
// 	}

// 	// Handle basic types (string, number, boolean etc)
// 	return componentMap?.[type as FormItemType]
// }

/**
 * Search for expression blocks in schema
 * @param properties
 * @param result
 * @returns
 */
export const searchExpressionFieldsInSchema = (
	schema: Record<string, JSONSchema>,
	result = [] as EXPRESSION_VALUE[],
) => {
	Object.values(schema?.properties || {}).forEach((subSchema) => {
		if (subSchema.type === FormItemType.Object) {
			searchExpressionFieldsInSchema(subSchema.properties || {}, result)
		}
		result.push(
			...(subSchema?.value?.expression_value || []),
			...(subSchema?.value?.const_value || []),
		)
	})
	return result.flat()
}

// Merge multiple data source items into one, as they all belong to the same node
export const mergeOptionsIntoOne = (options: DataSourceOption[]): DataSourceOption => {
	return options.reduce((mergeResult, currentOption) => {
		const newChildren = [
			...(mergeResult.children || []),
			...(currentOption.children || []),
		] as DataSourceOption[]
		// Result after deduplication based on node id and key
		const uniqueChildren = uniqBy(newChildren, (obj) => `${obj.nodeId}_${obj.key}`)
		mergeResult = {
			...mergeResult,
			...currentOption,
			children: uniqueChildren,
		}
		return mergeResult
	}, {} as DataSourceOption)
}

export const getCurrentDateTimeString = () => {
	const now = new Date()
	const year = now.getFullYear()
	const month = String(now.getMonth() + 1).padStart(2, "0")
	const day = String(now.getDate()).padStart(2, "0")
	const hours = String(now.getHours()).padStart(2, "0")
	const minutes = String(now.getMinutes()).padStart(2, "0")
	// const seconds = String(now.getSeconds()).padStart(2, "0")

	return `${year}${month}${day}${hours}${minutes}`
}

/**
 * Obfuscate all code node values in the flow
 */
export const shadowFlow = (flow: DelightfulFlow.Flow) => {
	const cloneFlow = cloneDeep(flow)

	const allCodeNode = Object.values(cloneFlow.nodes).filter(
		// eslint-disable-next-line eqeqeq
		(n) => n.node_type == customNodeType.Code,
	)

	allCodeNode.forEach((codeNode) => {
		const codeData = get(codeNode, ["params", "code"], "")
		set(codeNode, ["params", "code"], shadow(codeData))
	})

	return cloneFlow
}

/**
 * Decode all code node values in the flow
 */
export const unShadowFlow = (flow: DelightfulFlow.Flow) => {
	const cloneFlow = cloneDeep(flow)

	const allCodeNode = Object.values(cloneFlow.nodes).filter(
		// eslint-disable-next-line eqeqeq
		(n) => n.node_type == customNodeType.Code,
	)

	allCodeNode.forEach((codeNode) => {
		const codeData = get(codeNode, ["params", "code"], "")
		set(codeNode, ["params", "code"], unshadow(codeData))
	})

	return cloneFlow
}

/**
 * Obfuscate code node
 */
export const shadowNode = (node: DelightfulFlow.Node) => {
	const cloneNode = cloneDeep(node)
	const codeData = get(cloneNode, ["params", "code"], "")
	set(cloneNode, ["params", "code"], shadow(codeData))
	return cloneNode
}

export function removeEmptyValues(obj: Record<string, any>): Record<string, any> {
	return omitBy(
		obj,
		(value) => isEmpty(value) || (isObject(value) && !isArray(value) && isEmpty(value)),
	)
}

export const getExpressionPlaceholder = (str: string) => {
	return `${str}${i18next.t("common.allowExpressionPlaceholder", { ns: "flow" })}`
}

// Find the corresponding tool in the toolset by id
export const findTargetTool = (id: string) => {
	const { useableToolSets } = useFlowStore.getState()
	const allTools = useableToolSets.reduce((tools, currentToolSet) => {
		return tools.concat(
			currentToolSet.tools.map((tool) => ({
				...tool,
				icon: currentToolSet.icon,
			})),
		)
	}, [] as (UseableToolSet.UsableTool & { icon: string })[])
	return allTools.find((tool) => tool.code === id)
}

/**
 * Generate default component data
 * @param componentType
 * @returns
 */
export function genDefaultComponent<ResultType extends Common.ComponentTypes>(
	componentType: ComponentTypes,
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

/** Generate default schema based on type, can add or override default properties via defaultProps */
export const getDefaultSchemaWithDefaultProps = (
	type: string,
	defaultProps: Partial<JsonSchemaEditorProps>,
	itemsType?: string,
) => {
	return {
		...getDefaultSchema(type, itemsType),
		...defaultProps,
	}
}

// Get expression component render properties based on field type
export const getExpressionRenderConfig = (column: Sheet.Column) => {
	switch (column?.columnType) {
		case Schema.CHECKBOX:
			return {
				type: LabelTypeMap.LabelCheckbox,
				props: {},
			}
		case Schema.DATE:
		case Schema.CREATE_AT:
		case Schema.UPDATE_AT:
			return {
				type: LabelTypeMap.LabelDateTime,
				props: {},
			}
		case Schema.MEMBER:
			return {
				type: LabelTypeMap.LabelMember,
				props: {
					options: [],
					value: [],
					onSearch: async (searchInfo: { with_department: number; name: string }) => {
						const searchedUsers = await ContactApi?.searchUser?.({
							query: searchInfo.name,
							page_token: "",
							// @ts-ignore
							query_type: 2,
						})
						if (searchedUsers?.items?.length) {
							const filterUsers = searchedUsers?.items?.filter(
								(user) => user.user_type !== UserType.AI,
							)
							const filterUserInfos = filterUsers.map((user) => {
								return {
									id: user.user_id,
									name: user.real_name || user.nickname,
									avatar: user.avatar_url,
								}
							})
							return filterUserInfos
						}
						return []
					},
				},
			}
		case Schema.SELECT:
			return {
				type: LabelTypeMap.LabelSelect,
				props: {
					value: [],
					options: column?.columnProps?.options,
				},
			}
		case Schema.MULTIPLE:
			return {
				type: LabelTypeMap.LabelMultiple,
				props: {
					value: [],
					options: column?.columnProps?.options,
				},
			}
		default:
			return undefined
	}
}

export const getPlaceholder = (
	column: Sheet.Column,
	columnType: Schema,
	isSpecialHandle?: boolean,
) => {
	switch (columnType) {
		case Schema.TEXT:
		case Schema.NUMBER:
		case Schema.LINK:
			return "Please enter"
		case Schema.MEMBER:
		case Schema.CREATED:
		case Schema.UPDATED:
			return "Select member"
		case Schema.QUOTE_RELATION:
		case Schema.MUTUAL_RELATION:
		case Schema.SELECT:
		case Schema.MULTIPLE:
			return "Please select"
		case Schema.DATE:
		case Schema.CREATE_AT:
		case Schema.UPDATE_AT:
		case Schema.TODO_FINISHED_AT:
			if (isSpecialHandle) return "YYYY-MM-DD"
			return get(column, ["columnProps", "format"], "YYYY-MM-DD")
		default:
			return ""
	}
}





