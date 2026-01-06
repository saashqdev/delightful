import type { User } from "@/types/user"
import type { VerificationCode } from "@/const/bussiness"
import { genRequestUrl } from "@/utils/http"
import { shake } from "radash"
import { isNil } from "lodash-es"
import type { Login } from "@/types/login"
import { configStore } from "@/opensource/models/config"
import { RequestUrl } from "../constant"
import type { HttpClient, RequestConfig } from "../core/HttpClient"

export interface TeamshareUserInfo {
	id: string
	real_name: string
	avatar: string
	organization: string
	description: string
	nick_name: string
	phone: string
	is_remind_change_password: boolean
	platform_type: number
	is_organization_admin: boolean
	is_application_admin: boolean
	identifications: []
	shown_identification: null
	workbench_menu_config: {
		workbench: boolean
		application: boolean
		approval: boolean
		assignment: boolean
		cloud_storage: boolean
		knowledge_base: boolean
		message: boolean
		favorite: boolean
	}
	timezone: string
	is_perfect_password: boolean
	state_code: string
	departments: {
		name: string
		level: number
		id: string
	}[][]
}

export const generateUserApi = (fetch: HttpClient) => ({
	/**
	 * @description Login
	 * @param {Login.LoginType} type Login type
	 * @param {Login.SMSVerificationCodeFormValues | Login.MobilePhonePasswordFormValues} values Login form values
	 * @returns
	 */
	login(
		type: Login.LoginType,
		values: Login.SMSVerificationCodeFormValues | Login.MobilePhonePasswordFormValues,
	) {
		return fetch.post<Login.UserLoginsResponse>("/api/v1/sessions", {
			...values,
			type,
		})
	},

	/**
	 * @description Third-party login (DingTalk, WeCom, Feishu)
	 * @param {Login.ThirdPartyLoginsFormValues | Login.WechatOfficialAccountLoginsFormValues} values Login form values
	 */
	thirdPartyLogins(
		values: Login.ThirdPartyLoginsFormValues | Login.WechatOfficialAccountLoginsFormValues,
		options?: Omit<RequestConfig, "url" | "body">,
	) {
		return fetch.post<Login.UserLoginsResponse>(
			genRequestUrl(RequestUrl.thirdPartyLogins),
			values,
			{
				enableErrorMessagePrompt: false,
				enableRequestUnion: true,
				enableAuthorization: false,
				...options,
			},
		)
	},

	/**
	 * Get user devices
	 * @returns
	 */
	getUserDevices() {
		return fetch.get<User.UserDeviceInfo[]>(genRequestUrl(RequestUrl.getUserDevices))
	},

	/**
	 * Get user info
	 * @returns
	 */
	getUserInfo() {
		return fetch.get<User.UserInfo>(genRequestUrl(RequestUrl.getUserInfo))
	},

	/**
	 * Get user accounts
	 * @param {Record<string, string>} headers Request headers; business layer decides which account header to use
	 * @param {string} deployCode Private deploy code; business layer decides which service to query
	 */
	getUserOrganizations(headers?: Record<string, string>, deployCode?: string) {
		const { clusterConfig } = configStore.cluster
		const url =
			(!isNil(deployCode) ? clusterConfig?.[deployCode]?.services?.keewoodAPI?.url : "") || ""

		return fetch.get<User.UserOrganization[]>(url + genRequestUrl(RequestUrl.getUserAccounts), {
			headers: headers ?? {},
			enableRequestUnion: true,
		})
	},

	/**
	 * Logout from a specific device
	 * @param code
	 * @param id
	 * @returns
	 */
	logoutDevices(code: string, id: string) {
		return fetch.post(genRequestUrl(RequestUrl.logoutDevices), { code, id })
	},

	/**
	 * Get verification code by type
	 * @param type
	 * @param phone
	 * @returns
	 */
	getUsersVerificationCode(type: VerificationCode, phone?: string) {
		return fetch.post(
			genRequestUrl(RequestUrl.getUsersVerificationCode),
			shake({ type, phone }),
			{
				enableRequestUnion: true,
			},
		)
	},

	/**
	 * Get verification code for changing phone
	 * @param type
	 * @param phone
	 * @param state_code
	 * @returns
	 */
	getPhoneVerificationCode(type: VerificationCode, phone?: string, state_code?: string) {
		return fetch.post(
			genRequestUrl(RequestUrl.getUserVerificationCode),
			shake({ type, phone, state_code }),
			{
				enableRequestUnion: true,
			},
		)
	},

	/**
	 * Change password
	 * @param code
	 * @param new_password
	 * @param repeat_new_password
	 * @returns
	 */
	changePassword(code: string, new_password: string, repeat_new_password: string) {
		return fetch.put(genRequestUrl(RequestUrl.changePassword), {
			code,
			new_password,
			repeat_new_password,
		})
	},

	/**
	 * Change phone number
	 * @param code
	 * @param new_phone
	 * @param new_phone_code
	 * @param state_code
	 * @returns
	 */
	changePhone(code: string, new_phone: string, new_phone_code: string, state_code: string) {
		return fetch.put(genRequestUrl(RequestUrl.changePhone), {
			code,
			new_phone,
			new_phone_code,
			state_code,
		})
	},

	/**
	 * Get Teamshare user info
	 * @returns
	 */
	getTeamshareUserInfo() {
		return fetch.get<TeamshareUserInfo>(genRequestUrl(RequestUrl.getTeamshareUserInfo))
	},
})
