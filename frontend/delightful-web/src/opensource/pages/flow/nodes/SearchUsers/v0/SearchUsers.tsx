import DropdownCard from "@bedelightful/delightful-flow/dist/common/BaseUI/DropdownCard"
import { Flex, Form } from "antd"
import DelightfulSelect from "@bedelightful/delightful-flow/dist/common/BaseUI/Select"
import DelightfulExpressionWrap from "@bedelightful/delightful-flow/dist/common/BaseUI/DelightfulExpressionWrap"
import { ExpressionMode } from "@bedelightful/delightful-flow/dist/DelightfulExpressionWidget/constant"
import { IconCircleMinus, IconPlus } from "@tabler/icons-react"
import { useMemo } from "react"
import { useCurrentNode } from "@bedelightful/delightful-flow/dist/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { useMemoizedFn } from "ahooks"
import { set } from "lodash-es"
import { useFlow } from "@bedelightful/delightful-flow/dist/DelightfulFlow/context/FlowContext/useFlow"
import usePrevious from "@/opensource/pages/flow/common/hooks/usePrevious"
import { customNodeType } from "@/opensource/pages/flow/constants"
import useCurrentNodeUpdate from "@/opensource/pages/flow/common/hooks/useCurrentNodeUpdate"
import NodeOutputWrap from "@/opensource/pages/flow/components/NodeOutputWrap/NodeOutputWrap"
import { useTranslation } from "react-i18next"
import useFilters from "./hooks/useFilters"
import styles from "./SearchUsers.module.less"
import { filterTargetOptions } from "./constants"
import { getDefaultFilter, getFilterOption } from "./helpers"
import useRenderConfig from "./hooks/useRenderConfig"
import { v0Template } from "./template"

export default function SearchUsersV0() {
	const { t } = useTranslation()
	const [form] = Form.useForm()
	const { currentNode } = useCurrentNode()
	const { FilterTypeSelector } = useFilters()
	const { updateNodeConfig } = useFlow()

	const { expressionDataSource } = usePrevious()

	const initialValues = useMemo(() => {
		return {
			...v0Template.params,
			...currentNode?.params,
		}
	}, [currentNode])

	const onValuesChange = useMemoizedFn(() => {
		if (!currentNode) return
		Object.entries(form.getFieldsValue()).forEach(([changeKey, changeValue]) => {
			set(currentNode, ["params", changeKey], changeValue)
		})
		updateNodeConfig({ ...currentNode })
	})

	const { getRenderConfig, getExtraConfig } = useRenderConfig()

	useCurrentNodeUpdate({
		form,
		initialValues,
	})

	return (
		<NodeOutputWrap className={styles.searchUsers}>
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
											const renderConfig = getRenderConfig(filterItem)
											const extraConfig = getExtraConfig(filterItem?.left)
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
																	{
																		ns: "flow",
																	},
																)}
																dataSource={expressionDataSource}
																mode={ExpressionMode.Common}
																// @ts-ignore
																renderConfig={renderConfig}
																{...extraConfig}
															/>
														</Form.Item>
													</div>
													<span
														className={styles.deleteIcon}
														onClick={() => {
															subOpt.remove(subField.name)
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
