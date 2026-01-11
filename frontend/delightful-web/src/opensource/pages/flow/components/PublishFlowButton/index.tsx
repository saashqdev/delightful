import { Button, Form, message } from "antd"
import type { DelightfulFlowInstance } from "@bedelightful/delightful-flow/dist/DelightfulFlow"
import { useBoolean, useMemoizedFn, useUpdateEffect } from "ahooks"
import { useMemo, type MutableRefObject } from "react"
import type { DelightfulFlow } from "@bedelightful/delightful-flow/dist/DelightfulFlow/types/flow"
import { useFlowStore } from "@/opensource/stores/flow"
import DelightfulInput from "@bedelightful/delightful-flow/dist/common/BaseUI/Input"
import antdStyles from "@/opensource/pages/flow/index.module.less"
import { useTranslation } from "react-i18next"
import DelightfulModal from "@/opensource/components/base/DelightfulModal"
import { FlowApi } from "@/apis"
import styles from "./index.module.less"
import { useCustomFlow } from "../../context/CustomFlowContext/useCustomFlow"
import { shadowFlow } from "../../utils/helpers"

type PublishFlowButtonProps = {
	flowInstance?: MutableRefObject<DelightfulFlowInstance | null>
	flow?: DelightfulFlow.Flow
	Icon?: any
	isMainFlow: boolean
	initPublishList?: (this: any, flowCode: any) => Promise<void>
}

export default function PublishFlowButton({
	flowInstance,
	flow,
	Icon,
	isMainFlow,
	initPublishList,
}: PublishFlowButtonProps) {
	const { t } = useTranslation("interface")
	const { t: globalT } = useTranslation()
	const [form] = Form.useForm()

	const { setCurrentFlow } = useCustomFlow()

	const [open, { setTrue, setFalse }] = useBoolean(false)

	const { publishList } = useFlowStore()

	const publishFlow = useMemoizedFn(async (formValues: any) => {
		const latestFlow = flowInstance?.current?.getFlow()
		if (!latestFlow) return
		const shadowedFlow = shadowFlow(latestFlow)

		const flowId = latestFlow?.id ?? ""

		const requestParams = {
			name: formValues.name,
			description: formValues.description,
			delightful_flow: {
				...shadowedFlow,
				// @ts-ignore
				global_variable: flow?.global_variable,
			},
		}
		const publishDetail = await FlowApi.publishFlow(requestParams, flowId)
		message.success(globalT("common.savedSuccess", { ns: "flow" }))
		setFalse()
		// @ts-ignore
		setCurrentFlow({
			...latestFlow,
			version_code: publishDetail.id,
		})
		initPublishList?.(flowId)
	})

	const handleOk = useMemoizedFn(async () => {
		try {
			if (isMainFlow) {
				publishFlow({
					name: flow?.name,
					description: "",
				})
			} else {
				await form.validateFields()
				const values = form.getFieldsValue()
				publishFlow(values)
			}
		} catch (err_1) {
			console.error("form validate error: ", err_1)
		}
	})

	const handleCancel = useMemoizedFn(() => {
		setFalse()
	})

	const initialValues = useMemo(() => {
		return {
			name: `${flow?.name}_version${publishList.length + 1}`,
			description: "",
		}
	}, [publishList.length, flow?.name])

	useUpdateEffect(() => {
		form.setFieldsValue({
			name: `${flow?.name}_version${publishList.length + 1}`,
		})
	}, [publishList.length, flow])

	return (
		<>
			{!Icon && (
				<Button
					type="primary"
					onClick={isMainFlow ? handleOk : setTrue}
					className={styles.btn}
				>
					{t("button.publish")}
				</Button>
			)}
			{Icon && <Icon onClick={isMainFlow ? handleOk : setTrue} />}

			<DelightfulModal
				className={antdStyles.antdModal}
				title="Fill in version information"
				open={open}
				onOk={handleOk}
				onCancel={handleCancel}
			>
				<Form form={form} className={styles.form} initialValues={initialValues}>
					<Form.Item name="name" label="Version name">
						<DelightfulInput />
					</Form.Item>
					<Form.Item name="description" label="Version description">
						<DelightfulInput.TextArea />
					</Form.Item>
				</Form>
			</DelightfulModal>
		</>
	)
}





