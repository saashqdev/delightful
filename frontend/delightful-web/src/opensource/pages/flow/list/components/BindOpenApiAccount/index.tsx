import { useTranslation } from "react-i18next"
import { Flex, Form, Image, Select, Tooltip } from "antd"
import { useAsyncEffect, useMemoizedFn, useUpdateEffect } from "ahooks"
import { useForm } from "antd/es/form/Form"
import { mutate } from "swr"
import { RequestUrl } from "@/opensource/apis/constant"
import type { PlatformItem } from "@/types/flow"
import { IconX } from "@tabler/icons-react"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import DelightfulModal from "@/opensource/components/base/DelightfulModal"
import { useState } from "react"
import type { DefaultOptionType } from "antd/es/select"
import { createStyles } from "antd-style"

import DelightfulSpin from "@/opensource/components/base/DelightfulSpin"
import { FlowApi } from "@/apis"

type BindOpenApiAccountForm = {
	bindAccountIds: string[]
	bind: PlatformItem[]
}

type BindOpenApiAccountProps = {
	flowId: string
	open: boolean
	onClose: () => void
}

const useBindOpenApiAccountStyles = createStyles(({ css, isDarkMode, token }) => {
	return {
		select: css`
			.open-api-label {
				img {
					width: 20px;
					border-radius: 4px;
				}
			}
		`,
		iconX: css`
			border-radius: 4px;
			width: 40px;
			cursor: pointer;
			border: 1px solid transparent;
			color: ${isDarkMode ? token.delightfulColorUsages.white : token.delightfulColorScales.grey[4]};
			&:hover {
				background: ${isDarkMode
					? token.delightfulColorScales.grey[3]
					: token.delightfulColorScales.grey[0]};
			}
		`,
		avatar: css`
			border-radius: 4px;
			background: ${token.delightfulColorScales.grey[4]};
			width: 30px;
			height: 30px;
		`,
		form: css`
			.delightful-form-item {
				margin-bottom: 12px;
			}
		`,
	}
})

function BindOpenApiAccount({ flowId, open, onClose }: BindOpenApiAccountProps) {
	const { t } = useTranslation()

	const { styles } = useBindOpenApiAccountStyles()

	// const [open, { setTrue, setFalse }] = useBoolean(false)

	const [options, setOptions] = useState([] as DefaultOptionType[])

	const [bindList, setBindList] = useState([] as PlatformItem[])

	const [isLoading, setIsLoading] = useState(false)

	const [form] = useForm<BindOpenApiAccountForm>()

	const handleOk = useMemoizedFn(async () => {
		try {
			const res = await form.validateFields()
			try {
				const ids = res.bindAccountIds
				await FlowApi.bindOpenApiAccount(flowId, ids)
				// Update flow list
				mutate(RequestUrl.getFlowList)
				mutate(RequestUrl.getOpenApiAccountList)
				onClose()
			} catch (err: any) {
				if (err.message) console.error(err.message)
			}
		} catch (err_1) {
			console.error("form validate error: ", err_1)
		}
	})

	const handleCancel = useMemoizedFn(() => {
		form.resetFields()
		onClose()
	})

	const initBindList = useMemoizedFn(async () => {
		setIsLoading(true)
		try {
			const bindPlatformList = await FlowApi.getOpenApiAccountList(flowId)
			setBindList(bindPlatformList?.list || [])
		} catch (error) {
			// console.log(error)
		} finally {
			setIsLoading(false)
		}
	})

	const initOptionList = useMemoizedFn(async () => {
		const optionList = await FlowApi.getOpenPlatformOfMine()
		if (optionList) {
			setOptions(
				optionList.list.map((item) => {
					return {
						label: (
							<Flex className="open-api-label" align="center" gap={8}>
								<img src={item.avatar} width={20} alt="" />
								<span>{item.name}</span>
							</Flex>
						),
						value: item.id,
						realLabel: item.name,
					}
				}),
			)
		}
	})

	useAsyncEffect(async () => {
		if (open && flowId) {
			initBindList()
			initOptionList()
		}
	}, [open, flowId])

	const onSelectNewApp = useMemoizedFn(async (appId: string) => {
		await FlowApi.bindOpenApiAccount(flowId, [appId])
		initBindList()
	})

	const onRemoveApp = useMemoizedFn(async (appId: string) => {
		await FlowApi.removeOpenApiAccount(flowId, [appId])
		initBindList()
	})

	useUpdateEffect(() => {
		form.setFieldsValue({
			bind: bindList,
		})
	}, [bindList])

	const filterOption = useMemoizedFn((input, option) => {
		return option.realLabel.toLowerCase().includes(input.toLowerCase())
	})
	return (
		<DelightfulModal
			title="Authorize Application"
			open={open}
			onOk={handleOk}
			onCancel={handleCancel}
			closable
			okText={t("button.confirm", { ns: "interface" })}
			cancelText={t("button.cancel", { ns: "interface" })}
			centered
			footer={null}
		>
			<Form
				form={form}
				validateMessages={{ required: t("form.required", { ns: "interface" }) }}
				layout="vertical"
				className={styles.form}
			>
				<Form.Item
					label="Add Application"
					extra="Select to add"
					required
					rules={[{ required: true }]}
				>
					<Flex align="center" gap={10}>
						<Select
							popupClassName={styles.select}
							options={options}
							value={null}
							onChange={onSelectNewApp}
							showSearch
							filterOption={filterOption}
						/>
					</Flex>
				</Form.Item>
				<DelightfulSpin section spinning={isLoading}>
					<Form.Item label="Added Applications">
						<Form.List name="bind">
							{(subFields) => (
								<div
									style={{
										display: "flex",
										flexDirection: "column",
										rowGap: 16,
									}}
								>
									{subFields.map((subField) => {
										const fieldData = form.getFieldValue([
											"bind",
											subField.name,
										])
										return (
											<Flex
												key={subField.key}
												justify="space-between"
												gap={6}
												align="center"
											>
												<Flex
													className="open-api-label"
													align="center"
													gap={8}
												>
													{/* @ts-ignore */}
													{fieldData.avatar && (
														<Image
															src={fieldData.avatar}
															className={styles.avatar}
															width={30}
															height={30}
															alt=""
														/>
													)}
													{!fieldData.avatar && (
														<span className={styles.avatar} />
													)}
													<span>{fieldData.name}</span>
												</Flex>
											<Tooltip title={`Remove ${fieldData.name}`}>
													<DelightfulIcon
														component={IconX}
														className={styles.iconX}
														onClick={() => {
															onRemoveApp(fieldData.id)
															// subOpt.remove(subField.name)
														}}
														width={60}
													/>
												</Tooltip>
											</Flex>
										)
									})}
								</div>
							)}
						</Form.List>
					</Form.Item>
				</DelightfulSpin>
			</Form>
		</DelightfulModal>
	)
}

export default BindOpenApiAccount





