import DropdownCard from "@delightful/delightful-flow/dist/common/BaseUI/DropdownCard"
import { Flex, Form } from "antd"
import DelightfulSelect from "@delightful/delightful-flow/dist/common/BaseUI/Select"
import DelightfulExpressionWrap from "@delightful/delightful-flow/dist/common/BaseUI/DelightfulExpressionWrap"
import { ExpressionMode } from "@delightful/delightful-flow/dist/DelightfulExpressionWidget/constant"
import { IconCircleMinus, IconPlus } from "@tabler/icons-react"
import { useMemo } from "react"
import { useCurrentNode } from "@delightful/delightful-flow/dist/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { useMemoizedFn } from "ahooks"
import { cloneDeep, set } from "lodash-es"
import { useNodeConfigActions } from "@delightful/delightful-flow/dist/DelightfulFlow/context/FlowContext/useFlow"
import usePrevious from "@/opensource/pages/flow/common/hooks/usePrevious"
import useCurrentNodeUpdate from "@/opensource/pages/flow/common/hooks/useCurrentNodeUpdate"
import NodeOutputWrap from "@/opensource/pages/flow/components/NodeOutputWrap/NodeOutputWrap"
import { useTranslation } from "react-i18next"
import useFormListRemove from "@/opensource/pages/flow/common/hooks/useFormListRemove"
import useFilters from "./hooks/useFilters"
import styles from "./VectorDatabaseMatch.module.less"
import { filterTargetOptions } from "./constants"
import { getDefaultFilter, getFilterOption } from "./helpers"
import { v0Template } from "./template"

export default function VectorDatabaseMatchV0() {
	const { t } = useTranslation()
	const [form] = Form.useForm()
	const { currentNode } = useCurrentNode()
	const { FilterTypeSelector } = useFilters()
	const { updateNodeConfig } = useNodeConfigActions()
	const { removeFormListItem } = useFormListRemove()

	const { expressionDataSource } = usePrevious()

	const initialValues = useMemo(() => {
		return {
			...v0Template.params,
			...currentNode?.params,
		}
	}, [currentNode])
	// @ts-ignore
	const onValuesChange = useMemoizedFn((changedValues, allValues) => {
		if (!currentNode) return

		// Use deep clone to avoid reference issues
		const updatedParams = cloneDeep(allValues)

		// Update node parameters to the latest form values
		set(currentNode, ["params"], {
			...currentNode.params,
			...updatedParams,
		})

		updateNodeConfig({ ...currentNode })
	})

	useCurrentNodeUpdate({
		form,
		initialValues,
	})

	return (
		<NodeOutputWrap className={styles.vectorDatabaseMatch}>
			<Form form={form} initialValues={initialValues} onValuesChange={onValuesChange}>
				<DropdownCard
					title={t("common.searchConditions", { ns: "flow" })}
					height="auto"
					suffixIcon={FilterTypeSelector}
				>
					<Form.Item>
						<Form.List name={["filters"]}>
							{(subFields, subOpt) => {
								const filters = form.getFieldValue(["filters"])
								return (
									<div className={styles.filters}>
										{subFields.map((subField, i) => {
											const filterItem = form.getFieldValue(["filters", i])
											const operatorOptions = getFilterOption(
												filterItem?.left,
											)
											return (
												<Flex
													key={subField.key}
													className={styles.filterItem}
													gap={10}
												>
													<div className={styles.left}>
														<Form.Item
															noStyle
															name={[subField.name, "left"]}
														>
															<DelightfulSelect
																options={filterTargetOptions}
															/>
														</Form.Item>
													</div>
													<div className={styles.operator}>
														<Form.Item
															noStyle
															name={[subField.name, "operator"]}
														>
															<DelightfulSelect
																options={operatorOptions}
															/>
														</Form.Item>
													</div>
													<div className={styles.right}>
														<Form.Item
															noStyle
															name={[subField.name, "right"]}
															className={styles.right}
														>
															<DelightfulExpressionWrap
																placeholder={t(
																	"common.allowExpressionPlaceholder",
																	{ ns: "flow" },
																)}
																dataSource={expressionDataSource}
																mode={ExpressionMode.Common}
															/>
														</Form.Item>
													</div>
													<span
														className={styles.deleteIcon}
														onClick={() => {
															removeFormListItem(form, ["filters"], i)
														}}
													>
														<IconCircleMinus
															stroke={1}
															size={20}
															color="#1C1D2399"
														/>
													</span>
												</Flex>
											)
										})}
										<span
											onClick={() =>
												subOpt.add(getDefaultFilter(), filters.length)
											}
											className={styles.addBtn}
										>
											<IconPlus width={20} color="#1C1D23CC" />
											<span>{t("common.addConditions", { ns: "flow" })}</span>
										</span>
									</div>
								)
							}}
						</Form.List>
					</Form.Item>
				</DropdownCard>
			</Form>
		</NodeOutputWrap>
	)
}





