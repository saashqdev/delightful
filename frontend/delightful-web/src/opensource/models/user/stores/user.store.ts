import { makeAutoObservable } from "mobx"
import type { User } from "@/types/user"
import { platformKey } from "@/utils/storage"
import { keyBy } from "lodash-es"

export class UserStore {
	authorization: string | null = localStorage.getItem(platformKey("store:authentication"))

	userInfo: User.UserInfo | null = null

	organizations: User.UserOrganization[] = []

	/** magic organization code */
	organizationCode: string = ""

	/** teamshare organization code */
	teamshareOrganizationCode: string = ""

	magicOrganizationMap: Record<string, User.DelightfulOrganization> = {}

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
		this.magicOrganizationMap = organizations
	}

	setTeamshareOrganizations = (organizations: User.UserOrganization[]) => {
		this.organizations = organizations
	}

	// Sync magic organization with teamshare and vice versa

	/**
	 * @description Get organization object by magic organization code
	 * @param {string} organizationCode Organization code in the magic system
	 */
	getOrganizationByDelightful = (organizationCode: string) => {
		const { organizations, magicOrganizationMap } = this
		const orgMap = keyBy(organizations, "organization_code")
		// Get teamshare organization code
		return orgMap?.[
			magicOrganizationMap?.[organizationCode]?.third_platform_organization_code ?? ""
		]
	}

	/**
	 * @description Get organization info for current account (non-React usage)
	 * @return {User.UserOrganization | undefined}
	 */
	getOrganization = (): User.UserOrganization | null => {
		const { organizations, organizationCode, magicOrganizationMap, teamshareOrganizationCode } =
			this
		// Build organization map
		const orgMap = keyBy(organizations, "organization_code")
		let org = null
		// Try fetching org by magic organization code
		if (organizationCode) {
			org =
				orgMap?.[
					magicOrganizationMap?.[organizationCode]?.third_platform_organization_code ?? ""
				]
		}
		if (!org && teamshareOrganizationCode) {
			org = orgMap?.[teamshareOrganizationCode]
		}
		return org
	}
}

export const userStore = new UserStore()
