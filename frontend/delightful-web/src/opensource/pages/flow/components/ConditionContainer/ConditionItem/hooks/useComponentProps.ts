import { useMemo, useState } from "react"
import { Schema, type Sheet } from "@/types/sheet"
import type { Condition } from "../../types"
import {
	getColumnTargetType,
	getColumnType,
	getNotSupportedFilterColumns,
	getValueOptions,
	judgeIsMultiple,
} from "../../helpers"
import { AutomateFlowFieldGroup, ROW_ID_COLUMN_ID } from "../../constants"
import { getPlaceholder } from "@/opensource/pages/flow/utils/helpers"

type UseComponentProps = {
	condition: Condition
	columns: Sheet.Content["columns"]
	isSupportRowId: boolean
	sheetId: string
	dataTemplate: Record<string, Sheet.Detail>
}

export default function useComponentProps({
	condition,
	columns,
	isSupportRowId,
	sheetId,
	dataTemplate,
}: UseComponentProps) {
	const [notSupportedFilterColumns] = useState(() => getNotSupportedFilterColumns())

	// Current column target type; formula and lookup ultimately use value or referenced type
	const displayType = useMemo(() => {
		return getColumnTargetType(sheetId, condition?.column_id, dataTemplate)
	}, [condition?.column_id, dataTemplate, sheetId])

	// Left-side selectable field list
	const leftColumnOptions = useMemo(() => {
		const columnsOption = Object.keys(columns)
			.filter((columnId) => {
				return (
					!notSupportedFilterColumns.includes(columns[columnId]?.columnType) &&
					Reflect.has(AutomateFlowFieldGroup, columns[columnId]?.columnType)
				)
			})
			.map((columnId) => columns[columnId])

		if (isSupportRowId) {
			columnsOption.push({
				columnId: ROW_ID_COLUMN_ID,
				id: ROW_ID_COLUMN_ID,
				label: "Row Record ID",
				columnType: Schema.ROW_ID,
				columnProps: {},
			})
		}
		return columnsOption
	}, [columns, isSupportRowId, notSupportedFilterColumns])

	/** Center selectable condition list */
	const centerConditionOptions = useMemo(() => {
		const compareOption = AutomateFlowFieldGroup[displayType as Schema]?.conditions || []
		return compareOption
	}, [displayType])

	/** Dynamic props for right-side expression component */
	const rightExpressionProps = useMemo(() => {
		const column = columns[condition?.column_id] || {}
		// Original column type
		const columnType = getColumnType(columns, condition?.column_id)
		const valueOptions = getValueOptions({
			columns,
			condition,
			dataTemplate,
		})
		const isMultiple = judgeIsMultiple(column, condition)
		const placeholder = getPlaceholder(column, displayType, true)
		return {
			options: valueOptions,
			isMultiple,
			placeholder,
			columnType,
		}
	}, [columns, condition, dataTemplate, displayType])

	return {
		leftColumnOptions,
		centerConditionOptions,
		rightExpressionProps,
	}
}
