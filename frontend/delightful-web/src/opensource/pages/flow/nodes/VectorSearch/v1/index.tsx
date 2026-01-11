import { Form, InputNumber } from "antd"
import { useForm } from "antd/lib/form/Form"
import { useMemo } from "react"
import { useMemoizedFn } from "ahooks"
import { useNodeConfigActions } from "@bedelightful/delightful-flow/dist/DelightfulFlow/context/FlowContext/useFlow"
import { useCurrentNode } from "@bedelightful/delightful-flow/dist/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { set, cloneDeep } from "lodash-es"
import DelightfulExpressionWrap from "@bedelightful/delightful-flow/dist/common/BaseUI/DelightfulExpressionWrap"
import { ExpressionMode } from "@bedelightful/delightful-flow/dist/DelightfulExpressionWidget/constant"
import DelightfulSlider from "@bedelightful/delightful-flow/dist/common/BaseUI/Slider"
import DelightfulJsonSchemaEditor from "@bedelightful/delightful-flow/dist/DelightfulJsonSchemaEditor"
import { ShowColumns } from "@bedelightful/delightful-flow/dist/DelightfulJsonSchemaEditor/constants"
import { FormItemType } from "@bedelightful/delightful-flow/dist/DelightfulExpressionWidget/types"
import type { Widget } from "@/types/flow"
import type Schema from "@bedelightful/delightful-flow/dist/DelightfulJsonSchemaEditor/types/Schema"
import { useTranslation } from "react-i18next"
import { getExpressionPlaceholder } from "@/opensource/pages/flow/utils/helpers"
import styles from "./index.module.less"
import usePrevious from "../../../common/hooks/usePrevious"
import Output from "../../../common/Output"
import KnowledgeSelect from "./components/KnowledgeSelect/KnowledgeSelect"
import useOldKnowledgeHandle from "./components/KnowledgeSelect/hooks/useOldKnowledgeHandle"
import useCurrentNodeUpdate from "../../../common/hooks/useCurrentNodeUpdate"
import { v1Template } from "./template"

export default function VectorSearchV1() {
	const { t } = useTranslation()
	const [form] = useForm()
	const { updateNodeConfig } = useNodeConfigActions()

	const { currentNode } = useCurrentNode()

	const { expressionDataSource } = usePrevious()

	const onValuesChange = useMemoizedFn((changeValues) => {
		if (!currentNode) return

		Object.entries(changeValues).forEach(([changeKey, changeValue]) => {
			if (changeKey === "metadata_filter") {
				set(
					currentNode,
					["params", "metadata_filter", "structure"],
					(changeValue as Widget<Schema>)?.structure,
				)
				return
			}
			set(currentNode, ["params", changeKey], changeValue)
		})

		updateNodeConfig({
			...currentNode,
		})
	})

	const { handleOldKnowledge } = useOldKnowledgeHandle()

	const initialValues = useMemo(() => {
		let resultValue = currentNode?.params || cloneDeep(v1Template.params)
		resultValue = handleOldKnowledge(resultValue, "knowledge_codes", "vector_database_ids")
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
				<KnowledgeSelect name="vector_database_ids" multiple />
				<Form.Item name="query" label={t("common.searchKeywords", { ns: "flow" })}>
					<DelightfulExpressionWrap
						placeholder={getExpressionPlaceholder(
							t("common.searchKeywordsPlaceholder", { ns: "flow" }),
						)}
						dataSource={expressionDataSource}
						onlyExpression
						mode={ExpressionMode.TextArea}
					/>
				</Form.Item>
				<Form.Item
					name="limit"
					label={t("common.maxReturnCount", { ns: "flow" })}
					extra={t("common.maxReturnCountDesc", { ns: "flow" })}
				>
					<InputNumber />
				</Form.Item>
				<Form.Item
					name="score"
					label={t("common.minMatchRatio", { ns: "flow" })}
					extra={t("common.minMatchRatioDesc", { ns: "flow" })}
				>
					<DelightfulSlider min={0} max={1} step={0.1} />
				</Form.Item>
				<Form.Item
					name={["metadata_filter", "structure"]}
					label={t("common.metadataMatch", { ns: "flow" })}
					className={styles.metadata}
					valuePropName="data"
					style={{ marginBottom: 0 }}
				>
					<DelightfulJsonSchemaEditor
						allowExpression
						expressionSource={expressionDataSource}
						displayColumns={[ShowColumns.Key, ShowColumns.Type, ShowColumns.Value]}
						customOptions={{
							root: [FormItemType.Object],
							normal: [FormItemType.Number, FormItemType.String, FormItemType.Array],
						}}
					/>
				</Form.Item>

				{/* @ts-ignore  */}
				<Output value={currentNode?.output} wrapperClassName={styles.output} />
			</Form>
		</div>
	)
}





