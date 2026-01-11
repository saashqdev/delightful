/**
 * Stores common methods and state for single-step debugging of each node
 */

import type { ReactNode } from "react"
import { useMemo } from "react"
import type {
	EXPRESSION_VALUE,
	InputExpressionValue,
} from "@bedelightful/delightful-flow/dist/DelightfulExpressionWidget/types"
import { FormItemType, LabelTypeMap } from "@bedelightful/delightful-flow/dist/DelightfulExpressionWidget/types"
import { useBoolean, useMemoizedFn } from "ahooks"
import { Form, Input } from "antd"
import { useForm } from "antd/lib/form/Form"
import { useTranslation } from "react-i18next"
import { useCurrentNode } from "@bedelightful/delightful-flow/dist/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { cloneDeep, merge, uniqBy, get, set } from "lodash-es"
import {
	useFlowData,
	useNodeConfig,
} from "@bedelightful/delightful-flow/dist/DelightfulFlow/context/FlowContext/useFlow"
import type { DataSourceOption } from "@bedelightful/delightful-flow/dist/common/BaseUI/DropdownRenderer/Reference"
import type Schema from "@bedelightful/delightful-flow/dist/DelightfulJsonSchemaEditor/types/Schema"
import type { ResolveRule } from "@/types/flow"
import { ComponentTypes } from "@/types/flow"
import DelightfulJSONSchemaEditorWrap from "@bedelightful/delightful-flow/dist/common/BaseUI/DelightfulJsonSchemaEditorWrap"
import { ShowColumns } from "@bedelightful/delightful-flow/dist/DelightfulJsonSchemaEditor/constants"
import { DisabledField } from "@bedelightful/delightful-flow/dist/DelightfulJsonSchemaEditor/types/Schema"
import { SystemNodeSuffix } from "@bedelightful/delightful-flow/dist/DelightfulExpressionWidget/constant"
import antdStyles from "@/opensource/pages/flow/index.module.less"
import { cx } from "antd-style"
import DelightfulModal from "@/opensource/components/base/DelightfulModal"
import TestNodeBtn from "../TestNodeBtn"
import { useCustomFlow } from "../../context/CustomFlowContext/useCustomFlow"
import styles from "./testNode.module.less"
import {
	findFieldInDataSource,
	genDefaultComponent,
	getDefaultSchemaWithDefaultProps,
} from "../../utils/helpers"

import usePrevious from "./usePrevious"
import { customNodeType } from "../../constants"
import UpgradeVersionBtn from "../UpgradeVersionBtn/UpgradeVersion"

export type Field = {
	key: (string | number)[]
	label: string
	type: FormItemType
	sourceOption: DataSourceOption
}

type UseHeaderRightProps = {
	rules?: ResolveRule[]
	extraComponent?: ReactNode
}

export default function useHeaderRight({ rules, extraComponent }: UseHeaderRightProps) {
	const [form] = useForm()
	const { t } = useTranslation()
	const { currentNode } = useCurrentNode()
	const { nodeConfig } = useNodeConfig()
	const { debuggerMode } = useFlowData()

	const [open, { setTrue, setFalse }] = useBoolean(false)

	const { testNode } = useCustomFlow()

	const { expressionDataSource } = usePrevious()

	// Get all reference blocks (including constant values and expressions)
	const getAllExpressionLabelFields = useMemoizedFn((expression: InputExpressionValue) => {
		const result = [...(expression?.const_value || []), ...(expression?.expression_value || [])]
		return result.filter((node) => node.type === LabelTypeMap.LabelNode)
	})

	// Get corresponding data source item through labelNode
	const getDataSourceOption = useMemoizedFn((labelNode: EXPRESSION_VALUE) => {
		const [nodeId, ...fieldKeys] = labelNode?.value?.split?.(".") || []
		const pickNodeId = nodeId.split(SystemNodeSuffix)?.[0]
		let outputSource = expressionDataSource?.find?.(
			(dataSourceItem: DataSourceOption) => dataSourceItem?.nodeId === pickNodeId,
		)
		if (nodeId === "variables") {
			outputSource = expressionDataSource?.find?.((dataSourceItem: DataSourceOption) => {
				return dataSourceItem?.nodeType === customNodeType.VariableSave
			})
		}
		// console.log("fieldKeys", fieldKeys)

		const foundField = findFieldInDataSource(
			fieldKeys,
			(outputSource?.children || []) as DataSourceOption[],
		)
		return foundField
	})

	// Generate corresponding schema field based on data source item
	const generateSchemaFromSource = useMemoizedFn((sourceOption: DataSourceOption) => {
		const { rawSchema } = sourceOption
		const cloneSchema = cloneDeep(rawSchema)
		const mergeSchema = merge({}, cloneSchema, {
			encryption: false,
			encryption_value: null,
		})
		return mergeSchema as Schema
	})

	// Find all expression component values in schema and push to result
	const searchExpressionFields = useMemoizedFn(
		(
			properties: Record<string, Schema>,
			result = [] as EXPRESSION_VALUE[],
		): EXPRESSION_VALUE[] => {
			Object.values(properties || {}).forEach((schema) => {
				if (schema.type === FormItemType.Object) {
					searchExpressionFields(schema.properties || {}, result)
				}

				result.push(getAllExpressionLabelFields(schema.value))
			})
			return uniqBy(result.flat() as EXPRESSION_VALUE[], "value")
		},
	)

	const generateListOrObjectFields = useMemoizedFn((currentRule, list) => {
		let result = []
		if (currentRule?.type === "expression") {
			result = list.reduce((acc: EXPRESSION_VALUE[], cur: object) => {
				const expressionValue = get(cur, [
					...(currentRule?.subKeys || ""),
				]) as InputExpressionValue
				return [...acc, ...getAllExpressionLabelFields(expressionValue)]
			}, [] as EXPRESSION_VALUE[])
		} else {
			result = list.reduce((acc: EXPRESSION_VALUE[], cur: object) => {
				const schema = get(cur, [...(currentRule?.subKeys || "")]) as Schema
				return [...acc, ...searchExpressionFields(schema?.properties || {})]
			}, [] as EXPRESSION_VALUE[])
		}
		return result
	})

	// Dynamically calculate data source based on resolve path and type, generate dynamic schema
	const generateExpressionSourceForm = useMemoizedFn((resolveRules: ResolveRule[]) => {
		const defaultProps = {
			key: "root",
			title: "root",
		}
		// Regular reference form
		const referenceForm = genDefaultComponent(
			ComponentTypes.Form,
			// @ts-ignore
			getDefaultSchemaWithDefaultProps(FormItemType.Object, {
				...defaultProps,
			}),
		)
		// Environment variable reference form
		const globalVariableForm = genDefaultComponent(
			ComponentTypes.Form,
			// @ts-ignore
			getDefaultSchemaWithDefaultProps(FormItemType.Object, {
				...defaultProps,
			}),
		)

		resolveRules.forEach((currentRule) => {
			let allReferenceFields = [] as EXPRESSION_VALUE[]
			if (currentRule?.paramsType === "list") {
				const list = get(currentNode, currentRule.path, [])
				allReferenceFields = generateListOrObjectFields(currentRule, list)
			} else if (currentRule?.paramsType === "object") {
				const obj = get(currentNode, currentRule.path, {})
				const list = Object.values(obj) as object[]
				allReferenceFields = generateListOrObjectFields(currentRule, list)
			} else if (currentRule.type === "expression") {
			// Get current node's expression component value
			const expressionValue = get(currentNode, currentRule.path) as InputExpressionValue
			// Get reference blocks in a certain expression value of current node
			allReferenceFields = getAllExpressionLabelFields(expressionValue)
		} else {
			// Get schema
			const schema = get(currentNode, currentRule.path) as Schema
			// Get reference blocks in a certain expression value of current node
				allReferenceFields = searchExpressionFields(schema?.properties || {})
			}

		// Iterate reference blocks, generate schema fields and set to corresponding schema
			allReferenceFields.forEach((referenceField) => {
				const dataSourceOption = getDataSourceOption(referenceField)
				if (!dataSourceOption) return
				const schemaField = generateSchemaFromSource(dataSourceOption)
				if (schemaField && dataSourceOption.isGlobal) {
					// @ts-ignore
					set(globalVariableForm, ["structure", "properties", schemaField?.key], {
						...schemaField,
						// @ts-ignore
						title: `${schemaField.title || schemaField.key}`,
					})
				}
				if (schemaField && !dataSourceOption.isGlobal) {
					set(
						referenceForm,
						[
							"structure",
							"properties",
							// @ts-ignore
							`${dataSourceOption.nodeId}.${schemaField?.key}`,
						],
						{
							...schemaField,
							// @ts-ignore
							title: `${schemaField.title || schemaField.key}`,
						},
					)
				}
			})
		})

		return {
			referenceForm,
			globalVariableForm,
		}
	})

	const handleOk = useMemoizedFn(() => {
		if (!currentNode || !testNode) {
			return
		}
		const currentNodeConfig = nodeConfig?.[currentNode?.node_id]
		if (!currentNodeConfig) return
		form.validateFields().then((fields) => {
			testNode(currentNodeConfig, fields)
			setFalse()
		})
	})

	const handleCancel = useMemoizedFn(() => {
		setFalse()
	})

	const test = useMemoizedFn(() => {
		const { referenceForm, globalVariableForm } = generateExpressionSourceForm(rules || [])
		form.setFieldsValue({
			trigger_data_form: referenceForm,
			global_variable: globalVariableForm,
		})
		setTrue()
	})

	const showColumns = useMemo(() => {
		return [ShowColumns.Label, ShowColumns.Type, ShowColumns.Value]
	}, [])

	const disabledFields = useMemo(() => {
		return [DisabledField.Title, DisabledField.Type]
	}, [])

	const HeaderRight = useMemo(() => {
		return (
			<>
				{extraComponent}
				{(rules || []).length > 0 && <TestNodeBtn testFn={test} />}
				<UpgradeVersionBtn />

				<DelightfulModal
					title={t("common.inputArgs", { ns: "flow" })}
					open={open}
					onOk={handleOk}
					onCancel={handleCancel}
					closable
					okText={t("button.confirm", { ns: "interface" })}
					cancelText={t("button.cancel", { ns: "interface" })}
					centered
					className={cx(styles.modal, antdStyles.antdModal)}
					width={1000}
				>
					<Form
						form={form}
						validateMessages={{ required: t("form.required", { ns: "interface" }) }}
						className={styles.form}
						layout="vertical"
					>
						{!debuggerMode && (
							<Form.Item
								name="trigger_data_form"
								label={t("common.referenceArgsSettings", { ns: "flow" })}
							>
								<DelightfulJSONSchemaEditorWrap
									oneChildAtLeast={false}
									allowExpression={false}
									expressionSource={[]}
									displayColumns={showColumns}
									disableFields={disabledFields}
									columnNames={{
										[ShowColumns.Key]: t("common.variableName", { ns: "flow" }),
										[ShowColumns.Type]: t("common.variableType", {
											ns: "flow",
										}),
										[ShowColumns.Value]: t("common.variableValue", {
											ns: "flow",
										}),
										[ShowColumns.Label]: t("common.showName", { ns: "flow" }),
										[ShowColumns.Encryption]: t("common.encryption", {
											ns: "flow",
										}),
										[ShowColumns.Description]: t("common.variableDesc", {
											ns: "flow",
										}),
										[ShowColumns.Required]: t("common.required", {
											ns: "flow",
										}),
									}}
								/>
							</Form.Item>
						)}

						{!debuggerMode && (
							<Form.Item
								name="global_variable"
								label={t("common.environmentArgsSettings", { ns: "flow" })}
							>
								<DelightfulJSONSchemaEditorWrap
									oneChildAtLeast={false}
									allowExpression={false}
									expressionSource={[]}
									displayColumns={showColumns}
									disableFields={disabledFields}
									columnNames={{
										[ShowColumns.Key]: t("common.variableName", { ns: "flow" }),
										[ShowColumns.Type]: t("common.variableType", {
											ns: "flow",
										}),
										[ShowColumns.Value]: t("common.variableValue", {
											ns: "flow",
										}),
										[ShowColumns.Label]: t("common.showName", { ns: "flow" }),
										[ShowColumns.Encryption]: t("common.encryption", {
											ns: "flow",
										}),
										[ShowColumns.Description]: t("common.variableDesc", {
											ns: "flow",
										}),
										[ShowColumns.Required]: t("common.required", {
											ns: "flow",
										}),
									}}
								/>
							</Form.Item>
						)}

						{debuggerMode && (
						<Form.Item name="debug_data" label="Debug">
							<Input.TextArea
								className={styles.debug}
								placeholder="If you referenced start node.username and AI chat node.text, and start node id is xxx, AI chat node id is yyy, then please input:
{
	xxx: {
		username: 'custom content'
	},
	yyy: {
		text: 'custom content 2'
	}
}
									"
								/>
							</Form.Item>
						)}
					</Form>
				</DelightfulModal>
			</>
		)
	}, [
		debuggerMode,
		disabledFields,
		form,
		handleCancel,
		handleOk,
		open,
		rules,
		showColumns,
		t,
		test,
		extraComponent,
	])

	return {
		HeaderRight,
	}
}





