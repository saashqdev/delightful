import { Flex, Form, Tooltip, Flex as AntdFlex } from "antd"
import DropdownCard from "@delightful/delightful-flow/dist/common/BaseUI/DropdownCard"
import { IconCircleMinus, IconHelp } from "@tabler/icons-react"
import DelightfulSelect from "@delightful/delightful-flow/dist/common/BaseUI/Select"
import DelightfulSlider from "@delightful/delightful-flow/dist/common/BaseUI/Slider"
import { useTranslation } from "react-i18next"
import TSIcon from "@/opensource/components/base/TSIcon"
import useFormListRemove from "@/opensource/pages/flow/common/hooks/useFormListRemove"
import styles from "./KnowledgeDataList.module.less"
import usePanelConfig from "./hooks/usePanelConfig"
import { useMemo } from "react"
import { useCommercial } from "@/opensource/pages/flow/context/CommercialContext"
import { getKnowledgeTypeOptions } from "../../helpers"
import KnowledgeDatabaseSelectV1 from "../KnowledgeDatabaseSelect/TeamshareKnowledgeSelect"
import useKnowledgeDatabases from "./hooks/useKnowledgeDatabase"
import useProgress from "./hooks/useProgress"
import { knowledgeType } from "@/opensource/pages/vectorKnowledge/constant"
import UserKnowledgeSelect from "../KnowledgeDatabaseSelect/UserKnowledgeSelect"

type KnowledgeDataListProps = {
	handleAdd: () => void
	knowledgeListName?: string[]
	limitName?: string[]
	scoreName?: string[]
}

// Knowledge data list component
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

	const { teamshareDatabaseOptions, userDatabaseOptions, userDatabasePopupScroll } =
		useKnowledgeDatabases(form)

	const { progressList, initInterval, setProgressList } = useProgress({
		knowledgeListName,
	})

	const isCommercial = useMemo(() => !!extraData, [extraData])

	// Get Form.List field value to determine if data exists
	const knowledgeList = Form.useWatch(knowledgeListName, form) || []
	const hasKnowledgeData = Array.isArray(knowledgeList) && knowledgeList.length > 0

	const knowledgeDataOptions = useMemo(() => {
		return getKnowledgeTypeOptions(t, isCommercial)
	}, [t, isCommercial])

	// Render right component based on knowledge_type
	const renderRightComponent = (knowledgeTypeValue: number, subField: any) => {
		switch (knowledgeTypeValue) {
			case knowledgeType.UserKnowledgeDatabase:
				// User-created knowledge base
				return (
					<Form.Item noStyle name={[subField.name]}>
						<UserKnowledgeSelect
							options={userDatabaseOptions}
							onPopupScroll={userDatabasePopupScroll}
						/>
					</Form.Item>
				)
			case knowledgeType.TeamshareKnowledgeDatabase:
				// Tiangshu knowledge base
				return (
					<Form.Item noStyle name={[subField.name]}>
						{isCommercial ? (
							<KnowledgeDatabaseSelectV1
								options={teamshareDatabaseOptions}
								progressList={progressList}
								setProgressList={setProgressList}
								initInterval={initInterval}
							/>
						) : (
							<DelightfulSelect key={knowledgeTypeValue} options={[]} />
						)}
					</Form.Item>
				)
			default:
				return <DelightfulSelect key={knowledgeTypeValue} options={[]} />
		}
	}

	return (
		<div className={styles.knowledgeDataWrap}>
			<DropdownCard
				title={t("common.knowledgeData", { ns: "flow" })}
				height="auto"
				// Remove suffixIcon; no longer show add button in headerRight
			>
				{/* Add button, using the same style as addToolBtn in ToolSelect.tsx */}
				<div className={styles.knowledgeDataWrap}>
					<Form.Item>
						<Form.List name={knowledgeListName}>
							{(subFields) => {
								return (
									<div className={styles.knowledgeList}>
										{subFields.map((subField, i) => {
											// Safely read knowledge_type of current item from fetched knowledgeList
											const currentKnowledgeType =
												knowledgeList[i]?.knowledge_type

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
																fieldNames={{
																	label: "label",
																	value: "value",
																}}
															/>
														</Form.Item>
													</div>
													<div className={styles.right}>
														{renderRightComponent(
															currentKnowledgeType,
															subField,
														)}
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

				{/* Only show config items when there is knowledge data */}
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

