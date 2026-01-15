import { Form } from "antd"
import { useForm } from "antd/lib/form/Form"
import { useMemoizedFn } from "ahooks"
import { useNodeConfigActions } from "@bedelightful/delightful-flow/dist/DelightfulFlow/context/FlowContext/useFlow"
import { useCurrentNode } from "@bedelightful/delightful-flow/dist/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { set, cloneDeep } from "lodash-es"
import DropdownCard from "@bedelightful/delightful-flow/dist/common/BaseUI/DropdownCard"
import DelightfulExpressionWrap from "@bedelightful/delightful-flow/dist/common/BaseUI/DelightfulExpressionWrap"
import { ExpressionMode } from "@bedelightful/delightful-flow/dist/DelightfulExpressionWidget/constant"
import { useMemo } from "react"
import JSONSchemaRenderer from "@/opensource/pages/flow/components/JSONSchemaRenderer"
import usePrevious from "@/opensource/pages/flow/common/hooks/usePrevious"
import useCurrentNodeUpdate from "@/opensource/pages/flow/common/hooks/useCurrentNodeUpdate"
import { useTranslation } from "react-i18next"
import { getExpressionPlaceholder } from "@/opensource/pages/flow/utils/helpers"
import styles from "./TextSplit.module.less"
import { v0Template } from "./template"

export default function TextSplitV0() {
	const { t } = useTranslation()
	const [form] = useForm()
	const { updateNodeConfig } = useNodeConfigActions()

	const { currentNode } = useCurrentNode()

	const { expressionDataSource } = usePrevious()

	const onValuesChange = useMemoizedFn((changeValues) => {
		if (!currentNode) return

		Object.entries(changeValues).forEach(([changeKey, changeValue]) => {
			if (changeKey === "output") {
				set(currentNode, ["output", "form"], changeValue)
			} else {
				set(currentNode, ["params", changeKey], changeValue)
			}
		})

		updateNodeConfig({
			...currentNode,
		})
	})

	const initialValues = useMemo(() => {
		const currentNodeParams = currentNode?.params || {}
		const cloneTemplateParams = cloneDeep(v0Template.params)
		const mergeParams = {
			...cloneTemplateParams,
			...currentNodeParams,
		}
		return {
			...mergeParams,
			output: currentNode?.output?.form,
		}
	}, [currentNode?.output, currentNode?.params])

	useCurrentNodeUpdate({
		form,
		initialValues,
	})

	return (
		<div className={styles.textSplitWrapper}>
			<Form
				form={form}
				layout="vertical"
				initialValues={initialValues}
				onValuesChange={onValuesChange}
			>
				<Form.Item name={["content"]} label={t("textSplit.splitContent", { ns: "flow" })}>
					<DelightfulExpressionWrap
						onlyExpression
						mode={ExpressionMode.TextArea}
						placeholder={getExpressionPlaceholder(
							t("textSplit.splitContentPlaceholder", { ns: "flow" }),
						)}
						dataSource={expressionDataSource}
						minHeight="138px"
					/>
				</Form.Item>

				<DropdownCard title={t("common.output", { ns: "flow" })} height="auto">
					<JSONSchemaRenderer
						// @ts-ignore
						form={v0Template.output?.form?.structure}
					/>
				</DropdownCard>
			</Form>
		</div>
	)
}
