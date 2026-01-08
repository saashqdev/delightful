import { Form, message } from "antd"
import { useMemoizedFn } from "ahooks"
import type { Login } from "@/types/login"
import { LoginFormValuesMap, OnSubmitFn } from "@/opensource/pages/login/types"
import { getDeviceInfo } from "@/utils/devices"
import { useTranslation } from "react-i18next"
import { useState } from "react"
import { useClusterCode } from "@/opensource/providers/ClusterProvider"
import useLoginFormOverrideStyles from "@/styles/login-form-overrider"
import { loginService, userService } from "./service"
import { useStyles } from "./styles"
import { userStore } from "@/opensource/models/user"
import MobilePhonePasswordForm from "../components/MobilePhonePasswordForm"
import Footer from "../components/Footer"
import { useUserAgreedPolicy } from "../hooks/useUserAgreedPolicy"
import DelightfulSpin from "@/opensource/components/base/DelightfulSpin"
import DelightfulModal from "@/opensource/components/base/DelightfulModal"

interface AccountModalProps {
	onClose: () => void
}

function AccountModal(props: AccountModalProps) {
	const { onClose } = props

	const { styles, cx } = useStyles()
	const { styles: loginFormOverrideStyles } = useLoginFormOverrideStyles()
	const { clusterCode } = useClusterCode()
	const { t, i18n } = useTranslation("login")

	const [open, setOpen] = useState(true)
	const [form] = Form.useForm<LoginFormValuesMap[Login.LoginType.MobilePhonePassword]>()
	const { agree, setAgree, triggerUserAgreedPolicy } = useUserAgreedPolicy()

	const redirectUrlStep = useMemoizedFn(() => {
		onClose?.()
	})

	const [loading, setLoading] = useState(false)

	// Submit data and uniformly handle the logic for different login methods
	const onSubmit = useMemoizedFn<OnSubmitFn<Login.LoginType>>(async (type, values, overrides) => {
		if (!agree) {
			await triggerUserAgreedPolicy()
		}

		setLoading(true)

		values.device = await getDeviceInfo(i18n)
		const delightfulOrgSyncStep = loginService.delightfulOrganizationSyncStep(clusterCode as string)
		const userSyncStep = loginService.accountSyncStep(clusterCode as string)
		return Promise.resolve()
			.then(overrides?.loginStep ?? loginService.loginStep(type, values))
			.then(async (userInfo) => {
				// Due to workflow requirements, need to bind user token in Delightful first before setting token
				const delightfulOrgSyncResponse = await delightfulOrgSyncStep(userInfo)
				await loginService.authorizationSyncStep(userInfo)

				// Environment synchronization
				await loginService.syncClusterConfig()
				const orgSyncResponse = await loginService.organizationFetchStep({
					...delightfulOrgSyncResponse,
				})
				await loginService.organizationSyncStep(orgSyncResponse)
				return userSyncStep(orgSyncResponse)
			})
			.then(() => {
				const { userInfo } = userStore.user
				if (userInfo) {
					return userService.loadUserInfo(userInfo, { showSwitchLoading: true })
				}
				return Promise.resolve()
			})
			.then(redirectUrlStep)
			.catch((error) => {
				console.error("login error", error)
				if (error.code === 3102) {
					message.error(t("delightfulOrganizationSyncStep.pleaseBindExistingAccount"))
				}
			})
			.finally(() => {
				setLoading(false)
			})
	})

	const dom = <div className={styles.header}>{t("account.create")}</div>

	return (
		<DelightfulModal
			title={dom}
			footer={null}
			open={open}
			width={460}
			destroyOnClose
			centered
			onCancel={() => setOpen(false)}
			classNames={{
				header: styles.modalHeader,
				content: styles.modalContent,
				mask: styles.modalMask,
			}}
			wrapClassName={styles.modal}
			afterClose={onClose}
		>
			<DelightfulSpin spinning={loading}>
				<div className={cx(styles.layout, loginFormOverrideStyles.container)}>
					<MobilePhonePasswordForm form={form} onSubmit={onSubmit} />
					<Footer agree={agree} onAgreeChange={setAgree} tipVisible />
				</div>
			</DelightfulSpin>
		</DelightfulModal>
	)
}

export default AccountModal
