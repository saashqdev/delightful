import { useTranslation } from "react-i18next"
import { useDebounceFn, useMemoizedFn } from "ahooks"
import { getDeviceInfo } from "@/utils/devices"
import { Login } from "@/types/login"
import DelightfulSpin from "@/opensource/components/base/DelightfulSpin"
import { useState } from "react"
import Logger from "@/utils/log/Logger"
import { loginService, userService } from "@/services"
import { LoginValueKey } from "@/opensource/pages/login/constants"
import { RoutePath } from "@/const/routes"
import { useNavigate } from "@/opensource/hooks/useNavigate"
import type { LoginFormValuesMap, OnSubmitFn } from "./types"
import { withLoginService, useLoginServiceContext } from "./providers/LoginServiceProvider"
import MobilePhonePasswordForm from "./components/MobilePhonePasswordForm"
import { Form } from "antd"

const console = new Logger("sso")

function LoginPage() {
	const { i18n } = useTranslation("login")

	const navigate = useNavigate()
	const { clusterCode } = useLoginServiceContext()

	const [loading, setLoading] = useState(false)

	const [form] = Form.useForm<LoginFormValuesMap[Login.LoginType.MobilePhonePassword]>()

	// Login flow
	const redirect = useMemoizedFn(() => {
		const url = new URL(window.location.href)
		const { searchParams } = url
		/** Redirect URL here, if it's an external site, need to consider whether to carry parameters */
		const redirectURI = searchParams.get(LoginValueKey.REDIRECT_URL)
		if (redirectURI) {
			window.location.assign(decodeURIComponent(redirectURI))
		} else {
			navigate(RoutePath.Chat, { replace: true })
		}
	})

	// Submit data, uniformly handle logic for different login methods
	const { run: onSubmit } = useDebounceFn<OnSubmitFn<Login.LoginType.MobilePhonePassword>>(
		async (
			type: Login.LoginType.MobilePhonePassword,
			values: LoginFormValuesMap[Login.LoginType.MobilePhonePassword],
			overrides?: {
				loginStep?: () => Promise<Login.UserLoginsResponse>
			},
		) => {
			await form.validateFields()

			setLoading(true)
			values.device = await getDeviceInfo(i18n)
			const delightfulOrgSyncStep = loginService.delightfulOrganizationSyncStep(clusterCode as string)
			const userSyncStep = loginService.accountSyncStep(clusterCode as string)
			return (
				Promise.resolve()
					.then(overrides?.loginStep ?? loginService.loginStep(type, values))
					.then((...args) => {
						console.error("loginStep", ...args)
						return Promise.resolve(...args)
					})
					.then(loginService.authorizationSyncStep)
					.then((...args) => {
						console.error("authorizationSyncStep", ...args)
						return Promise.resolve(...args)
					})
					.then(delightfulOrgSyncStep)
					.then(async (userInfo) => {
					// Environment synchronization
						await loginService.syncClusterConfig()
						return Promise.resolve(userInfo)
					})
					// @ts-ignore
					.then(loginService.organizationFetchStep)
					.then(loginService.organizationSyncStep)
					.then(userSyncStep)
					.then(() => {
						return Promise.resolve(userService.wsLogin({ showLoginLoading: false }))
					})
					.then(redirect)
					.catch((error) => {
						console.error("error", error?.message)
						if (error.code === 3102) {
						// User has not created an account, redirect to invitation page
							navigate(RoutePath.Invite, { replace: true })
						}
					})
					.finally(() => {
						setLoading(false)
					})
			)
		},
		{ wait: 3000, leading: true, trailing: false },
	)

	return (
		<DelightfulSpin spinning={loading}>
			<MobilePhonePasswordForm form={form} onSubmit={onSubmit} />
		</DelightfulSpin>
	)
}

export default withLoginService(LoginPage, loginService)
