import { LoginDeployment } from "@/opensource/pages/login/constants"
import type { getDeviceInfo } from "@/utils/devices"
import type { FormInstance } from "antd"

export interface LoginPanelProps<V> {
	form?: FormInstance<V>
	/** Before next step */
	nextStepBefore?: () => Promise<void>
	/** Whether disabled */
	disabled?: boolean
	/** Set login method */
	setLoginType?: (loginType: Login.LoginType) => void
	/** Set login environment */
	setDeployment?: (deployment: LoginDeployment) => void
	/** Whether to show next step */
	nextStep?: boolean
	/** Set whether to show next step */
	setNextStep?: (nextStep: boolean) => void
}

export namespace Login {
	/** Login method (strongly bound to API, do not modify) */
	export enum LoginType {
		/** SMS verification code login */
		SMSVerificationCode = "phone_captcha",
		/** Phone number + password login */
		MobilePhonePassword = "phone_password",
		/** Account password login */
		// AccountPassword = "username",
		/** DingTalk scan code login */
		DingTalkScanCode = "DingTalk",
		/** DingTalk auto-login */
		DingTalkAvoid = "DingTalkAvoid",
		/** WeCom */
		WecomScanCode = "WeCom",
		/** Lark */
		LarkScanCode = "Lark",
		/** Lark auto-login */
		LarkAvoid = "Lark",
		/** WeChat official account login */
		WechatOfficialAccount = "wechat_official_account",
	}

	/** Login response (verification code, phone+password, DingTalk, Lark, WeCom, etc.) */
	export interface UserLoginsResponse {
		access_token: string
		bind_phone: boolean
		is_perfect_password: boolean
		user_info: {
			avatar: string
			description: string
			id: string
			mobile: string
			position: string
			real_name: string
			state_code: string
		}
	}

	/** Common login form data type */
	interface LoginFormCommonValues {
		device?: Awaited<ReturnType<typeof getDeviceInfo>>
		redirect: string
	}

	/** Verification code login form */
	export interface SMSVerificationCodeFormValues extends LoginFormCommonValues {
		type: Login.LoginType
		/** Phone number */
		phone: string
		/** Country code */
		state_code: string
		/** Verification code */
		code: string
		/** Whether to auto-register */
		auto_register: boolean
	}

	/** Phone number + password */
	export interface MobilePhonePasswordFormValues extends LoginFormCommonValues {
		type: Login.LoginType
		/** Phone number */
		phone: string
		/** Country code */
		state_code: string
		/** Password */
		password: string
	}

	/** DingTalk scan code login */
	export interface ThirdPartyLoginsFormValues extends LoginFormCommonValues {
		/** Third-party login platform */
		platform_type: Login.LoginType
		/** Third-party platform temporary authorization code */
		authorization_code: string
	}

	/** Official account login */
	export interface WechatOfficialAccountLoginsFormValues
		extends Omit<LoginFormCommonValues, "redirect"> {
		/** Third-party login platform */
		platform_type: Login.LoginType.WechatOfficialAccount
		/** Third-party platform temporary authorization code */
		authorization_code: string
		/** Scene value */
		scene_value: string
	}
}
