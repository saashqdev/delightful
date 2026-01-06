import { getDataSourceMap, getExpressionFirstItem } from "@/DelightfulExpressionWidget/helpers"
import { FormItemType } from "@/DelightfulExpressionWidget/types"
import { getDefaultBooleanConstantSource } from "@/DelightfulJsonSchemaEditor/components/schema-json/schema-item/constants"
import DelightfulSelect from "@/common/BaseUI/Select"
import { DelightfulExpressionWidget } from "@/index"
import { IconCircleMinus, IconCirclePlus } from "@tabler/icons-react"
import classname from "clsx"
import i18next from "i18next"
import React, { useCallback, useMemo, useState } from "react"
import { useTranslation } from "react-i18next"
import type { CacheDictionary, ChangeRef, OptionsProps } from ".."
import { CONDITION_OPTIONS, RELATION_COMP_TYPE, SpecialConditionValues } from "../constants"
import { useGlobal } from "../context/Global/useGlobal"
import { RelationItemStyle } from "../style"
import { Expression } from "../types/expression"

interface RelationItemProps {
	pos: string
	changeRef: React.MutableRefObject<ChangeRef>
	conditionData: Expression.CompareNode | Expression.OperationNode
	options: OptionsProps
	cacheDictionary: CacheDictionary
	readonly: boolean
}

export function RelationItem({
	pos,
	// Current condition editor data
	conditionData,
	changeRef,
	options,
	// Data source
	cacheDictionary,
	readonly,
}: RelationItemProps) {
	const { t } = useTranslation()
	const { maxGroupDepth, openConvertButton, termWidth, expressionSource } = options
	const { leftDisabledPos, disabledOperationPos, showTitlePosList } = useGlobal()

	// eslint-disable-next-line prefer-const
	let [cur, setCur] = useState(conditionData)
	const handleAddConditionGroup = useCallback(() => {
		changeRef.current.addConditionGroup(pos)
	}, [changeRef, pos])

	const handleRemoveConditionItem = useCallback(() => {
		changeRef.current.removeConditionItem(pos)
	}, [changeRef, pos])

	const handleConvertConditionItem = useCallback(() => {
		changeRef.current.convertConditionItem(pos)
	}, [changeRef, pos])

	// const handleBlur = useCallback(() => {
	// 	if (!_.isEqual(cur, conditionData)) changeRef.current.updateConditionData(pos, cur, true)
	// }, [changeRef, conditionData, cur, pos])

	const handleChange = (
		value: any,
		key: keyof Expression.CompareNode | keyof Expression.OperationNode,
	) => {
		cur = { ...cur, [key]: value }
		setCur({ ...cur, [key]: value })
		changeRef.current.updateConditionData(pos, cur, false)
	}

	/** For compare mode, display only the first value */
	const conditionValue = useMemo(() => {
		const { condition } = cur as Expression.CompareNode
		return condition || "equals"
	}, [cur])

	const computedRightStyle = useMemo(() => {
		if (SpecialConditionValues.includes(conditionValue)) {
			return {
				display: "none",
			}
		}
		return {
			display: "block",
		}
	}, [conditionValue])

	// Whether to show titles
	const showTitle = useMemo(() => {
		return showTitlePosList.includes(pos)
	}, [showTitlePosList, pos])

	const { expressionSourceWithDefaultOptions, rightOperandsOnlyExpression } = useMemo(() => {
		const expressionValue = getExpressionFirstItem(
			(cur as Expression.CompareNode).left_operands,
		)
		const dataSourceMap = getDataSourceMap(expressionSource || [])
		const leftSelectedOption = dataSourceMap?.[expressionValue?.value]
		if (leftSelectedOption?.type !== FormItemType.Boolean)
			return {
				expressionSourceWithDefaultOptions: expressionSource,
				rightOperandsOnlyExpression: false,
			}
		const booleanConstants = getDefaultBooleanConstantSource()
		return {
			expressionSourceWithDefaultOptions: [...booleanConstants, ...(expressionSource || [])],
			rightOperandsOnlyExpression: leftSelectedOption?.type === FormItemType.Boolean,
		}
	}, [expressionSource, cur])

	return (
		<RelationItemStyle className="relation-item">
			<div className="condition_vertical_fields">
				{cur.type === RELATION_COMP_TYPE.COMPARE && (
					<div className={classname("condition_vertical_row")}>
						<div className="condition_vertical_col left">
							<div className="condition_fields-item">
								{showTitle && (
									<div className="title">
										{i18next.t("common.referenceVariables", {
											ns: "magicFlow",
										})}
									</div>
								)}
								<DelightfulExpressionWidget
									onChange={(value) => {
										handleChange(value, "left_operands")
									}}
									value={(cur as Expression.CompareNode).left_operands}
									dataSource={expressionSource}
									allowExpression
									allowModifyField
									disabled={leftDisabledPos.includes(pos)}
									onlyExpression
									referencePlaceholder={i18next.t(
										"common.expressionPlaceholder",
										{
											ns: "magicFlow",
										},
									)}
								/>
							</div>
						</div>
						<div className="condition_vertical_col compare">
							<div className="condition_fields-item">
								{showTitle && (
									<div className="title">
										{i18next.t("common.selectConditions", { ns: "magicFlow" })}
									</div>
								)}
								<DelightfulSelect
									style={{ width: "100%" }}
									onChange={(value: any) => handleChange(value, "condition")}
									options={CONDITION_OPTIONS}
									value={conditionValue}
								/>
							</div>
						</div>
						<div className="condition_vertical_col right">
							<div className="condition_fields-item" style={computedRightStyle}>
								{showTitle && (
									<div className="title">
										{i18next.t("common.compareValue", { ns: "magicFlow" })}
									</div>
								)}
								<DelightfulExpressionWidget
									onChange={(value) => {
										handleChange(value, "right_operands")
									}}
									value={(cur as Expression.CompareNode).right_operands}
									dataSource={expressionSourceWithDefaultOptions}
									allowExpression
									allowModifyField
									onlyExpression={rightOperandsOnlyExpression}
								/>
							</div>
						</div>
					</div>
				)}
				{cur.type === RELATION_COMP_TYPE.OPERATION && (
					<div className="condition_vertical_row">
						<div className="condition_vertical_col">
							<div className="condition_fields-item">
								<DelightfulExpressionWidget
									value={(cur as Expression.OperationNode).operands}
									dataSource={expressionSource}
									onChange={(value) => handleChange(value, "operands")}
									allowExpression
									allowModifyField
								/>
							</div>
						</div>
					</div>
				)}
				{
					<div className="right-condition_operations">
						{showTitle && (
							<div className="title">
								{i18next.t("common.operation", { ns: "magicFlow" })}
							</div>
						)}

						<div className="condition_vertical_panel">
							{pos.split("-").length < maxGroupDepth && (
								<span className="add-icon" onClick={handleAddConditionGroup}>
									<IconCirclePlus stroke={1} size={20} color="#315CEC" />
								</span>
							)}
							{
								openConvertButton && null
								// <Button
								// 	type="light"
								// 	theme="primary"
								// 	onClick={handleConvertConditionItem}
								// 	style={{
								// 		visibility: disabledOperationPos.includes(pos)
								// 			? "hidden"
								// 			: "visible",
								// 	}}
								// >
								// 	Switch
								// </Button>
							}

							<span
								className="delete-icon"
								onClick={handleRemoveConditionItem}
								style={{
									visibility: disabledOperationPos.includes(pos)
										? "hidden"
										: "visible",
								}}
							>
								<IconCircleMinus stroke={1} size={20} color="#1C1D2399" />
							</span>
						</div>
					</div>
				}
			</div>
		</RelationItemStyle>
	)
}
