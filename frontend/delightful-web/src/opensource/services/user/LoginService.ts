/* eslint-disable class-methods-use-this */
import type { LoginFormValuesMap, LoginStepResult } from "@/opensource/pages/login/types"
import { Login } from "@/types/login"
import type { User } from "@/types/user"
import { getDeviceInfo } from "@/utils/devices"
import { keyBy } from "lodash-es"
import { userTransformer } from "@/opensource/models/user/transformers"
import type * as apis from "@/opensource/apis"
import type { Container } from "@/opensource/services/ServiceContainer"
import type { VerificationCode } from "@/const/bussiness"
import { userStore } from "@/opensource/models/user/stores"
import { configStore } from "@/opensource/models/config"
import type { ConfigService } from "../config/ConfigService"
import type { UserService } from "./UserService"

export class LoginService {
	protected userApi: (typeof apis)["UserApi"]

	protected authApi: (typeof apis)["AuthApi"]

	protected commonApi: (typeof apis)["CommonApi"]

	protected readonly service: Container

	constructor(dependencies: typeof apis, service: Container) {
		this.userApi = dependencies.UserApi
		this.authApi = dependencies.AuthApi
		this.commonApi = dependencies.CommonApi
		this.service = service
	}

	/**
	 * @description Unified login
	 */
	loginStep = <T extends Login.LoginType>(type: T, values: LoginFormValuesMap[T]) => {
		return async () => {
			values.device = await getDeviceInfo(configStore.i18n.i18n.instance)
			switch (type) {
				case Login.LoginType.MobilePhonePassword:
					return this.userApi.login(type, values as Login.MobilePhonePasswordFormValues)
				case Login.LoginType.SMSVerificationCode:
					return this.userApi.login(type, values as Login.SMSVerificationCodeFormValues)
				case Login.LoginType.DingTalkScanCode:
				case Login.LoginType.DingTalkAvoid:
				case Login.LoginType.LarkScanCode:
				case Login.LoginType.WecomScanCode:
				case Login.LoginType.WechatOfficialAccount:
					return this.userApi.thirdPartyLogins(values as Login.ThirdPartyLoginsFormValues)
				default:
					throw new Error("Missing login type")
			}
		}
	}

	/** Sync current login account environment configuration */
	syncClusterConfig = async () => {
		try {
			const { login_code } = await this.authApi.getAccountDeployCode()
			const config = await this.getClusterConfig(login_code)
			return { clusterCode: login_code, clusterConfig: config }
		} catch (error: any) {
			const newMessage = `deployCodeSyncStep: ${error.message}`
			const newError = new Error(newMessage)
			newError.stack = error?.stack
			return Promise.reject(error)
		}
	}

	/** Get cluster environment */
	getClusterConfig = async (code: string) => {
		const { config } = await this.commonApi.getPrivateConfigure(code)
		await this.service.get<ConfigService>("configService").setClusterConfig(code, config)
		return Promise.resolve(config)
	}

	/** Step 2: Sync delightful organization by environment */
	delightfulOrganizationSyncStep = (clusterCode: string) => {
		return async (
			params: Login.UserLoginsResponse,
			teamshareOrganizationCode?: string,
		): Promise<Omit<LoginStepResult, "organizationCode">> => {
			try {
				const { access_token } = params
				const result = await this.authApi.bindDelightfulAuthorization(
					access_token,
					clusterCode,
					teamshareOrganizationCode,
				)
				const delightfulOrganizationMap = keyBy(result, "delightful_organization_code")
				return { access_token, delightfulOrganizationMap }
			} catch (error: any) {
				const newMessage = `delightfulOrganizationSyncStep: ${error.message}`
				const newError = new Error(newMessage)
				newError.stack = error?.stack
				window.console.error(error)
				return Promise.reject(error)
			}
		}
	}

	/** Step 4: Account synchronization (check current) */
	accountSyncStep = (deployCode: string) => {
		return async (params: LoginStepResult): Promise<string> => {
			try {
				const {
					access_token,
					delightfulOrganizationMap,
					organizations,
					teamshareOrganizationCode,
				} = params

				const delightfulOrgs = Object.values(delightfulOrganizationMap)

				const orgCode =
					teamshareOrganizationCode ??
					delightfulOrgs?.[0]?.third_platform_organization_code

				const delightfulOrg = keyBy(
					Object.values(delightfulOrganizationMap),
					"third_platform_organization_code",
				)

				if (orgCode) {
					const userInfo = await this.service
						.get<UserService>("userService")
						.fetchUserInfo(delightfulOrg?.[orgCode]?.delightful_user_id)
					if (userInfo) {
						// Construct user info after login completion, maintain in account system
						const userAccount: User.UserAccount = {
							deployCode,
							nickname: userInfo?.nickname,
							organizationCode: userInfo?.organization_code,
							avatar: userInfo?.avatar_url,
							delightful_id: userInfo?.delightful_id,
							delightful_user_id: userInfo?.user_id,
							access_token,
							teamshareOrganizations: organizations ?? [],
							organizations: delightfulOrgs,
						}
						this.service
							.get<UserService>("userService")
							.setUserInfo(userTransformer(userInfo))
						this.service.get<UserService>("userService").setAccount(userAccount)
					}
				}

				return access_token
			} catch (error: any) {
				const newMessage = `accountSyncStep: ${error.message}`
				const newError = new Error(newMessage)
				newError.stack = error?.stack
				return Promise.reject(error)
			}
		}
	}

	/** Account token synchronization */
	authorizationSyncStep = (userInfo: Login.UserLoginsResponse) => {
		this.service.get<UserService>("userService").setAuthorization(userInfo.access_token)
		return Promise.resolve(userInfo)
	}

	/** Teamshare ecosystem organization acquisition */
	organizationFetchStep = async (
		params: Omit<LoginStepResult, "organizationCode">,
	): Promise<LoginStepResult> => {
		try {
			const { access_token, delightfulOrganizationMap } = params

			// Get all organizations under current Teamshare account
			// const organizations = await this.userApi.getUserOrganizations(
			// 	{authorization: access_token},
			// 	deployCode,
			// )
			// debugger
			// const teamshareOrgsCode = organizations.map((o) => o.organization_code)
			//
			// // After obtaining Teamshare organizations, filter delightfulOrganizationMap for validity (since backend doesn't validate delightfulOrganizationMap data, filter delightfulOrganizationMap based on organizations not in Teamshare)
			// const delightfulOrganizationArray = Object.values(allDelightfulOrganizationMap).filter((o) =>
			// 	teamshareOrgsCode.includes(o.third_platform_organization_code),
			// )
			// const delightfulOrganizationMap = keyBy(
			// 	delightfulOrganizationArray,
			// 	"third_platform_organization_code",
			// )
			// const teamshareOrgMap = keyBy(delightfulOrganizationArray, "delightful_organization_code")

			// const authorizedOrgsCode = Object.keys(delightfulOrganizationMap) // Authorized organizations
			// const authorizedOrg = organizations.find((org) =>
			// 	authorizedOrgsCode.includes(org.organization_code),
			// )

			// Handle delightfulOrganizationCode (priority: check cache existence)
			const delightfulOrgCodeCache = userStore.user?.organizationCode
			// let delightfulOrgCode = null
			// if (delightfulOrgCodeCache && teamshareOrgMap?.[delightfulOrgCodeCache]) {
			// 	// Use cache only if delightfulOrgCode in cache exists and is valid in current account
			// 	delightfulOrgCode = delightfulOrgCodeCache
			// } else {
			// 	// If delightful organization Code doesn't exist, use the first as preferred choice
			// 	delightfulOrgCode =
			// 		delightfulOrganizationMap?.[authorizedOrg?.organization_code ?? ""]
			// 			?.delightful_organization_code
			// }

			return {
				access_token,
				delightfulOrganizationMap,
				organizations: [],
				organizationCode:
					delightfulOrgCodeCache ||
					Object.values(delightfulOrganizationMap)?.[0]?.delightful_organization_code,
			}
		} catch (error: any) {
			const newMessage = `organizationFetchStep: ${error.message}`
			const newError = new Error(newMessage)
			newError.stack = error?.stack
			return Promise.reject(error)
		}
	}

	/** Teamshare ecosystem organization synchronization */
	organizationSyncStep = async (params: LoginStepResult) => {
		const {
			organizationCode,
			teamshareOrganizationCode,
			organizations,
			delightfulOrganizationMap,
		} = params

		this.service.get<UserService>("userService").setOrganization({
			organizationCode,
			teamshareOrganizationCode,
			organizations,
			delightfulOrganizationMap,
		})
		return Promise.resolve(params)
	}

	/** Step 6: User information synchronization (delightful system unique user ID) */
	fetchUserInfoStep = async (unionId: string) => {
		try {
			const userInfo = await this.service
				.get<UserService>("userService")
				.fetchUserInfo(unionId)
			if (userInfo) {
				this.service.get<UserService>("userService").setUserInfo(userTransformer(userInfo))
			}
			return userInfo
		} catch (error: any) {
			const newMessage = `fetchUserInfoStep: ${error.message}`
			const newError = new Error(newMessage)
			newError.stack = error?.stack
			return Promise.reject(error)
		}
	}

	/** Get user phone number verification code */
	getPhoneVerificationCode = async (
		type: VerificationCode,
		phone: string,
		stateCode?: string,
	) => {
		return this.userApi.getPhoneVerificationCode(type, phone, stateCode)
	}

	/** Get user phone number verification code */
	getUsersVerificationCode = async (type: VerificationCode, phone: string) => {
		return this.userApi.getUsersVerificationCode(type, phone)
	}
}
