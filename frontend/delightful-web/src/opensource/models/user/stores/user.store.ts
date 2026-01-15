import { makeAutoObservable } from "mobx"
import type { User } from "@/types/user"
import { platformKey } from "@/utils/storage"
import { keyBy } from "lodash-es"

export class UserStore {
	authorization: string | null = localStorage.getItem(platformKey("store:authentication"))

	userInfo: User.UserInfo | null = null

	organizations: User.UserOrganization[] = []

	/** delightful organization code */
	organizationCode: string = ""

	/** teamshare organization code */
	teamshareOrganizationCode: string = ""

	delightfulOrganizationMap: Record<string, User.DelightfulOrganization> = {}

	/** Whether current user is an admin */
	isAdmin: boolean = false

	constructor() {
		makeAutoObservable(this, {}, { autoBind: true })
	}

	setAuthorization = (authCode: string | null) => {
		this.authorization = authCode
	}

	setUserInfo = (userInfo: User.UserInfo | null) => {
		this.userInfo = userInfo
	}

	setOrganizationCode = (organizationCode: string) => {
		this.organizationCode = organizationCode
	}

	setTeamshareOrganizationCode = (teamshareOrganizationCode: string) => {
		this.teamshareOrganizationCode = teamshareOrganizationCode
	}

	setOrganizations = (organizations: Record<string, User.DelightfulOrganization>) => {
		this.delightfulOrganizationMap = organizations
	}

	setTeamshareOrganizations = (organizations: User.UserOrganization[]) => {
		this.organizations = organizations
	}

	// Sync delightful organization with teamshare and vice versa

	/**
	 * @description Get organization object by delightful organization code
	 * @param {string} organizationCode Organization code in the delightful system
	 */
	getOrganizationByDelightful = (organizationCode: string) => {
		const { organizations, delightfulOrganizationMap } = this
		const orgMap = keyBy(organizations, "organization_code")
		// Get teamshare organization code
		return orgMap?.[
			delightfulOrganizationMap?.[organizationCode]?.third_platform_organization_code ?? ""
		]
	}

	/**
	 * @description Get organization info for current account (non-React usage)
	 * @return {User.UserOrganization | undefined}
	 */
	getOrganization = (): User.UserOrganization | null => {
		const {
			organizations,
			organizationCode,
			delightfulOrganizationMap,
			teamshareOrganizationCode,
		} = this
		// Build organization map
		const orgMap = keyBy(organizations, "organization_code")
		let org = null
		// Try fetching org by delightful organization code
		if (organizationCode) {
			org =
				orgMap?.[
					delightfulOrganizationMap?.[organizationCode]
						?.third_platform_organization_code ?? ""
				]
		}
		if (!org && teamshareOrganizationCode) {
			org = orgMap?.[teamshareOrganizationCode]
		}
		return org
	}
}

export const userStore = new UserStore()
