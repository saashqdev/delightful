import { Form } from "antd"
import { useForm } from "antd/lib/form/Form"
import { useMemo } from "react"
import { useMemoizedFn } from "ahooks"
import { useNodeConfigActions } from "@delightful/delightful-flow/dist/DelightfulFlow/context/FlowContext/useFlow"
import { useCurrentNode } from "@delightful/delightful-flow/dist/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { set } from "lodash-es"
import DelightfulJsonSchemaEditor from "@delightful/delightful-flow/dist/DelightfulJsonSchemaEditor"
import { ShowColumns } from "@delightful/delightful-flow/dist/DelightfulJsonSchemaEditor/constants"
import { FormItemType } from "@delightful/delightful-flow/dist/DelightfulExpressionWidget/types"
import type Schema from "@delightful/delightful-flow/dist/DelightfulJsonSchemaEditor/types/Schema"
import styles from "./index.module.less"
import usePrevious from "../../../common/hooks/usePrevious"
import useCurrentNodeUpdate from "../../../common/hooks/useCurrentNodeUpdate"
import { v0Template } from "./template"

export default function End() {
	const [form] = useForm()
	const { updateNodeConfig } = useNodeConfigActions()

	const { currentNode } = useCurrentNode()

	const { expressionDataSource } = usePrevious()

	const onValuesChange = useMemoizedFn((changeValues) => {
		Object.entries(changeValues).forEach(([changeKey, changeValue]) => {
			if (changeKey === "output") {
				set(currentNode, ["output", "form", "structure"], changeValue as Schema)
				return
			}
			set(currentNode, ["params", changeKey], changeValue)
		})

		updateNodeConfig({
			...currentNode,
		})
	})

	const initialValues = useMemo(() => {
		return {
			output: currentNode?.output?.form?.structure || v0Template.output?.form?.structure,
		}
	}, [currentNode?.output?.form?.structure])

	useCurrentNodeUpdate({
		form,
		initialValues,
	})

	return (
		<div className={styles.endWrapper}>
			<Form
				form={form}
				layout="vertical"
				initialValues={initialValues}
				onValuesChange={onValuesChange}
			>
				<Form.Item name={["output"]} className={styles.output} valuePropName="data">
					<DelightfulJsonSchemaEditor
						allowExpression
						expressionSource={expressionDataSource}
						displayColumns={[
							ShowColumns.Key,
							ShowColumns.Label,
							ShowColumns.Type,
							ShowColumns.Value,
						]}
						customOptions={{
							root: [FormItemType.Object],
							normal: [
								FormItemType.Number,
								FormItemType.String,
								FormItemType.Boolean,
								FormItemType.Array,
								FormItemType.Object,
							],
						}}
					/>
				</Form.Item>
			</Form>
		</div>
	)
}
