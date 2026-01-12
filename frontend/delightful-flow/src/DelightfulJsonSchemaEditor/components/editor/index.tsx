import { FormItemType, InputExpressionValue } from "@/DelightfulExpressionWidget/types"
import { copyToClipboard } from "@/DelightfulFlow/utils"
import { useExportFields } from "@/DelightfulJsonSchemaEditor/context/ExportFieldsContext/useExportFields"
import { DataSourceOption } from "@/common/BaseUI/DropdownRenderer/Reference"
import { Button, Checkbox, Col, Flex, Input, Modal, Row, Tabs, Tooltip, message } from "antd"
import { IconFileExport, IconPlus, IconSelect } from "@tabler/icons-react"
import { useMemoizedFn } from "ahooks"
import i18next from "i18next"
import _ from "lodash"
import { observer } from "mobx-react"
import React, { ReactElement, createContext, useContext, useState } from "react"
import { useTranslation } from "react-i18next"
import { AppendPosition, ONLY_JSON_ROOT, SCHEMA_TYPE, ShowColumns } from "../../constants"
import { useGlobal } from "../../context/GlobalContext/useGlobal"
import { SchemaMobxContext } from "../../index"
import Schema, { CustomOptions } from "../../types/Schema"
import { convertJsonToSchema, handleSchema, isSchemaFormat } from "../../utils/SchemaUtils"
import { cleanAndFilterArray, unFocusSchemaValue } from "../../utils/helpers"
import QuietEditor from "../quiet-editor"
import SchemaJson from "../schema-json"
import useCols from "../schema-json/hooks/useCols"
import SchemaOther from "../schema-other"
import IconImport, { ImportType } from "./components/IconImport"
import TopRow from "./components/TopRow"
import { createSchema } from "./genson-js"
import { JsonSchemaEditorWrap } from "./style"

interface EditorContextProp {
	changeCustomValue: (newValue: Schema) => void
	mock?: boolean
	customOptions?: CustomOptions
	allowExpression?: boolean
	expressionSource?: DataSourceOption[]
}

export const EditorContext = createContext<EditorContextProp>({
	changeCustomValue: () => {},
	mock: false,
	customOptions: {
		root: [] as FormItemType[],
		items: [] as FormItemType[],
		normal: [] as FormItemType[],
	},
	allowExpression: false,
	expressionSource: [],
})

interface EditorProp {
	jsonEditor?: boolean
	mock?: boolean
	allowExpression?: boolean
	onlyJson?: boolean
	customOptions?: CustomOptions
	expressionSource?: DataSourceOption[]
	jsonImport?: boolean
	debuggerMode?: boolean
	valueEdit?: boolean
	onBlur?: (schema: Schema) => void
}

const Editor = observer(
	({
		onlyJson = true,
		customOptions = {},
		expressionSource = [],
		allowExpression = false,
		jsonImport = false,
		debuggerMode = false,
		valueEdit = false,
		onBlur = () => {},
		...props
	}: EditorProp): ReactElement => {
		const { t } = useTranslation()
		const {
			displayColumns = [],
			columnNames,
			showImport,
			showTopRow,
			showAdd,
			showOperation,
		} = useGlobal()
		const schemaMobx = useContext(SchemaMobxContext)

		const { exportFields, showExportCheckbox, setShowExportCheckbox } = useExportFields()

		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		const [stateVal, setStateVal] = useState<Record<string, any>>({
			visible: false,
			show: true,
			editVisible: false,
			description: "",
			descriptionKey: null,
			advVisible: false,
			itemKey: [],
			curItemCustomValue: null,
			checked: true,
			editorModalName: "", // Modal name: description | mock
			mock: "",
		})

		const { LabelCol, TypeCol, ValueCol, DescCol } = useCols()

		const rootOptions = cleanAndFilterArray(customOptions.root as string[])

		const SELECT_OPTIONS =
			(rootOptions as string[]).length > 0
				? rootOptions
				: onlyJson
				? ONLY_JSON_ROOT
				: SCHEMA_TYPE

		const [jsonSchemaData, setJsonSchemaData] = useState<string>()
		const [jsonData, setJsonData] = useState<string | undefined>()
		const [importJsonType, setImportJsonType] = useState<string | null>(null)

		// JSON import modal
		const showModal = () => {
			setStateVal((prevState) => {
				return { ...prevState, visible: true }
			})
		}

		const handleOk = () => {
			if (importJsonType !== "schema") {
				if (!jsonData) {
					return
				}
				let jsonObject = null
				try {
					jsonObject = JSON.parse(jsonData)
				} catch (ex) {
					message.error("Invalid JSON format").then(() => {})
					return
				}
				// eslint-disable-next-line @typescript-eslint/no-explicit-any
				const jsonDataVal: any = { ...createSchema(jsonObject) }
				schemaMobx.changeSchema(jsonDataVal)
			} else {
				if (!jsonSchemaData) {
					return
				}
				let jsonObject = null
				try {
					jsonObject = JSON.parse(jsonSchemaData)
				} catch (ex) {
					message.error("Invalid JSON format").then(() => {})
					return
				}
				schemaMobx.changeSchema(jsonObject)
			}
			setStateVal((prevState) => {
				return { ...prevState, visible: false }
			})
		}

		const handleCancel = () => {
			setStateVal((prevState) => {
				return { ...prevState, visible: false }
			})
		}

		// Data coming from EditorComponent
		const handleParams = (value: string | undefined) => {
			if (!value) return
			let parseData = JSON.parse(value)
			parseData = handleSchema(parseData)
			schemaMobx.changeSchema(parseData)
		}

		// Update data type
		const handleChangeType = (key: string, value: string) => {
			schemaMobx.changeType({ keys: [key], value })
		}

		const handleImportJson = (value: string | undefined) => {
			if (!value) {
				setJsonData(undefined)
			} else {
				setJsonData(value)
			}
		}

		const handleImportJsonSchema = (value: string | undefined) => {
			if (!value) {
				setJsonSchemaData(undefined)
			} else {
				setJsonSchemaData(value)
			}
		}

		// Add child node
		const handleAddChildField = (key: string) => {
			schemaMobx.addChildField({ keys: [key] })
			setStateVal((prevState) => {
				return { ...prevState, show: true }
			})
		}

		const clickIcon = () => {
			setStateVal((prevState) => {
				return { ...prevState, show: !prevState.show }
			})
		}

		// Modify remark information
		const handleChangeValue = (e: any, key: string[], value: string | InputExpressionValue) => {
			let changeValue: InputExpressionValue | string | boolean | { mock: string } = value
			if (key[0] === "mock" && value) {
				changeValue = { mock: value as string }
			}
			schemaMobx.changeValue({ keys: key, value: changeValue })
		}

		// When clicking ok in remark/mock popup
		const handleEditOk = (name: string) => {
			setStateVal((prevState) => {
				return { ...prevState, editVisible: false }
			})
			let value = stateVal[name]
			if (name === "mock") {
				value = value ? { mock: value } : ""
			}
			schemaMobx.changeValue({ keys: stateVal.descriptionKey, value })
		}

		const handleEditCancel = () => {
			setStateVal((prevState) => {
				return { ...prevState, editVisible: false }
			})
		}

		/**
		 * Show modal popup
		 * prefix: node prefix information
		 * name: popup name ['description', 'mock']
		 * value: input value
		 * type: if current field is object || array showEdit is disabled
		 */
		const showEdit = (
			prefix: string[],
			name: string,
			value?: string | { mock: string },
			type?: string,
		) => {
			if (type === "object" || type === "array") {
				return
			}
			const descriptionKey = [...prefix].concat(name)
			let inputValue = value
			if (typeof value !== "string") {
				inputValue = name === "mock" ? (value ? value.mock : "") : value
			}
			setStateVal((prevState) => {
				return {
					...prevState,
					editVisible: true,
					[name]: inputValue,
					descriptionKey,
					editorModalName: name,
				}
			})
		}

		// Modify remark/mock parameter information
		const changeDesc = (value: string, name: string) => {
			setStateVal((prevState) => {
				return { ...prevState, [name]: value }
			})
		}

		// Advanced settings
		const handleAdvOk = () => {
			if (stateVal.itemKey.length === 0) {
				schemaMobx.changeSchema(stateVal.curItemCustomValue)
			} else {
				schemaMobx.changeValue({
					keys: stateVal.itemKey,
					value: stateVal.curItemCustomValue,
				})
			}
			setStateVal((prevState) => {
				return { ...prevState, advVisible: false }
			})
		}

		const handleAdvCancel = () => {
			setStateVal((prevState) => {
				return { ...prevState, advVisible: false }
			})
		}

		const showAdv = (key: string[], value?: Schema) => {
			setStateVal((prevState) => {
				return {
					...prevState,
					advVisible: true,
					itemKey: key,
					curItemCustomValue: value, // Current node data information
				}
			})
		}

		const tabItems = [
			{
				label: "json",
				key: "json",
				children: <QuietEditor height={300} language="json" onChange={handleImportJson} />,
			},
			{
				label: "schema",
				key: "schema",
				children: (
					<QuietEditor height={300} language="json" onChange={handleImportJsonSchema} />
				),
			},
		]

		//  Modify json-schema value in popup
		const changeCustomValue = (newValue: Schema) => {
			setStateVal((prevState) => {
				return { ...prevState, curItemCustomValue: newValue }
			})
		}

		const changeSwitch = (value: boolean) => {
			setStateVal((prevState) => {
				return { ...prevState, checked: value }
			})
			schemaMobx.requireAll({ required: value })
		}

		const showSchema = () => {
			console.log(JSON.parse(JSON.stringify(schemaMobx)))
		}

		const { visible, editVisible, advVisible, checked, editorModalName } = stateVal

		//   function handleMockSelectShowEdit() {
		//     showEdit([], 'mock', schemaMobx.schema.mock, schemaMobx.schema.type);
		//   }

		const handleBlur = () => {
			onBlur(JSON.parse(JSON.stringify(schemaMobx.schema)))
		}

		//  Add child node
		const handleAddField = useMemoizedFn(() => {
			schemaMobx.addChildField({
				keys: ["properties"],
			})
		})

		const showExportFieldsSelections = useMemoizedFn(() => {
			exportFields.changeSchema(schemaMobx.schema)
			setShowExportCheckbox(!showExportCheckbox)
		})

		const handleCopy = useMemoizedFn(() => {
			const cloneSchema = _.cloneDeep(exportFields.schema)
			unFocusSchemaValue(cloneSchema)
			console.log("schema", JSON.parse(JSON.stringify(cloneSchema)))
			copyToClipboard(JSON.stringify(cloneSchema))
			message.success(i18next.t("common.exportSuccess", { ns: "delightfulFlow" }))
			setShowExportCheckbox(!showExportCheckbox)
		})

		const onImport = useMemoizedFn((importForm) => {
			try {
				const { content, fieldKeys = [] } = importForm

				// Try to parse JSON content
				const jsonContent = JSON.parse(content)

				// Determine if it's Schema format or plain JSON
				const schema = isSchemaFormat(jsonContent)
					? jsonContent
					: convertJsonToSchema(jsonContent)

				const keys = fieldKeys.reduce(
					(acc: string[], curKey: string, index: number) => {
						if (index !== fieldKeys.length - 1) {
							acc.push(...[curKey, "properties"])
						}
						return acc
					},
					["properties"] as string[],
				)

				switch (importForm.type) {
					case ImportType.Push:
						schemaMobx.insertFields({
							fields: schema,
							keys: ["properties"],
							position: AppendPosition.Tail,
						})
						break
					case ImportType.Insert:
						schemaMobx.insertFields({
							fields: schema,
							keys: keys,
							position: AppendPosition.Next,
							name: fieldKeys?.[fieldKeys?.length - 1],
						})
						break
					default:
						schemaMobx.changeSchema(schema)
				}
				message.success(i18next.t("common.importSuccess", { ns: "delightfulFlow" }))

				return true
			} catch (err) {
				console.log("importerror", err)

				return false
			}
		})

		const [lastObjectItemOffsetTop, setObjectLastItemOffsetTop] = useState(0)

		return (
			<EditorContext.Provider
				value={{
					changeCustomValue,
					mock: props.mock,
					customOptions,
					allowExpression: allowExpression,
					expressionSource: expressionSource,
				}}
			>
				<div className="json-schema-react-editor" onBlur={handleBlur}>
					{jsonImport && (
						<Button type="primary" onClick={showModal}>
							import_json
						</Button>
					)}
					{debuggerMode && (
						<Button type="primary" onClick={showSchema} style={{ marginLeft: "10px" }}>
							Show Result
						</Button>
					)}
					<Modal
						width={750}
						maskClosable={false}
						open={visible}
						title="import_json"
						onOk={handleOk}
						onCancel={handleCancel}
						className="json-schema-react-editor-import-modal"
						okText="ok"
						cancelText="cancel"
						footer={[
							<Button key="back" onClick={handleCancel}>
								cancel
							</Button>,
							<Button key="submit" type="primary" onClick={handleOk}>
								ok
							</Button>,
						]}
					>
						<Tabs
							defaultActiveKey="json"
							onChange={(key) => {
								setImportJsonType(key)
							}}
							items={tabItems}
						/>
					</Modal>

					<Modal
						title={
							<div>{i18next.t("jsonSchema.setDesc", { ns: "delightfulFlow" })} &nbsp;</div>
						}
						width={750}
						maskClosable={false}
						open={editVisible}
						onOk={() => handleEditOk(editorModalName)}
						onCancel={handleEditCancel}
						okText={i18next.t("common.confirm", { ns: "delightfulFlow" })}
						cancelText={i18next.t("common.cancel", { ns: "delightfulFlow" })}
					>
						<Input.TextArea
							value={stateVal[editorModalName]}
							placeholder={i18next.t("common.descPlaceholder", { ns: "delightfulFlow" })}
							onChange={(event) => changeDesc(event.target.value, editorModalName)}
							autoSize={{ minRows: 6, maxRows: 10 }}
						/>
					</Modal>

					{advVisible && (
						<Modal
							title="adv_setting"
							width={750}
							maskClosable={false}
							open={advVisible}
							onOk={handleAdvOk}
							onCancel={handleAdvCancel}
							okText="ok"
							cancelText="cancel"
							className="json-schema-react-editor-adv-modal"
						>
							<SchemaOther
								data={JSON.stringify(stateVal.curItemCustomValue, null, 2)}
							/>
						</Modal>
					)}
					<JsonSchemaEditorWrap style={{ marginTop: 10 }}>
						<Row>
							{props.jsonEditor && (
								<Col span={8}>
									<QuietEditor
										height={500}
										value={JSON.stringify(schemaMobx.schema, null, 2)}
										language="json"
										onChange={handleParams}
									/>
								</Col>
							)}
							<Col span={props.jsonEditor ? 16 : 24} className="wrapper">
								<Row align="middle" gutter={11}>
									<Col span={22}>
										<Row
											align="middle"
											gutter={11}
											className="json-schema-header"
											style={{ marginLeft: 0 }}
										>
											{showExportCheckbox && (
												<Checkbox className="json-schema-field-check" />
											)}
											{displayColumns.includes(ShowColumns.Key) && (
												<Col span={5} className="key-col">
													<Row
														justify="space-around"
														align="middle"
														className="field-name"
													>
														<Col flex="auto">
															{columnNames[ShowColumns.Key]}
														</Col>
													</Row>
												</Col>
											)}
											{displayColumns.includes(ShowColumns.Label) && (
												<Col span={LabelCol} className="label-col">
													{columnNames[ShowColumns.Label]}
												</Col>
											)}
											{displayColumns.includes(ShowColumns.Type) && (
												<Col span={TypeCol} className="type-col">
													{columnNames[ShowColumns.Type]}
												</Col>
											)}
											{displayColumns.includes(ShowColumns.Required) && (
												<Col flex="50px">
													{columnNames[ShowColumns.Required]}
												</Col>
											)}
											{displayColumns.includes(ShowColumns.Encryption) && (
												<Col flex="50px">
													{columnNames[ShowColumns.Encryption]}
												</Col>
											)}

											{displayColumns.includes(ShowColumns.Value) && (
												<Col span={ValueCol} className="value-col">
													{columnNames[ShowColumns.Value]}
												</Col>
											)}
											{displayColumns.includes(ShowColumns.Description) && (
												<Col span={DescCol} className="desc-col">
													{columnNames[ShowColumns.Description]}
												</Col>
											)}
										</Row>
									</Col>
									{showOperation && (
										<Col
											flex="70px"
											className="json-schema-header"
											style={{ marginRight: "5.5px" }}
										>
											<Flex align="center" gap={2}>
												{showImport && <IconImport onImport={onImport} />}
												{!showExportCheckbox && (
													<Tooltip
														title={i18next.t(
															"jsonSchema.selectExportFields",
															{
																ns: "delightfulFlow",
															},
														)}
													>
														<span
															className="add-icon"
															onClick={showExportFieldsSelections}
														>
															<IconSelect
																size={16}
																stroke={1}
																color="#00BF9A"
															/>
														</span>
													</Tooltip>
												)}

												{showExportCheckbox && (
													<Tooltip
														title={i18next.t("common.export", {
															ns: "delightfulFlow",
														})}
													>
														<span
															className="add-icon"
															onClick={handleCopy}
														>
															<IconFileExport
																size={16}
																stroke={1}
																color="#00BF9A"
															/>
														</span>
													</Tooltip>
												)}
											</Flex>
										</Col>
									)}
								</Row>
								{showTopRow && (
									<TopRow
										showEdit={showEdit}
										allowExpression={allowExpression}
										lastObjectItemOffsetTop={lastObjectItemOffsetTop}
									/>
								)}

								{stateVal.show && (
									<SchemaJson
										showEdit={showEdit}
										showAdv={showAdv}
										showExtraLine={false}
										setObjectLastItemOffsetTop={setObjectLastItemOffsetTop}
									/>
								)}
							</Col>
							{showAdd && (
								<span className="add-row" onClick={handleAddField}>
									<IconPlus size={16} stroke={2} color="#1C1D23CC" />
									{i18next.t("common.addArguments", { ns: "delightfulFlow" })}
								</span>
							)}
						</Row>
					</JsonSchemaEditorWrap>
				</div>
			</EditorContext.Provider>
		)
	},
)

export default Editor

