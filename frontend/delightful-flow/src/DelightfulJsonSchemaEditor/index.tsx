/* eslint-disable import/extensions */
/* eslint-disable no-unused-vars */
/* eslint-disable @typescript-eslint/no-unused-vars */
import React, { createContext, useEffect, useImperativeHandle, useMemo, useState } from "react"
import { ErrorBoundary } from "react-error-boundary"
import { useTranslation } from "react-i18next"
import { ConfigProvider, message } from "antd"
import "antd/dist/reset.css"
import { reaction } from "mobx"
import { observer } from "mobx-react"
import { ExpressionSource } from "@/DelightfulExpressionWidget/types"
import { DataSourceOption } from "@/common/BaseUI/DropdownRenderer/Reference"
import ErrorContent from "@/common/BaseUI/ErrorComponent/ErrorComponent"
import Editor from "./components/editor"
import { AppendPosition, DefaultColumnNames, DefaultDisplayColumns, ShowColumns } from "./constants"
import { ExportFieldsProvider } from "./context/ExportFieldsContext/Provider"
import { GlobalProvider } from "./context/GlobalContext/Provider"
import { GlobalStyle } from "./global.style"
import useExpressionSource from "./hooks/useExpressionSource"
import "./index.less"
import { Common } from "./types/Common"
import Schema, { CustomFieldsConfig, CustomOptions } from "./types/Schema"
import SchemaDescription from "./types/SchemaDescription"
import { genRootField } from "./utils/helpers"
import { CLASSNAME_PREFIX } from "@/common/constants"

/**
 * @title JsonSchemaEditor
 */
export interface JsonSchemaEditorProps {
	/** Enable mock mode */
	mock?: boolean
	/** Show the JSON editor */
	jsonEditor?: boolean
	/** Callback when schema changes */
	onChange?: (schema: Schema) => void
	/** Initial schema */
	data?: Schema | string
	/** Allow expressions */
	allowExpression?: boolean
	/** Restrict root node to JSON format only */
	onlyJson?: boolean
	/** Custom parameter type options (items, root, normal) */
	customOptions?: CustomOptions
	/** Expression data source */
	expressionSource?: DataSourceOption[]
	/** Enable importing JSON */
	jsonImport?: boolean
	/** Enable debugger mode */
	debuggerMode?: boolean
	/** Developer-only: default value edit mode */
	valueEdit?: boolean
	/** Blur event callback */
	onBlur?: (schema: Schema) => void
	/** Allow add/delete operations */
	allowOperation?: boolean
	/** Require at least one child node */
	oneChildAtLeast?: boolean
	/** Default key for the generated child node */
	firstChildKey?: string
	/** Fields disabled globally (non-editable) */
	disableFields?: string[]
	/** Position for inserting adjacent nodes; defaults to after current field, can append to end */
	relativeAppendPosition?: AppendPosition
	/** TODO Allow using current form fields as an expression data source; do not enable for now */
	allowSourceInjectBySelf?: boolean
	/** Unique ID for the current form component */
	uniqueFormId?: string
	/** Upstream form data source */
	contextExpressionSource?: ExpressionSource
	/** Handler when the current form data source changes */
	onInnerSourceMapChange?: (innerSource: Record<string, Common.Options>) => void

	/** Currently visible columns */
	displayColumns?: ShowColumns[]

	/**
	 * Custom column labels for parameters
	 */
	columnNames?: Record<ShowColumns, string>

	/**
	 * Allow adding new fields
	 */
	allowAdd?: boolean

	/**
	 * Require all values to be set via expressions
	 */
	onlyExpression?: boolean

	/**
	 * Custom configuration for specific fields
	 */
	customFieldsConfig?: CustomFieldsConfig

	/**
	 * Show the import control
	 */
	showImport?: boolean

	/**
	 * Show the first row (root level)
	 */
	showTopRow?: boolean

	/**
	 * Show the global add-parameter action
	 */
	showAdd?: boolean

	/**
	 * Show the operations column
	 */
	showOperation?: boolean

	/**
	 * Trigger updates immediately on initial load
	 */
	fireImmediately?: boolean
}

export const SchemaMobxContext = createContext<SchemaDescription>(new SchemaDescription())

const JsonSchemaObserverEditor = observer(
	React.forwardRef<any, JsonSchemaEditorProps>(
		(
			{
				allowOperation = true,
				oneChildAtLeast = false,
				allowSourceInjectBySelf = false,
				firstChildKey = "field_0",
				disableFields = [],
				allowAdd = true,
				showAdd = true,
				onlyExpression = false,
				showImport = true,
				showTopRow = false,
				showOperation = true,
				relativeAppendPosition = AppendPosition.Tail,
				contextExpressionSource,
				uniqueFormId,
				displayColumns = DefaultDisplayColumns,
				columnNames,
				customFieldsConfig,
				fireImmediately = false,
				onChange,
				onInnerSourceMapChange,
				data,
				...props
			},
			ref,
		) => {
			// eslint-disable-next-line @typescript-eslint/no-unused-vars
			const { t: translate } = useTranslation()
			const [contextVal] = useState<SchemaDescription>(new SchemaDescription())
			const { innerExpressionSourceMap, updateInnerSourceMap } = useExpressionSource({
				allowSourceInjectBySelf,
				uniqueFormId,
			})

			useEffect(() => {
				// Default with a single field
				let defaultSchema = genRootField(oneChildAtLeast, firstChildKey)
				if (data) {
					if (typeof data === "string") {
						try {
							defaultSchema = JSON.parse(data)
						} catch (e) {
							message.error("Provided string is not valid JSON!")
						}
					} else if (Object.prototype.toString.call(data) === "[object Object]") {
						// Fix: no add button on first row when data is an empty object
						if (Object.keys(data).length > 0) {
							defaultSchema = data as typeof defaultSchema
						}
						// Fix: PHP may send properties as an empty array
						if (Array.isArray(data?.properties)) {
							defaultSchema.properties = {}
						}
					} else {
						message.error("JSON data supports only string or object")
					}
				}
				contextVal.changeSchema(defaultSchema)
			}, [JSON.stringify(data), oneChildAtLeast, firstChildKey])

			// Handle reaction separately to create it once and clean up on unmount
			useEffect(() => {
				// Create reaction instance
				const disposer = reaction(
					() => contextVal.schema,
					(schema) => {
						if (onChange) {
							onChange(JSON.parse(JSON.stringify(schema)))
						}
					},
					{ fireImmediately },
				)

				// Return cleanup function to run on unmount or dependency change
				return () => {
					disposer()
				}
			}, [contextVal, onChange, fireImmediately])

			useEffect(() => {
				const newInnerSourceMap = updateInnerSourceMap(contextVal.schema)
				if (onInnerSourceMapChange) {
					onInnerSourceMapChange(newInnerSourceMap)
				}
			}, [uniqueFormId, contextVal.schema, updateInnerSourceMap, onInnerSourceMapChange])

			// APIs exposed to consumers
			useImperativeHandle(
				ref,
				() => ({
					/** Add a child field under the root node */
					addRootChildField: (fieldName: string) => {
						const allKeys = Object.keys(contextVal.schema.properties || {})
						// Skip if it already exists
						if (!allKeys.includes(fieldName)) {
							contextVal.addChildField({
								keys: ["properties"],
								customFieldName: fieldName,
							})
						}
					},
					/** Delete a child field under the root node */
					deleteRootChildField: (fieldName: string) => {
						contextVal.deleteField({
							keys: ["properties", fieldName],
						})
					},
					/** Delete child fields not included in fieldNames */
					deleteRootChildFieldsNotIn: (fieldNames: string[]) => {
						const allKeys = Object.keys(contextVal.schema.properties || {})
						let delKeys = [] as string[]
						if (allKeys.length > 0) {
							delKeys = allKeys.filter((key) => !fieldNames.includes(key)) as string[]
						}
						delKeys.forEach((delKey) => {
							contextVal.deleteField({
								keys: ["properties", delKey],
							})
						})
					},
				}),
				[contextVal],
			)

			const mergedColumnNames = useMemo(() => {
				return {
					...DefaultColumnNames(),
					...(columnNames || {}),
				}
			}, [columnNames])

			return (
				<ErrorBoundary
					fallbackRender={({ error }) => {
						// eslint-disable-next-line no-console
						console.log("error", error)
						return <ErrorContent />
					}}
				>
					<ConfigProvider prefixCls={CLASSNAME_PREFIX}>
						<div>
							<GlobalStyle />
							<GlobalProvider
								allowOperation={allowOperation}
								allowAdd={allowAdd}
								showAdd={showAdd}
								disableFields={disableFields}
								relativeAppendPosition={relativeAppendPosition}
								innerExpressionSourceMap={innerExpressionSourceMap}
								allowSourceInjectBySelf={allowSourceInjectBySelf}
								contextExpressionSource={contextExpressionSource}
								uniqueFormId={uniqueFormId}
								displayColumns={displayColumns}
								showOperation={showOperation}
								columnNames={mergedColumnNames}
								customFieldsConfig={customFieldsConfig}
								onlyExpression={onlyExpression}
								showImport={showImport}
								showTopRow={showTopRow}
							>
								<ExportFieldsProvider defaultExportFields={contextVal.schema}>
									<SchemaMobxContext.Provider value={contextVal}>
										<Editor {...props} />
									</SchemaMobxContext.Provider>
								</ExportFieldsProvider>
							</GlobalProvider>
						</div>
					</ConfigProvider>
				</ErrorBoundary>
			)
		},
	),
)

export default JsonSchemaObserverEditor as React.ComponentType<JsonSchemaEditorProps>
