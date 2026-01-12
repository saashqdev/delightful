import { useNodeConfigActions } from "@/DelightfulFlow/context/FlowContext/useFlow"
import { useCurrentNode } from "@/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import DropdownCard from "@/common/BaseUI/DropdownCard"
import { useMemoizedFn } from "ahooks"
import { Form } from "antd"
import _ from "lodash"
import React from "react"
import DelightfulExpression from "../../../common/Expression"
import Output from "../../../common/Output"
import useCurrentNodeUpdate from "../../../common/hooks/useCurrentNodeUpdate"
import usePrevious from "../../../common/hooks/usePrevious"
import LLMParameters from "./components/LLMParameters"
import useLLM from "./hooks/useLLM"
import styles from "./index.module.less"

export default function LLMV0() {
	const [form] = Form.useForm()
	const { updateNodeConfig } = useNodeConfigActions()

	const { currentNode } = useCurrentNode()

	const { LLMOptions, LLMValue, onLLMValueChange, initialValues } = useLLM({ form })

	const { expressionDataSource } = usePrevious()

	const onValuesChange = useMemoizedFn((changeValues) => {
		if (!currentNode) return

		// Special handling for llm field
		if (changeValues.llm) {
			const { model, ...rest } = changeValues.llm
			_.set(currentNode, ["params", "model"], model)
			if (rest && Object.keys(rest).length)
				_.set(currentNode, ["params", "model_config"], rest)
		} else {
			Object.entries(changeValues).forEach(([changeKey, changeValue]) => {
				_.set(currentNode, ["params", changeKey], changeValue)
			})
		}

		updateNodeConfig({ ...currentNode })
	})

	console.log("getFieldsValue", form.getFieldsValue(), initialValues)

	useCurrentNodeUpdate({
		form,
	})

	return (
		<div className={styles.llm}>
			<Form
				form={form}
				layout="vertical"
				onValuesChange={onValuesChange}
				initialValues={initialValues}
			>
				<Form.Item name="llm" label="Model" className={styles.formItem}>
					<LLMParameters
						LLMValue={LLMValue}
						onChange={onLLMValueChange}
						options={LLMOptions}
					/>
					{/* <LLMSelect value={inputValue} onChange={onChange} options={LLMOptions} /> */}
				</Form.Item>
				<div className={styles.inputHeader}>Input</div>
				<div className={styles.inputBody}>
					<DropdownCard
						title="Prompt"
						headerClassWrapper={styles.promptWrapper}
						height="auto"
					>
						<DelightfulExpression
							label="System"
							name="system_prompt"
							placeholder="Fixed guiding words for large models. Adjust content to guide chat direction. Prompt content is fixed at the beginning of context. Use '@' to add variables"
							dataSource={expressionDataSource}
							showExpand
						/>

						<DelightfulExpression
							label="User"
							name="user_prompt"
							placeholder="Fixed guiding words for large models. Adjust content to guide chat direction. Prompt content is fixed at the beginning of context. Use '@' to add variables"
							className={styles.LLMInput}
							dataSource={expressionDataSource}
						/>
					</DropdownCard>
				</div>
				{/* @ts-ignore  */}
				<Output value={currentNode?.output} />
			</Form>
		</div>
	)
}

