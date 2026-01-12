import { IconEdit } from "@douyinfe/semi-icons"
import { Col, Row, Select, Switch, Tooltip } from "antd"
import { IconCirclePlus } from "@tabler/icons-react"
import { useMemoizedFn } from "ahooks"
import i18next from "i18next"
import React, { useContext, useMemo } from "react"
import { useTranslation } from "react-i18next"
import resolveToString from "@/common/utils/template"
import { InputExpressionValue } from "@/DelightfulExpressionWidget/types"
import { SchemaMobxContext } from "@/DelightfulJsonSchemaEditor"
import useCols from "@/DelightfulJsonSchemaEditor/components/schema-json/hooks/useCols"
import useCustomConfig from "@/DelightfulJsonSchemaEditor/components/schema-json/hooks/useCustomConfig"
import usePropertiesLength from "@/DelightfulJsonSchemaEditor/components/schema-json/hooks/usePropertiesLength"
import useSelectOptions from "@/DelightfulJsonSchemaEditor/components/schema-json/hooks/useSelectOptions"
import useSvgLine from "@/DelightfulJsonSchemaEditor/components/schema-json/hooks/useSvgLine"
import { SchemaObjectWrap } from "@/DelightfulJsonSchemaEditor/components/schema-json/schema-object/index.style"
import SvgLine from "@/DelightfulJsonSchemaEditor/components/svgLine"
import { SchemaValueSplitor, ShowColumns } from "@/DelightfulJsonSchemaEditor/constants"
import { useExportFields } from "@/DelightfulJsonSchemaEditor/context/ExportFieldsContext/useExportFields"
import { useGlobal } from "@/DelightfulJsonSchemaEditor/context/GlobalContext/useGlobal"
import { DisabledField } from "@/DelightfulJsonSchemaEditor/types/Schema"
import DelightfulInput from "@/common/BaseUI/Input"
import DelightfulSelect from "@/common/BaseUI/Select"
import { DelightfulExpressionWidget } from "@/index"
import { TopRowWrapper } from "./style"

export type TopRowProps = {
	allowExpression: boolean
	lastObjectItemOffsetTop: number
	showEdit: Function
}
const { Option } = Select

export default function TopRow(props: TopRowProps) {
	const { displayColumns = [], columnNames, disableFields } = useGlobal()
	const { allowExpression, lastObjectItemOffsetTop, showEdit } = props
	const { LabelCol, TypeCol, ValueCol, DescCol } = useCols()
	const schemaMobx = useContext(SchemaMobxContext)
	const { exportFields } = useExportFields()
	const { t } = useTranslation()

	// Modify data type
	const handleChangeType = useMemoizedFn((key: string, value: string) => {
		schemaMobx.changeType({ keys: [key], value })
		exportFields.changeType({ keys: [key], value })
	})

	// Modify value
	const handleChangeValue = useMemoizedFn(
		(key: string[], value: string | InputExpressionValue) => {
			const changeValue: InputExpressionValue | string | boolean | { mock: string } = value
			schemaMobx.changeValue({ keys: key, value: changeValue })
			exportFields.changeValue({ keys: key, value: changeValue })
		},
	)

	const { selectOptions } = useSelectOptions()

	const { expressionSourceWithDefaultOptions, _allowOperation, _allowAdd } = useCustomConfig({
		value: schemaMobx.schema.value,
		name: "root",
		type: schemaMobx.schema.type,
	})

	// Add child node
	const handleAddChildField = useMemoizedFn((key: string) => {
		schemaMobx.addChildField({ keys: [key] })
		exportFields.addChildField({ keys: [key] })
	})

	const changeSwitch = useMemoizedFn((value: boolean) => {
		exportFields.requireAll({ required: value })
		schemaMobx.requireAll({ required: value })
	})

	const { propertiesLength } = usePropertiesLength({ prefix: ["properties"] })

	const childLength = Object.keys(schemaMobx.schema.properties || {}).length

	const value = schemaMobx.schema

	const { colSvgLineProps } = useSvgLine({
		lastSchemaOffsetTop: lastObjectItemOffsetTop,
		childLength: 0,
		propertiesLength,
		defaultY1: -1,
	})

	const selectValue = useMemo(() => {
		return value.items ? `${value.type}${SchemaValueSplitor}${value.items.type}` : value.type
	}, [value])

	return (
		<SchemaObjectWrap>
			<TopRowWrapper
				gutter={11}
				align="middle"
				className="json-schema-item"
				style={{ margin: 0, marginTop: "10px" }}
			>
				<Col span={22}>
					<Row align="middle" gutter={11} style={{ marginLeft: 0, gap: "10px" }}>
						{displayColumns.includes(ShowColumns.Key) && (
							<Col span={5}>
								<Row
									justify="space-around"
									align="middle"
									className="field-name key-col"
								>
									<Col flex="auto">
										<DelightfulInput
											disabled
											// @ts-ignore
											value={schemaMobx.schema?.key || "root"}
										/>
									</Col>
								</Row>
							</Col>
						)}

						{displayColumns.includes(ShowColumns.Label) && (
							<Col span={LabelCol} className="label-col ">
								<DelightfulInput
									placeholder={resolveToString(
										i18next.t("flow.pleaseInputSomething", { ns: "delightfulFlow" }),
										{
											name: columnNames[ShowColumns.Label],
										},
									)}
									value={schemaMobx.schema.title || "object"}
									onChange={(event: any) =>
										handleChangeValue(["title"], event.target.value)
									}
									disabled
								/>
							</Col>
						)}

						{displayColumns.includes(ShowColumns.Type) && (
							<Col span={TypeCol} className="type-col">
								<DelightfulSelect
									style={{ width: "100%" }}
									onChange={(value: string) => handleChangeType(`type`, value)}
									value={selectValue || "object"}
									disabled
								>
									{selectOptions.map((item) => {
										return (
											<Option value={item.value} key={item.value}>
												{item.label}
											</Option>
										)
									})}
								</DelightfulSelect>
							</Col>
						)}
						{displayColumns.includes(ShowColumns.Required) && (
							<Col span="50px" className="required-col">
								<Tooltip
									placement="top"
									title={i18next.t("common.selectAll", { ns: "delightfulFlow" })}
								>
									<Switch
										style={{ paddingRight: 0 }}
										checked={!!schemaMobx.schema.required}
										disabled={
											!(
												schemaMobx.schema.type === "object" ||
												schemaMobx.schema.type === "array"
											)
										}
										onChange={(val) => changeSwitch(val)}
									/>
								</Tooltip>
							</Col>
						)}

						{displayColumns.includes(ShowColumns.Value) && (
							<Col span={ValueCol} className="value-col">
								<DelightfulExpressionWidget
									value={schemaMobx.schema.value}
									onChange={(val: InputExpressionValue) =>
										handleChangeValue(["value"], val)
									}
									placeholder={resolveToString(
										i18next.t("flow.pleaseInputSomething", { ns: "delightfulFlow" }),
										{
											name: columnNames[ShowColumns.Value],
										},
									)}
									dataSource={expressionSourceWithDefaultOptions}
									allowExpression={allowExpression}
									allowModifyField
									multiple={false}
								/>
							</Col>
						)}

						{displayColumns.includes(ShowColumns.Description) && (
							<Col span={DescCol}>
								<DelightfulInput
									suffix={
										<IconEdit
											className="input-icon-editor"
											onClick={() =>
												showEdit(
													[],
													"description",
													schemaMobx.schema.description,
												)
											}
										/>
									}
									placeholder={i18next.t("common.descPlaceholder", {
										ns: "delightfulFlow",
									})}
									value={value.description}
									onChange={(event: any) =>
										handleChangeValue(["description"], event.target.value)
									}
									disabled={disableFields.includes(DisabledField.Description)}
								/>
							</Col>
						)}
					</Row>
				</Col>

				<Col flex="70px" className="json-schema-operator">
					{_allowOperation && (
						<>
							{_allowAdd && (
								<span
									className="add"
									onClick={(e) => {
										e.stopPropagation()
										handleAddChildField("properties")
									}}
								>
									<Tooltip
										placement="top"
										title={i18next.t("jsonSchema.addSubField", {
											ns: "delightfulFlow",
										})}
									>
										<IconCirclePlus stroke={1} size={20} color="#315CEC" />
									</Tooltip>
								</span>
							)}
						</>
					)}
				</Col>
			</TopRowWrapper>
			{childLength > 0 && <SvgLine {...colSvgLineProps} className="col-line" />}
		</SchemaObjectWrap>
	)
}

