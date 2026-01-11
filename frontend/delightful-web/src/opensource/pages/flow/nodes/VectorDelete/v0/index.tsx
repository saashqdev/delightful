import { Form } from "antd"
import { useForm } from "antd/lib/form/Form"
import { useMemo } from "react"
import { useMemoizedFn } from "ahooks"
import { useNodeConfigActions } from "@delightful/delightful-flow/dist/DelightfulFlow/context/FlowContext/useFlow"
import { useCurrentNode } from "@delightful/delightful-flow/dist/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { set, cloneDeep } from "lodash-es"
import DelightfulExpressionWrap from "@delightful/delightful-flow/dist/common/BaseUI/DelightfulExpressionWrap"
import { ExpressionMode } from "@delightful/delightful-flow/dist/DelightfulExpressionWidget/constant"
import { ShowColumns } from "@delightful/delightful-flow/dist/DelightfulJsonSchemaEditor/constants"
import { FormItemType } from "@delightful/delightful-flow/dist/DelightfulExpressionWidget/types"
import DelightfulJSONSchemaEditorWrap from "@delightful/delightful-flow/dist/common/BaseUI/DelightfulJsonSchemaEditorWrap"
import { useTranslation } from "react-i18next"
import styles from "./index.module.less"
import usePrevious from "../../../common/hooks/usePrevious"
import KnowledgeSelect from "../../VectorSearch/v0/components/KnowledgeSelect/KnowledgeSelect"
import useOldKnowledgeHandle from "../../VectorSearch/v0/components/KnowledgeSelect/hooks/useOldKnowledgeHandle"
import useCurrentNodeUpdate from "../../../common/hooks/useCurrentNodeUpdate"
import { v0Template } from "./template"

export default function VectorDeleteV0() {
	const { t } = useTranslation()
	const [form] = useForm()
	const { updateNodeConfig } = useNodeConfigActions()

	const { currentNode } = useCurrentNode()

	const { expressionDataSource } = usePrevious()

	const onValuesChange = useMemoizedFn((changeValues) => {
		if (!currentNode) return

		Object.entries(changeValues).forEach(([changeKey, changeValue]) => {
			set(currentNode, ["params", changeKey], changeValue)
		})

		updateNodeConfig({
			...currentNode,
		})
	})

	const { handleOldKnowledge } = useOldKnowledgeHandle()

	const initialValues = useMemo(() => {
		let resultValue = currentNode?.params || cloneDeep(v0Template.params)
		resultValue = handleOldKnowledge(resultValue)
		return resultValue
	}, [currentNode?.params, handleOldKnowledge])

	useCurrentNodeUpdate({
		form,
		initialValues,
	})

	return (
		<div className={styles.vectorWrapper}>
			<Form
				form={form}
				layout="vertical"
				initialValues={initialValues}
				onValuesChange={onValuesChange}
			>
				<KnowledgeSelect />
				<Form.Item
					name={["metadata"]}
					label={t("common.metadataMatch", { ns: "flow" })}
					className={styles.metadata}
					style={{ marginBottom: 0 }}
				>
					<DelightfulJSONSchemaEditorWrap
						allowExpression
						expressionSource={expressionDataSource}
						displayColumns={[ShowColumns.Key, ShowColumns.Type, ShowColumns.Value]}
						customOptions={{
							root: [FormItemType.Object],
							normal: [FormItemType.Number, FormItemType.String, FormItemType.Array],
						}}
					/>
				</Form.Item>
				<Form.Item
					name="business_id"
					label={t("common.businessId", { ns: "flow" })}
					extra={t("common.businessIdDesc", { ns: "flow" })}
					style={{ marginBottom: 0 }}
				>
					<DelightfulExpressionWrap
						placeholder={t("common.allowExpressionPlaceholder", { ns: "flow" })}
						dataSource={expressionDataSource}
						onlyExpression
						mode={ExpressionMode.TextArea}
					/>
				</Form.Item>
			</Form>
		</div>
	)
}





