/**
 * State related to argument invocation
 */

import { customNodeType } from "@/opensource/pages/flow/constants"
import { TriggerType } from "@/opensource/pages/flow/nodes/Start/v0/constants"
import type { MutableRefObject } from "react"
import { useEffect, useMemo, useState } from "react"
import type { FormInstance } from "antd"
import { Form } from "antd"
import { getComponent } from "@/opensource/pages/flow/utils/helpers"
import type { FormItemType } from "@bedelightful/delightful-flow/dist/DelightfulExpressionWidget/types"
import { FlowType } from "@/types/flow"
import type { DelightfulFlow } from "@bedelightful/delightful-flow/dist/DelightfulFlow/types/flow"
import type { DelightfulFlowInstance } from "@bedelightful/delightful-flow/dist/DelightfulFlow"
import { transformSchemaToDynamicFormItem } from "../helpers"

export type DynamicFormItem = {
	label: string
	type: string
	key: string
	required: boolean
}

type UseArgumentsProps = {
	open: boolean
	form: FormInstance
	flow?: DelightfulFlow.Flow
	onValuesChange: (this: any, changeValues: any) => void
	flowInstance: MutableRefObject<DelightfulFlowInstance | null>
}

export default function useArguments({
	open,
	form,
	flow,
	flowInstance,
	onValuesChange,
}: UseArgumentsProps) {
	const [dynamicFormItems, setDynamicFormItems] = useState([] as DynamicFormItem[])

	const isArgumentsFlow = useMemo(() => {
		// @ts-ignore
		return flow?.type === FlowType.Sub || flow?.type === FlowType.Tools
	}, [flow])

	useEffect(() => {
		if (isArgumentsFlow && open) {
			const latestFlow = flowInstance?.current?.getFlow?.()
			const startNode = latestFlow?.nodes?.find?.(
				(n: DelightfulFlow.Node) => `${n.node_type}` === customNodeType.Start,
			)
			const argumentsBranch = startNode?.params?.branches?.find?.(
				// @ts-ignore
				(branch) => branch.trigger_type === TriggerType.Arguments,
			)

			// Process output
			const outputSchema = argumentsBranch?.output?.form?.structure
			const outputDynamicItems = transformSchemaToDynamicFormItem(outputSchema!)

			// Process custom_system_output
			// @ts-ignore
			const customSystemOutputSchema = argumentsBranch?.custom_system_output?.form?.structure
			const customSystemOutputDynamicItems = transformSchemaToDynamicFormItem(
				customSystemOutputSchema!,
			)

			// Merge two parts of dynamic form items
			const combinedDynamicItems = [...outputDynamicItems, ...customSystemOutputDynamicItems]
			setDynamicFormItems(combinedDynamicItems)
		}
	}, [open, isArgumentsFlow, flowInstance])

	const DynamicFormItemComponents = useMemo(() => {
		return dynamicFormItems.map((field) => {
			return (
				<Form.Item
					name={["trigger_data", field.key]}
					label={field.label}
					required={field.required}
					rules={[{ required: true }]}
					key={field.key}
				>
					{getComponent(field.type as FormItemType)}
				</Form.Item>
			)
		})
	}, [dynamicFormItems])

	useEffect(() => {
		if (isArgumentsFlow && open) {
			form?.setFieldsValue?.({
				trigger_type: TriggerType.Arguments,
			})
			// Manually trigger onValuesChange
			onValuesChange({
				trigger_type: TriggerType.Arguments,
			})
		}
	}, [flow, form, isArgumentsFlow, onValuesChange, open])

	return {
		DynamicFormItemComponents,
		isArgumentsFlow,
		dynamicFormItems,
	}
}





