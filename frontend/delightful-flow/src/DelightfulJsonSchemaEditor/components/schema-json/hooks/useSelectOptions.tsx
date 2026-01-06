/** Filter available options */
import { FormItemType } from "@/DelightfulExpressionWidget/types"
import { SCHEMA_TYPE } from "@/DelightfulJsonSchemaEditor/constants"
import { cleanAndFilterArray, getSelectOptions } from "@/DelightfulJsonSchemaEditor/utils/helpers"
import { useContext, useMemo } from "react"
import { EditorContext } from "../../editor"

export default function useSelectOptions() {
	const context = useContext(EditorContext)
	const FILTER_OPTIONS = cleanAndFilterArray<FormItemType>((context.customOptions as any).normal)

	const NORMAL_OPTIONS = FILTER_OPTIONS.length > 0 ? FILTER_OPTIONS : SCHEMA_TYPE

	const selectOptions = useMemo(() => {
		return getSelectOptions(NORMAL_OPTIONS)
	}, [NORMAL_OPTIONS])

	return {
		selectOptions,
	}
}
