import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { IconEye, IconEyeOff } from "@tabler/icons-react"
import { Flex, Form, Input } from "antd"
import { useTranslation } from "react-i18next"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import type { LoginPanelProps } from "@/types/login"
import { Login } from "@/types/login"
import { useDebounceFn, useMemoizedFn } from "ahooks"
import type { OnSubmitFn } from "@/opensource/pages/login/types"
import { useStyles } from "../../styles"
import { LoginValueKey } from "../../constants"
import PhoneInputFormItem from "../PhoneInputFormItem"
import { useTheme } from "antd-style"

const defaultValue: () => Login.MobilePhonePasswordFormValues = () => ({
	phone: "",
	state_code: "+86",
	password: "",
	type: Login.LoginType.MobilePhonePassword,
	redirect: window.location.href,
})

interface PasswordFormProps extends LoginPanelProps<Login.MobilePhonePasswordFormValues> {
	onSubmit: OnSubmitFn<Login.LoginType.MobilePhonePassword>
}

export default function MobilePhonePasswordForm(props: PasswordFormProps) {
	const { form, onSubmit } = props
	const { t } = useTranslation("login")
	const { delightfulColorUsages } = useTheme()

	const { styles } = useStyles()

	const phoneStateCode = Form.useWatch<string>(LoginValueKey.PHONE_STATE_CODE, form)

	const { run: handleSubmit } = useDebounceFn(
		useMemoizedFn(() => {
			form?.validateFields().then((values) => {
				if (values) onSubmit(Login.LoginType.MobilePhonePassword, values)
			})
		}),
		{ wait: 200 },
	)

	const onKeyDown = useMemoizedFn((event) => {
		if (event.key === "Enter") {
			handleSubmit()
		}
	})

	return (
		<Flex vertical>
			<Form
				layout="vertical"
				form={form}
				initialValues={defaultValue()}
				onFinish={handleSubmit}
			>
				<Flex vertical gap={20}>
					<Form.Item hidden name={LoginValueKey.REDIRECT_URL} />
					<Form.Item hidden name={LoginValueKey.PHONE_STATE_CODE} />
					<PhoneInputFormItem
						name={LoginValueKey.PHONE}
						label={t("phone.label")}
						className={styles.formItem}
						phoneStateCode={phoneStateCode}
						onChangePhoneStateCode={(value) => form?.setFieldValue("state_code", value)}
					/>
					<Form.Item
						name={LoginValueKey.PASSWORD}
						label={t("password.label")}
						className={styles.formItem}
					>
						<Input.Password
							placeholder={t("password.placeholder")}
							iconRender={(visible) => (
								<DelightfulIcon
									component={visible ? IconEye : IconEyeOff}
									size={16}
									color={delightfulColorUsages.text[2]}
								/>
							)}
							onKeyDown={onKeyDown}
							autoComplete="current-password"
						/>
					</Form.Item>
					<DelightfulButton
						type="primary"
						block
						className={styles.login}
						onClick={handleSubmit}
					>
						{t("login")}
					</DelightfulButton>
				</Flex>
				{/* <div className={styles.options}>
					<DelightfulButton
						type="link"
						onClick={() => switchLoginType(loginConfig.switchType)}
					>
						{t(loginConfig.translateKey.switch)}
					</DelightfulButton>
					<DelightfulButton type="link">{t("findPassword")}</DelightfulButton>
				</div> */}
			</Form>
		</Flex>
	)
}
