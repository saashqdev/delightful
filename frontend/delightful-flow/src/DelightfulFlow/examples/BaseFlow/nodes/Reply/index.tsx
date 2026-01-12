import { ExpressionMode } from "@/DelightfulExpressionWidget/constant"
import { useFlow } from "@/DelightfulFlow/context/FlowContext/useFlow"
import { useCurrentNode } from "@/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import BaseDropdownRenderer from "@/common/BaseUI/DropdownRenderer/Base"
import TsSelect from "@/common/BaseUI/Select"
import { Form } from "antd"
import { DefaultOptionType } from "antd/lib/select"
import { IconPhoto, IconTextSize } from "@tabler/icons-react"
import { useMemoizedFn } from "ahooks"
import _ from "lodash"
import React, { ReactNode, useMemo } from "react"
import DelightfulExpression from "../../common/Expression"
import usePrevious from "../../common/hooks/usePrevious"
import { MessageType } from "./constants"
import useReply from "./hooks/useReply"
import styles from "./index.module.less"

type OptionType = DefaultOptionType & { icon: ReactNode }

export default function Reply() {
	const { updateNodeConfig, nodeConfig } = useFlow()

	const { currentNode } = useCurrentNode()

	const [form] = Form.useForm()
	const messageTypeOptions = useMemo(() => {
		return [
			{
				label: (
					<div className={styles.label}>
						<IconTextSize color="#000000" stroke={1} className={styles.icon} />
						<span>Text</span>
					</div>
				),
				realLabel: "Text",
				value: MessageType.Text,
			},
			{
				label: (
					<div className={styles.label}>
						<IconPhoto color="#3A57D1" stroke={1} className={styles.icon} />
						<span>Image</span>
					</div>
				),
				realLabel: "Image",
				value: MessageType.Image,
			},
		]
	}, [])

	const formValues = form.getFieldsValue()

	const { replyTemplate } = useReply()

	const { expressionDataSource } = usePrevious()

	const onValuesChange = useMemoizedFn((changeValues) => {
		if (!currentNode || !nodeConfig || !nodeConfig[currentNode?.node_id]) return
		const currentNodeConfig = nodeConfig[currentNode?.node_id]

		Object.entries(changeValues).forEach(([changeKey, changeValue]) => {
			_.set(currentNodeConfig, ["params", changeKey], changeValue)
		})

		updateNodeConfig({
			...currentNodeConfig,
		})
	})

	const initialValues = useMemo(() => {
		return currentNode?.params
	}, [currentNode?.params])

	return (
		<div className={styles.replyWrapper}>
			<Form
				form={form}
				layout="vertical"
				onValuesChange={onValuesChange}
				initialValues={initialValues}
			>
				<Form.Item name="type" label="Message Card Type">
					<TsSelect
						options={messageTypeOptions}
						dropdownRenderProps={{
							placeholder: "Search card type",
							component: BaseDropdownRenderer,
						}}
						className={styles.messageTypeSelect}
						placeholder="Please select"
					/>
				</Form.Item>
				{formValues.type === MessageType.Text && (
					<DelightfulExpression
						name="content"
						label="Message Content"
						mode={ExpressionMode.TextArea}
						placeholder="Plain text message content, use '$ + space' to add variables"
						dataSource={expressionDataSource}
					/>
				)}
				{formValues.type === MessageType.Image && (
					<>
						<DelightfulExpression
							name="link"
							label="Image Link"
							mode={ExpressionMode.TextArea}
							placeholder="Enter image link, use '$ + space' to add variables"
							minHeight="100%"
							dataSource={expressionDataSource}
						/>
						<DelightfulExpression
							name="link_desc"
							label="Description Text"
							mode={ExpressionMode.TextArea}
							placeholder="Enter description text, use '$ + space' to add variables"
							minHeight="100%"
							dataSource={expressionDataSource}
						/>
					</>
				)}
			</Form>
		</div>
	)
}

