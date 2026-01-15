import { Flex, Form, Tooltip, Flex as AntdFlex } from "antd"
import DropdownCard from "@bedelightful/delightful-flow/dist/common/BaseUI/DropdownCard"
import { IconCircleMinus, IconHelp } from "@tabler/icons-react"
import DelightfulSelect from "@bedelightful/delightful-flow/dist/common/BaseUI/Select"
import DelightfulSlider from "@bedelightful/delightful-flow/dist/common/BaseUI/Slider"
import { useTranslation } from "react-i18next"
import TSIcon from "@/opensource/components/base/TSIcon"
import useFormListRemove from "@/opensource/pages/flow/common/hooks/useFormListRemove"
import styles from "./KnowledgeDataList.module.less"
import usePanelConfig from "./hooks/usePanelConfig"
import { useMemo } from "react"
import { useCommercial } from "@/opensource/pages/flow/context/CommercialContext"
import { getKnowledgeTypeOptions } from "../../helpers"

type KnowledgeDataListProps = {
	handleAdd: () => void
	knowledgeListName?: string[]
	limitName?: string[]
	scoreName?: string[]
}

export default function KnowledgeDataListV1({
	handleAdd,
	limitName = ["limit"],
	scoreName = ["score"],
	knowledgeListName = ["knowledge_list"],
}: KnowledgeDataListProps) {
	const { t } = useTranslation()
	const { removeFormListItem } = useFormListRemove()
	const form = Form.useFormInstance()
	const extraData = useCommercial()

	const { limit, score } = usePanelConfig()

	// Get Form.List field values to determine if there is data
	const knowledgeList = Form.useWatch(knowledgeListName) || []
	const hasKnowledgeData = Array.isArray(knowledgeList) && knowledgeList.length > 0

	const knowledgeDataOptions = useMemo(() => {
		return getKnowledgeTypeOptions(t, !!extraData)
	}, [t, extraData])

	return (
		<div className={styles.knowledgeDataWrap}>
			<DropdownCard
				title={t("common.knowledgeData", { ns: "flow" })}
				height="auto"
				// Remove suffixIcon, no longer show add button in headerRight
			>
				{/* Add button, using the same style as addToolBtn in ToolSelect.tsx */}
				<div className={styles.knowledgeDataWrap}>
					<Form.Item>
						<Form.List name={knowledgeListName}>
							{(subFields) => {
								return (
									<div className={styles.knowledgeList}>
										{subFields.map((subField, i) => {
											return (
												<Flex
													key={subField.key}
													className={styles.knowledgeItem}
													gap={10}
												>
													<div className={styles.left}>
														<Form.Item
															noStyle
															name={[subField.name, "knowledge_type"]}
														>
															<DelightfulSelect
																options={knowledgeDataOptions}
															/>
														</Form.Item>
													</div>
													<div className={styles.right}>
														<Form.Item noStyle name={[subField.name]}>
															<DelightfulSelect
																options={knowledgeDataOptions}
															/>
														</Form.Item>
													</div>
													<span
														className={styles.deleteIcon}
														onClick={() => {
															removeFormListItem(
																form,
																knowledgeListName,
																i,
															)
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
									</div>
								)
							}}
						</Form.List>
					</Form.Item>
					<AntdFlex
						className={styles.addKnowledgeBtn}
						justify="center"
						align="center"
						gap={4}
						onClick={handleAdd}
					>
						<TSIcon type="ts-add" />
						{t("common.addKnowledgeData", { ns: "flow" })}
					</AntdFlex>
				</div>

				{/* Show config items only when there is knowledge data */}
				{hasKnowledgeData && (
					<>
						<div className={styles.parameters}>
							<div className={styles.left}>
								<span className={styles.title}>{limit.label}</span>
								<Tooltip title={limit.tooltips}>
									<IconHelp size={16} color="#1C1D2399" className={styles.icon} />
								</Tooltip>
							</div>
							<div className={styles.right}>
								<Form.Item name={limitName}>
									<DelightfulSlider
										min={limit.extra.min}
										max={limit.extra.max}
										step={limit.extra.step}
										className={styles.slider}
										marks={{
											[limit.extra.min]: `${limit.extra.min}`,
											[limit.extra.max]: `${limit.extra.max}`,
											[limit.defaultValue]: `${t("common.recommend", {
												ns: "flow",
											})} ${limit.defaultValue}`,
										}}
									/>
								</Form.Item>
							</div>
						</div>
						<div className={styles.parameters}>
							<div className={styles.left}>
								<span className={styles.title}>{score.label}</span>
								<Tooltip title={score.tooltips}>
									<IconHelp size={16} color="#1C1D2399" className={styles.icon} />
								</Tooltip>
							</div>
							<div className={styles.right}>
								<Form.Item name={scoreName}>
									<DelightfulSlider
										min={score.extra.min}
										max={score.extra.max}
										step={score.extra.step}
										className={styles.slider}
										marks={{
											[score.extra.min]: `${score.extra.min}`,
											[score.extra.max]: `${score.extra.max}`,
											[score.defaultValue]: `${t("common.recommend", {
												ns: "flow",
											})} ${score.defaultValue}`,
										}}
									/>
								</Form.Item>
							</div>
						</div>
					</>
				)}
			</DropdownCard>
		</div>
	)
}
