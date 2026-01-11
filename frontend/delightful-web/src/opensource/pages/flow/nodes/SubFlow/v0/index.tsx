import DropdownCard from "@delightful/delightful-flow/dist/common/BaseUI/DropdownCard"
import { useMemo, useState } from "react"
import { useCurrentNode } from "@delightful/delightful-flow/dist/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { ShowColumns } from "@delightful/delightful-flow/dist/DelightfulJsonSchemaEditor/constants"
import JSONSchemaRenderer from "@/opensource/pages/flow/components/JSONSchemaRenderer"
import { useFlowStore } from "@/opensource/stores/flow"
import { Form, Select } from "antd"
import { useMemoizedFn, useMount, useUpdateEffect } from "ahooks"
import { get, set } from "lodash-es"
import type Schema from "@delightful/delightful-flow/dist/DelightfulJsonSchemaEditor/types/Schema"
import DelightfulJsonSchemaEditor from "@delightful/delightful-flow/dist/DelightfulJsonSchemaEditor"
import { useNodeConfigActions } from "@delightful/delightful-flow/dist/DelightfulFlow/context/FlowContext/useFlow"
import { replaceRouteParams } from "@/utils/route"
import { RoutePath } from "@/const/routes"
import RenderLabelCommon from "@/opensource/pages/flow/components/RenderLabel/RenderLabel"
import { useTranslation } from "react-i18next"
import { FlowRouteType } from "@/types/flow"
import { FlowApi } from "@/apis"
import usePrevious from "../../../common/hooks/usePrevious"
import styles from "./index.module.less"
import { v0Template } from "./template"

export default function SubFlowV0() {
	const { t } = useTranslation()
	const [form] = Form.useForm()
	const { currentNode } = useCurrentNode()
	const [input, setInput] = useState<Schema>()

	const { updateNodeConfig } = useNodeConfigActions()

	const { subFlows } = useFlowStore()

	const flowInfo = useMemo(() => {
		return {
			name: t("common.flow", { ns: "flow" }),
			list: subFlows.filter((f) => f.enabled),
		}
	}, [subFlows, t])

	const subFlowOptions = useMemo(() => {
		return flowInfo.list.map((flow) => {
			return {
				label: (
					<RenderLabelCommon
						name={flow.name}
						url={`${replaceRouteParams(RoutePath.FlowDetail, {
							id: flow.id as string,
							type: FlowRouteType.Sub,
						})}`}
					/>
				),
				value: flow.id,
				realLabel: flow.name,
			}
		})
	}, [flowInfo.list])

	const { expressionDataSource } = usePrevious()

	// Handle when sub-flow is dragged directly from the left material panel
	useMount(() => {
		if (!currentNode) return
		const subFlowId = get(currentNode, ["params", "sub_flow_id"])
		const subFlowInput = get(currentNode, ["input"])
		if (subFlowId && !subFlowInput) {
			FlowApi.getSubFlowArguments(subFlowId).then((subFlow) => {
				const index = subFlows.findIndex((flow) => flow.id === subFlow.id)
				if (index !== -1) {
					const oldFlowDetail = subFlows[index]
					set(currentNode, ["params", "avatar"], oldFlowDetail.icon)
					set(currentNode, ["output"], subFlow.output)
					if (!currentNode?.input) {
						set(currentNode, ["input"], subFlow.input)
						setInput(subFlow?.input?.form?.structure)
					} else {
						setInput(currentNode?.input?.form?.structure)
					}

					updateNodeConfig({ ...currentNode })
				}
			})
		}
	})

	/** Sync upstream from downstream */
	const onValuesChange = useMemoizedFn(async (changeValues) => {
		if (!currentNode) return
		if (Reflect.has(changeValues, "sub_flow_id")) {
			const subFlowId = changeValues.sub_flow_id
			const subFlow = await FlowApi.getSubFlowArguments(subFlowId)
			const subFlowDetail = subFlows.find((flow) => flow.id === subFlowId)
			setInput(subFlow.input?.form.structure)
			set(currentNode, ["output"], subFlow.output)
			set(currentNode, ["input"], subFlow.input)
			set(currentNode, ["params", "avatar"], subFlowDetail?.icon)
			set(currentNode, ["params", "sub_flow_id"], subFlowId)
			updateNodeConfig({ ...currentNode })
		}
	})

	/** Sync downstream from upstream */
	useMount(() => {
		const serverInput = get(currentNode, ["input", "form", "structure"])
		setInput(serverInput)
	})

	/** Sync to node when input updates */
	useUpdateEffect(() => {
		if (!currentNode) return
		set(currentNode, ["input", "form", "structure"], input)
		const subFlowId = get(currentNode, ["params", "sub_flow_id"])
		form.setFieldsValue({
			sub_flow_id: subFlowId,
		})
	}, [input])

	const initialValues = useMemo(() => {
		return {
			input: currentNode?.input,
			output: currentNode?.output,
			...{ ...v0Template.params, ...currentNode?.params },
		}
	}, [currentNode?.input, currentNode?.output, currentNode?.params])

	return (
		<Form
			form={form}
			className={styles.subFlow}
			layout="vertical"
			onValuesChange={onValuesChange}
			initialValues={initialValues}
		>
			<Form.Item
				name="sub_flow_id"
				className={styles.select}
				label={`${t("sub.selectFlow", { ns: "flow" })}`}
			>
				<Select
					options={subFlowOptions}
					style={{ width: "100%" }}
					popupClassName="nowheel"
					className="nodrag"
					getPopupContainer={(triggerNode) => triggerNode.parentNode}
					labelRender={(props: any) => {
						if (!props.value) return undefined
						if (!props.label) {
							return (
								<RenderLabelCommon
									name={t("sub.invalidFlow", { ns: "flow" })}
									url={`${replaceRouteParams(RoutePath.FlowDetail, {
										id: props.value as string,
										type: FlowRouteType.Sub,
									})}`}
									danger
								/>
							)
						}

						return props.label
					}}
					showSearch
					// Search key
					optionFilterProp="realLabel"
					// Sort
					filterSort={(optionA, optionB) => {
						return (optionA?.realLabel ?? "")
							.toLowerCase()
							.localeCompare((optionB?.realLabel ?? "").toLowerCase())
					}}
				/>
			</Form.Item>
			<div className={styles.input}>
				<DropdownCard title={t("common.input", { ns: "flow" })} height="auto">
					<DelightfulJsonSchemaEditor
						data={input}
						onChange={setInput}
						allowExpression
						expressionSource={expressionDataSource}
						displayColumns={[
							ShowColumns.Key,
							ShowColumns.Value,
							ShowColumns.Type,
							ShowColumns.Required,
						]}
						columnNames={{
							[ShowColumns.Key]: t("common.variableName", { ns: "flow" }),
							[ShowColumns.Type]: t("common.variableType", { ns: "flow" }),
							[ShowColumns.Value]: t("common.variableValue", { ns: "flow" }),
							[ShowColumns.Label]: t("common.showName", { ns: "flow" }),
							[ShowColumns.Description]: t("common.variableDesc", { ns: "flow" }),
							[ShowColumns.Encryption]: t("common.encryption", { ns: "flow" }),
							[ShowColumns.Required]: t("common.required", { ns: "flow" }),
						}}
						showAdd={false}
					/>
				</DropdownCard>
			</div>

			<div className={styles.output}>
				<DropdownCard title={t("common.output", { ns: "flow" })} height="auto">
					{/* @ts-ignore */}
					<JSONSchemaRenderer form={currentNode?.output?.form?.structure} />
				</DropdownCard>
			</div>
		</Form>
	)
}





