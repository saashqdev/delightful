/* eslint-disable class-methods-use-this */
import type { StructureUserItem } from "@/types/organization"
import type { User } from "@/types/user"
import { keyBy } from "lodash-es"
import type { Login } from "@/types/login"
import { initDataContextDb } from "@/opensource/database/data-context"
import type * as apis from "@/opensource/apis"
import type { Container } from "@/opensource/services/ServiceContainer"
import { userStore } from "@/opensource/models/user/stores"
import { userTransformer } from "@/opensource/models/user/transformers"
import { UserRepository } from "@/opensource/models/user/repositories/UserRepository"
import { AccountRepository } from "@/opensource/models/user/repositories/AccountRepository"
import type { LoginService } from "./LoginService"
import chatWebSocket from "@/opensource/apis/clients/chatWebSocket"
import groupInfoService from "@/opensource/services/groupInfo"
import userInfoService from "@/opensource/services/userInfo"
import chatDb from "@/opensource/database/chat"
import MessageSeqIdService from "@/opensource/services/chat/message/MessageSeqIdService"
import MessageService from "@/opensource/services/chat/message/MessageService"
import conversationService from "@/opensource/services/chat/conversation/ConversationService"
import { interfaceStore } from "@/opensource/stores/interface"
import { ChatApi } from "@/apis"
import ChatFileService from "../chat/file/ChatFileService"
import EditorDraftService from "../chat/editor/DraftService"
import Logger from "@/utils/log/Logger"
import { BroadcastChannelSender } from "@/opensource/broadcastChannel"

const console = new Logger("UserService")

export interface OrganizationResponse {
	delightfulOrganizationMap: Record<string, User.DelightfulOrganization>
	organizations?: Array<User.UserOrganization>
	/** Organization Code under delightful ecosystem */
	organizationCode?: string
	/** Organization Code under teamshare ecosystem */
	teamshareOrganizationCode?: string
}

export class UserService {
	private readonly contactApi: typeof apis.ContactApi

	private readonly service: Container

	lastLogin: {
		authorization: string
		promise: Promise<void>
	} | null = null

	constructor(dependencies: typeof apis, service: Container) {
		this.contactApi = dependencies.ContactApi
		this.service = service

		/** Listen for connection success event, auto login */
		chatWebSocket.on("open", ({ reconnect }) => {
			console.log("open", { reconnect })
			if (reconnect) {
				console.log("Reconnected successfully, auto login")
				this.wsLogin({ showLoginLoading: false })
			}
		})
	}

	/**
	 * @description Initialize (persistent data/memory state)
	 */
	async init() {
		// Sync current user state
		const user = new UserRepository()
		const token = await user.getAuthorization()
		const userInfo = await user.getUserInfo()

		userStore.user.setAuthorization(token ?? null)
		userStore.user.setUserInfo(userInfo ?? null)

		// Organization sync
		const organizations = await user.getOrganizations()
		const organizationCode = await user.getOrganizationCode()
		const teamshareOrganizations = await user.getTeamshareOrganizations()
		const teamshareOrganizationCode = await user.getTeamshareOrganizationCode()

		userStore.user.setOrganizations(organizations ?? {})
		userStore.user.setOrganizationCode(organizationCode ?? "")
		userStore.user.setTeamshareOrganizations(teamshareOrganizations ?? [])
		userStore.user.setTeamshareOrganizationCode(teamshareOrganizationCode ?? "")

		// Sync all accounts
		const account = new AccountRepository()
		const accounts = await account.getAll()

		accounts.map((a) => userStore.account.setAccount(a))
	}

	/**
	 * @description Sync user environment when logged in
	 */
	loadConfig = async () => {
		// const { authorization } = userStore.user
		// if (authorization) {
		// 	// Step 1: Check authorization code
		// 	const deployCode = await this.service.get<LoginService>("loginService").deployCodeSyncStep()
		// 	setClusterCode(deployCode)
		// 	console.warn("withPrivateDeploy Step 1: Sync current account deployCode", deployCode)
		//
		// 	// Step 2: Get environment configuration
		// 	const config = await loginService.envSyncStep(deployCode)
		// 	console.warn("withPrivateDeploy Step 2: Get deployConfig", config)
		// }
	}

	/**
	 * @description Remove current user token
	 */
	setAuthorization(authorization: string | null) {
		// Set memory state, sync persistent data
		const user = new UserRepository()
		user.setAuthorization(authorization ?? "")

		userStore.user.setAuthorization(authorization)
	}

	/**
	 * @description Fetch user info
	 * @param {string} unionId User unique ID
	 */
	fetchUserInfo = async (unionId: string): Promise<StructureUserItem | null> => {
		if (!unionId) {
			return null
		}
		const { items } = await this.contactApi.getUserInfos({ user_ids: [unionId] })
		return items?.[0]
	}

	/**
	 * @description Set user info in UserStore
	 * @param userInfo
	 */
	setUserInfo(userInfo: User.UserInfo | null) {
		const info = userInfo ?? null
		// Persistent data sync
		const user = new UserRepository()
		user.setUserInfo(info)

		// Memory state sync
		userStore.user.setUserInfo(info)

		// Only fetch if value exists
		// if (info) {
		// 	AuthApi.getAdminPermission().then((res) => {
		// 		userStore.user.isAdmin = res.is_admin
		// 	})
		// }
		return info
	}

	/**
	 * @description Organization sync
	 */
	setOrganization(params: OrganizationResponse) {
		const {
			organizationCode,
			teamshareOrganizationCode,
			organizations,
			delightfulOrganizationMap,
		} = params
		// Persistent data sync
		const user = new UserRepository()
		user.setOrganizations(delightfulOrganizationMap ?? {})
		user.setOrganizationCode(organizationCode ?? "")
		user.setTeamshareOrganizations(organizations ?? [])
		user.setTeamshareOrganizationCode(teamshareOrganizationCode ?? "")

		// Memory state sync
		userStore.user.setOrganizationCode(organizationCode || "")
		userStore.user.setTeamshareOrganizationCode(teamshareOrganizationCode || "")
		userStore.user.setOrganizations(delightfulOrganizationMap || {})
		userStore.user.setTeamshareOrganizations(organizations || [])
	}

	/**
	 * @description Set Teamshare organization list
	 */
	setTeamshareOrganizations(organizations: Array<User.UserOrganization>) {
		const user = new UserRepository()
		user.setTeamshareOrganizations(organizations ?? [])
		userStore.user.setTeamshareOrganizations(organizations || [])
	}

	/**
	 * @description Switch organization
	 */
	switchOrganization = async (
		delightful_user_id: string,
		delightful_organization_code: string,
		fallbackUserInfo: User.UserInfo,
	) => {
		try {
			this.setDelightfulOrganizationCode(delightful_organization_code)

			// Fetch user info
			const { items } = await this.contactApi.getUserInfos({
				user_ids: [delightful_user_id],
				query_type: 2,
			})

			const targetUser = items[0]

			if (!targetUser) {
				throw new Error("targetUser is null")
			}

			const userInfo = userTransformer(targetUser)
			await this.loadUserInfo(userInfo, { showSwitchLoading: true })
			this.setUserInfo(userInfo)

			/** Broadcast organization switch */
			BroadcastChannelSender.switchOrganization({
				userInfo,
				delightfulOrganizationCode: delightful_organization_code,
			})
		} catch (err) {
			console.error(err)
			// Switch failed, restore current organization
			this.setDelightfulOrganizationCode(fallbackUserInfo?.organization_code)
			this.setUserInfo(fallbackUserInfo)
		}
	}

	setDelightfulOrganizationCode(organizationCode: string) {
		const user = new UserRepository()
		const { delightfulOrganizationMap } = userStore.user
		const teamshareOrgCode =
			delightfulOrganizationMap?.[organizationCode]?.third_platform_organization_code ?? ""
		user.setOrganizationCode(organizationCode)
		user.setTeamshareOrganizationCode(teamshareOrgCode)

		userStore.user.setOrganizationCode(organizationCode)
		userStore.user.setTeamshareOrganizationCode(teamshareOrgCode)
	}

	setTeamshareOrganizationCode(organizationCode: string) {
		const user = new UserRepository()
		const { delightfulOrganizationMap } = userStore.user
		const orgMap = keyBy(
			Object.values(delightfulOrganizationMap),
			"third_platform_organization_code",
		)
		const orgCode = orgMap?.[organizationCode]?.delightful_organization_code
		user.setOrganizationCode(orgCode)
		user.setTeamshareOrganizationCode(organizationCode)

		userStore.user.setOrganizationCode(orgCode)
		userStore.user.setTeamshareOrganizationCode(organizationCode)
	}

	removeOrganization() {
		const user = new UserRepository()
		user.setOrganizations({})
		user.setOrganizationCode("")
		user.setTeamshareOrganizations([])
		user.setTeamshareOrganizationCode("")

		userStore.user.setOrganizationCode("")
		userStore.user.setTeamshareOrganizationCode("")
		userStore.user.setOrganizations({})
		userStore.user.setTeamshareOrganizations([])
	}

	/**
	 * @description Add account
	 * @param userAccount
	 */
	setAccount(userAccount: User.UserAccount) {
		// Persistent data sync
		const account = new AccountRepository()
		account.put(userAccount).catch(console.error)

		// Memory state sync
		userStore.account.setAccount(userAccount)

		// Broadcast account addition
		BroadcastChannelSender.addAccount(userAccount)
	}

	/**
	 * FIXME: On error, restore current account
	 * @description Switch account
	 * @param unionId
	 * @param delightfulOrganizationCode
	 */
	switchAccount = async (
		unionId: string,
		delightful_user_id: string,
		delightful_organization_code: string,
	) => {
		const { accounts } = userStore.account
		const account = accounts.find((o) => o.delightful_id === unionId)
		if (account) {
			const delightfulOrgSyncStep = this.service
				.get<LoginService>("loginService")
				.delightfulOrganizationSyncStep(account?.deployCode as string)

			this.setAuthorization(account?.access_token)

			if (delightful_user_id && delightful_organization_code) {
				// Sync user's organization
				this.setDelightfulOrganizationCode(delightful_organization_code)
				// Step 1: Environment sync
				await this.service
					.get<LoginService>("loginService")
					.getClusterConfig(account.deployCode)
				// Step 2: Sync user information
				await this.service
					.get<LoginService>("loginService")
					.fetchUserInfoStep(delightful_user_id)
				// Step 3: Fetch organization hierarchy in delightful
				const { delightfulOrganizationMap } = await delightfulOrgSyncStep({
					access_token: account.access_token,
				} as Login.UserLoginsResponse)
				// Step 4: Organization sync (fetch then sync)
				const response = await this.service
					.get<LoginService>("loginService")
					.organizationFetchStep({
						delightfulOrganizationMap,
						access_token: account.access_token,
						deployCode: account.deployCode,
					})
				await this.service.get<LoginService>("loginService").organizationSyncStep(response)

				await this.wsLogin({ showLoginLoading: true })
			}
		}
	}

	/**
	 * @description Account removal
	 * @param unionId
	 */
	deleteAccount = async (unionId?: string) => {
		const allClean = async () => {
			MessageService.destroy()

			// remove the current token
			const user = new UserRepository()
			await user.setAuthorization("")
			userStore.user.setAuthorization(null)

			this.setUserInfo(null)
			this.removeOrganization()

			const account = new AccountRepository()
			await account.clear()
		}
		if (unionId) {
			// Remove persistent data
			const account = new AccountRepository()
			await account.delete(unionId)

			userStore.account.deleteAccount(unionId)

			if (userStore.account.accounts.length === 0) {
				await allClean()
			}
		} else {
			await allClean()
		}
	}

	/**
	 * @description Account sync (sync organizations, user info, etc.)
	 */
	async fetchAccount() {
		const { accounts } = userStore.account

		const task = []

		const loginService = this.service.get<LoginService>("loginService")

		for (let i = 0, len = accounts.length; i < len; i += 1) {
			const account = accounts[i]

			const job = () => {
				// eslint-disable-next-line no-async-promise-executor
				return new Promise(async (resolve) => {
					// Environment sync
					await loginService.getClusterConfig(account?.deployCode)
					// Delightful organization sync
					const delightfulOrgSyncStep = loginService.delightfulOrganizationSyncStep(
						account?.deployCode,
					)
					const { delightfulOrganizationMap } = await delightfulOrgSyncStep({
						access_token: account?.access_token,
					} as Login.UserLoginsResponse)
					// Teamshare organization sync
					const { organizations } = await loginService.organizationFetchStep({
						delightfulOrganizationMap,
						access_token: account.access_token,
						deployCode: account?.deployCode,
					})

					userStore.account.updateAccount(account.delightful_id, {
						...account,
						organizations: Object.values(delightfulOrganizationMap),
						teamshareOrganizations: organizations || [],
					})

					resolve({ organizations, delightfulOrganizationMap })
				})
			}

			task.push(job())
		}

		Promise.all(task)
			.then((response) => {
				console.log("account update success", response)
			})
			.catch(console.error)
	}

	wsLogin({ showLoginLoading = true }: { showLoginLoading?: boolean } = {}) {
		const { authorization } = userStore.user

		if (!authorization) {
			throw new Error("authorization or organization_code is required")
		}

		// If current authorization equals lastLogin.authorization, return lastLogin's promise
		if (authorization === this.lastLogin?.authorization) {
			console.log("authorization is the same, returning lastLogin's promise", this.lastLogin)
			return this.lastLogin.promise
		}

		this.lastLogin = {
			authorization,
			promise: ChatApi.login(authorization)
				.then(async (res) => {
					userStore.user.setUserInfo(res.data.user)
					console.log("ws login successful", res)
					// Switch chat data
					await this.loadUserInfo(res.data.user, { showSwitchLoading: showLoginLoading })
				})
				.catch(async (err) => {
					console.log("ws login failed", err)
					if (err.code === 3103) {
						console.log(err)
						// accountBusiness.accountLogout() -> this.deleteAccount()
						await this.deleteAccount()
					}
					if (this.lastLogin?.authorization === authorization) {
						this.lastLogin.promise = Promise.reject(err)
					}
				})
				.finally(() => {
					console.log("ws login ended")
					if (this.lastLogin?.authorization === authorization) {
						this.lastLogin.promise = Promise.resolve()
					}
				}),
		}

		return this.lastLogin.promise
	}

	/*
	 * @description Clear lastLogin
	 */
	clearLastLogin() {
		this.lastLogin = null
	}
	/**
	 * @description Switch user
	 * @param delightfulUser
	 * @param showSwitchLoading
	 */
	async loadUserInfo(
		delightfulUser: User.UserInfo,
		{ showSwitchLoading = true }: { showSwitchLoading?: boolean } = {},
	) {
		try {
			const delightfulId = delightfulUser.delightful_id
			const userId = delightfulUser.user_id

			console.log("Switching account", delightfulId)
			if (showSwitchLoading) interfaceStore.setIsSwitchingOrganization(true)

			// If current account ID equals the provided account ID, do not switch
			chatDb.switchDb(delightfulId)
			ChatFileService.init()
			EditorDraftService.initDrafts()

			const db = initDataContextDb(delightfulId, userId)
			await userInfoService.loadData(db)
			await groupInfoService.loadData(db)

			// Check render sequence IDs for all organizations
			MessageSeqIdService.checkAllOrganizationRenderSeqId()

			// For reconnection cases, do not reset the view
			if (showSwitchLoading) {
				// Reset message data views
				conversationService.reset() // Switch to empty conversation
				MessageService.reset()
			}

			/** If first load, pull messages */
			if (!MessageSeqIdService.getGlobalPullSeqId()) {
				await MessageService.pullMessageOnFirstLoad(
					delightfulUser.delightful_id,
					delightfulUser.organization_code,
				)
			} else {
				await conversationService.init(
					delightfulUser.delightful_id,
					delightfulUser.organization_code,
					delightfulUser,
				)
				// Pull offline messages (only applies info of this organization internally)
				MessageService.pullOfflineMessages()
			}

			// this.conversationGroupBusiness.initConversationGroups(
			// 	delightfulUser.delightful_id,
			// 	delightfulUser.organization_code,
			// )

			/** Setup message-pulling loop */
			MessageService.init()
			// this.messagePullBusiness.registerMessagePullLoop()
			if (showSwitchLoading) interfaceStore.setIsSwitchingOrganization(false)
		} catch (error) {
			console.error("Switch account failed", error)
		}
	}
}
