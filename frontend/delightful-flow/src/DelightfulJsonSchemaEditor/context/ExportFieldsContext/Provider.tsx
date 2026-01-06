import Schema from "@/DelightfulJsonSchemaEditor/types/Schema"
import SchemaDescription from "@/DelightfulJsonSchemaEditor/types/SchemaDescription"
import { useUpdateEffect } from "ahooks"
import _ from "lodash"
import type { PropsWithChildren } from "react"
import React, { useMemo, useState } from "react"

export const ExportFieldsContext = React.createContext({
	exportFields: new SchemaDescription(), // Fields to export
	showExportCheckbox: false, // Whether to show the export column
	setShowExportCheckbox: (() => {}) as React.Dispatch<React.SetStateAction<boolean>>,
})

export const ExportFieldsProvider = ({
	defaultExportFields,
	children,
}: PropsWithChildren<{
	defaultExportFields: Schema
}>) => {
	const [exportFields, setExportFields] = useState(
		new SchemaDescription({...defaultExportFields}),
	)
	useUpdateEffect(() => {
		setExportFields(new SchemaDescription({ ...defaultExportFields }))
	}, [defaultExportFields])

	/** Whether to enable the export option */
	const [showExportCheckbox, setShowExportCheckbox] = useState(false)

	// console.log("EXPORT_FEIDLS", JSON.parse(JSON.stringify(exportFields.schema)))

	const value = useMemo(() => {
		return {
			exportFields,
			showExportCheckbox,
			setShowExportCheckbox,
		}
	}, [exportFields, showExportCheckbox, setShowExportCheckbox])

	return <ExportFieldsContext.Provider value={value}>{children}</ExportFieldsContext.Provider>
}

