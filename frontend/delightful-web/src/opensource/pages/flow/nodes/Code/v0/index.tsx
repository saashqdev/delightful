import DropdownCard from "@bedelightful/delightful-flow/dist/common/BaseUI/DropdownCard"
import { ShowColumns } from "@bedelightful/delightful-flow/dist/DelightfulJsonSchemaEditor/constants"
import { useMemo, useRef, useState } from "react"
import { useCurrentNode } from "@bedelightful/delightful-flow/dist/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import Editor from "@monaco-editor/react"
import { useMemoizedFn, useUpdateEffect } from "ahooks"
import { cloneDeep, set } from "lodash-es"
import { Form, Modal } from "antd"
import DelightfulJSONSchemaEditorWrap from "@bedelightful/delightful-flow/dist/common/BaseUI/DelightfulJsonSchemaEditorWrap"
import {
	useFlowData,
	useNodeConfigActions,
} from "@bedelightful/delightful-flow/dist/DelightfulFlow/context/FlowContext/useFlow"
import DelightfulExpressionWrap from "@bedelightful/delightful-flow/dist/common/BaseUI/DelightfulExpressionWrap"
import { ExpressionMode } from "@bedelightful/delightful-flow/dist/DelightfulExpressionWidget/constant"
import { useTranslation } from "react-i18next"
import styles from "./index.module.less"
import usePrevious from "../../../common/hooks/usePrevious"
import LanguageSelect from "./components/LanguageSelect/LanguageSelect"
import useMode, { CodeMode } from "./hooks/useMode"
import useCode from "./hooks/useCode"
import { v0Template } from "./template"

export default function Code() {
	const { t } = useTranslation()
	const [form] = Form.useForm()
	const { currentNode } = useCurrentNode()
	const { expressionDataSource } = usePrevious()
	const { flow } = useFlowData()

	const [codeString, setCodeString] = useState("")

	const { updateNodeConfig } = useNodeConfigActions()

	const { ModeChanger } = useMode()

	const { checkIsDefaultCode, updateCode } = useCode({ form })

	const initialValues = useMemo(() => {
		const currentNodeParams = currentNode?.params || {}
		const cloneTemplateParams = cloneDeep(v0Template.params)
		const mergeParams = {
			...cloneTemplateParams,
			...currentNodeParams,
		}
		// Convert to boolean for form echo display
		// @ts-ignore
		mergeParams.mode = mergeParams.mode === CodeMode.Normal
		return {
			...mergeParams,
			input: currentNode?.input?.form,
			output: currentNode?.output?.form,
		}
	}, [currentNode?.input, currentNode?.output, currentNode?.params])

	const onValuesChange = useMemoizedFn((changeValues) => {
		if (!currentNode) return
		Object.entries(changeValues).forEach(([changeKey, changeValue]) => {
			if (changeKey === "input" || changeKey === "output") {
				set(currentNode, [changeKey, "form"], changeValue)
				// Convert boolean to actual value
			} else if (changeKey === "mode") {
				set(
					currentNode,
					["params", changeKey],
					changeValue ? CodeMode.Normal : CodeMode.Expression,
				)
			} else {
				if (changeKey === "language") {
					if (checkIsDefaultCode()) {
						updateCode(changeValue as string)
					} else {
						Modal.confirm({
							title: t("code.checkToUseTemplate", { ns: "flow" }),
							content: t("code.useTemplateDesc", { ns: "flow" }),
							onOk: () => {
								updateCode(changeValue as string)
							},
						})
					}
				}
				set(currentNode, ["params", changeKey], changeValue)
			}
		})
		updateNodeConfig({ ...currentNode })
	})

	useUpdateEffect(() => {
		form.setFieldsValue({
			...currentNode?.params,
			input: currentNode?.input?.form,
			output: currentNode?.output?.form,
			mode: currentNode?.params?.mode === CodeMode.Normal,
		})
	}, [flow, currentNode])

	const [isEditorFocused, setIsEditorFocused] = useState(false)
	const editorRef = useRef()

	// Set focus event listener when editor is mounted
	const handleEditorMount = (editor: any) => {
		editorRef.current = editor

		editor.onDidFocusEditorWidget(() => setIsEditorFocused(true))
		editor.onDidBlurEditorWidget(() => setIsEditorFocused(false))
	}

	// Focus editor when overlay is clicked
	const handleOverlayClick = () => {
		// @ts-ignore
		editorRef.current?.focus?.()
	}

	return (
		<Form
			form={form}
			className={styles.code}
			initialValues={initialValues}
			onValuesChange={onValuesChange}
		>
			<div className={styles.input}>
				<DropdownCard title={t("common.input", { ns: "flow" })} height="auto">
					<Form.Item name="input">
						<DelightfulJSONSchemaEditorWrap
							allowExpression
							expressionSource={expressionDataSource}
							displayColumns={[ShowColumns.Key, ShowColumns.Value, ShowColumns.Type]}
						/>
					</Form.Item>
				</DropdownCard>
			</div>
			<div className={styles.codeEditor}>
				<DropdownCard
					title={t("common.code", { ns: "flow" })}
					height="auto"
					suffixIcon={ModeChanger}
				>
					<LanguageSelect />
					{(!currentNode?.params?.mode ||
						currentNode?.params?.mode === CodeMode.Normal) && (
						<>
							{/* Transparent overlay layer that intercepts events when unfocused */}
							{!isEditorFocused && (
								<div
									style={{
										position: "absolute",
										top: 0,
										left: 0,
										right: 0,
										bottom: 0,
										zIndex: 10,
										cursor: "grab",
									}}
									onClick={handleOverlayClick}
									onMouseDown={(e) => e.stopPropagation()} // Prevent node dragging
								/>
							)}
							<Form.Item name="code">
								<Editor
									theme="vs-dark"
									language="php"
									value={codeString}
									height="400px"
									onChange={(value) => setCodeString(value!)}
									className="nodrag"
									onMount={handleEditorMount}
									options={{
										// Adjust editor interaction based on focus state
										readOnly: !isEditorFocused,
										contextmenu: isEditorFocused,
									}}
								/>
							</Form.Item>
						</>
					)}
					{currentNode?.params?.mode === CodeMode.Expression && (
						<Form.Item name={["import_code"]}>
							<DelightfulExpressionWrap
								onlyExpression
								dataSource={expressionDataSource}
								mode={ExpressionMode.TextArea}
								placeholder={t("common.allowExpressionPlaceholder", { ns: "flow" })}
							/>
						</Form.Item>
					)}
				</DropdownCard>
			</div>

			<div className={styles.output}>
				<DropdownCard title={t("common.output", { ns: "flow" })} height="auto">
					<Form.Item name="output">
						<DelightfulJSONSchemaEditorWrap
							allowExpression
							expressionSource={expressionDataSource}
							displayColumns={[ShowColumns.Key, ShowColumns.Label, ShowColumns.Type]}
						/>
					</Form.Item>
				</DropdownCard>
			</div>
		</Form>
	)
}
