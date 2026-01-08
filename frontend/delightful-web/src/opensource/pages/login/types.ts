import type { Login } from "@/types/login"
import type { User } from "@/types/user"

// Mapping relationship between login methods and forms
export type LoginFormValuesMap = {
	[Login.LoginType.SMSVerificationCode]: Login.SMSVerificationCodeFormValues
	[Login.LoginType.MobilePhonePassword]: Login.MobilePhonePasswordFormValues
	[Login.LoginType.DingTalkScanCode]: Login.ThirdPartyLoginsFormValues
	[Login.LoginType.DingTalkAvoid]: Login.ThirdPartyLoginsFormValues
	[Login.LoginType.LarkScanCode]: Login.ThirdPartyLoginsFormValues
	[Login.LoginType.WecomScanCode]: Login.ThirdPartyLoginsFormValues
	[Login.LoginType.WechatOfficialAccount]: Login.WechatOfficialAccountLoginsFormValues
}

/**
 * Login submit function
 */
export type OnSubmitFn<T extends Login.LoginType> = (
	type: T,
	values: LoginFormValuesMap[T],
	overrides?: {
		loginStep?: () => Promise<Login.UserLoginsResponse>
	},
) => void

export interface LoginStepResult {
	access_token: string
	delightfulOrganizationMap: Record<string, User.DelightfulOrganization>
	organizations?: Array<User.UserOrganization>
	/** Organization code under delightful ecosystem */
	organizationCode?: string
	/** Organization code under teamshare ecosystem */
	teamshareOrganizationCode?: string
	deployCode?: string
}
