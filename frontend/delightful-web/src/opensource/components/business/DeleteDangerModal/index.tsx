import { Flex, Form, Input } from "antd"
import { useTranslation } from "react-i18next"
import { useBoolean, useMemoizedFn } from "ahooks"
import DelightfulModal from "@/opensource/components/base/DelightfulModal"
import type { OpenableProps } from "@/utils/react"
import IconWarning from "@/enhance/tabler/icons-react/icons/IconWarning"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import { useMemo, useState } from "react"

import { colorScales } from "@/opensource/providers/ThemeProvider/colors"
import { useStyles } from "./styles"

interface DeleteDangerModalProps {
	// Content to delete
	content: string
	// Whether secondary confirmation is required
	needConfirm?: boolean
	onSubmit?: () => void
}

function DeleteDangerModal({
	content,
	needConfirm = false,
	onSubmit,
	onClose,
	...props
}: OpenableProps<DeleteDangerModalProps>) {
	const { styles } = useStyles()
	const { t } = useTranslation("interface")
	const [form] = Form.useForm()
	const [open, { setFalse }] = useBoolean(true)
	const [focus, { setTrue: openFocus, setFalse: closeFocus }] = useBoolean(false)
	const [confirmText, setConfirmText] = useState<string>("")

	const onCancel = useMemoizedFn(() => {
		setFalse()
		closeFocus()
		onClose?.()
	})

	const onConfirm = useMemoizedFn(() => {
		onSubmit?.()
		onCancel()
	})

	const disable = useMemo(() => {
		return needConfirm ? !(confirmText === content) : false
	}, [content, confirmText, needConfirm])

	const footer = useMemoizedFn(() => (
		<Flex justify="end" gap={12}>
			<DelightfulButton onClick={onCancel} type="text" className={styles.button}>
				{t("button.cancel")}
			</DelightfulButton>
			<DelightfulButton
				disabled={disable}
				onClick={onConfirm}
				className={styles.dangerButton}
			>
				{t("deleteConfirm")}
			</DelightfulButton>
		</Flex>
	))

	return (
		<DelightfulModal
			className={styles.modal}
			centered
			maskClosable={false}
			open={open}
			onOk={onConfirm}
			onCancel={onCancel}
			width={400}
			destroyOnClose
			footer={footer}
			{...props}
		>
			<Flex gap={12}>
				<IconWarning size={24} color={colorScales.orange[5]} />
				<Flex vertical gap={8}>
					<div className={styles.title}>{t("deleteConfirmTitle")}</div>
					{needConfirm && (
						<>
							<div className={styles.desc}>
								<span>{t("deleteConfirmTip1")} </span>
								<span className={styles.content}>{content}</span>
								<span> {t("deleteConfirmTip2")}</span>
							</div>
							<Form form={form} requiredMark={false}>
								<Form.Item
									name="confirm"
									noStyle
									rules={[
										() => ({
											validator(_, value) {
												if (!value || content === value) {
													return Promise.resolve()
												}
												return Promise.reject(new Error(t("diffTip")))
											},
										}),
									]}
								>
									{!focus ? (
										<Flex
											align="center"
											className={styles.fakeInput}
											onClick={openFocus}
										>
											<span>{t("pleaseInput1")} </span>
											<span className={styles.content}>{content}</span>
											<span> {t("pleaseInput2")}</span>
										</Flex>
									) : (
										<Input
											className={styles.input}
											autoFocus
											onChange={(e) => setConfirmText(e.target.value)}
										/>
									)}
								</Form.Item>
							</Form>
						</>
					)}
					{!needConfirm && (
						<div className={styles.desc}>
							<span>{t("deleteConfirm")} </span>
							<span className={styles.content}>{content}</span>
						</div>
					)}
				</Flex>
			</Flex>
		</DelightfulModal>
	)
}

export default DeleteDangerModal
