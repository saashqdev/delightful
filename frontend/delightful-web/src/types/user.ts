export const enum UserType {
	AI = 0,
	Normal = 1,
}

/**
 * User status
 */
export const enum UserStatus {
	// Disabled
	disable = 0,
	// Normal
	normal = 1,
}

export namespace User {
	/** Delightful ecosystem organization (organization info needs to be mapped with Teamshare) */
	export interface DelightfulOrganization {
		/** Delightful user UnionID (unique across entire Delightful ecosystem) */
		delightful_id: string
		/** Delightful organization code */
		delightful_organization_code: string
		/** Delightful user OpenId (unique within current organization) */
		delightful_user_id: string
		/** Organization logo */
		organization_name: string
		/** Organization name */
		organization_logo: string | null
		/** Third-party platform organization code */
		third_platform_organization_code: string
		/** Third-party platform user ID */
		third_platform_user_id: string
		/** Third-party platform type */
		third_platform_type: string | null
	}

	/** Teamshare account organization */
	export interface UserOrganization {
		id: string
		member_id: string
		platform_type: number
		real_name: string
		avatar: string
		organization_code: string
		organization_name: string
		organization_logo: {
			url: string
			key: string
			name: string
			uid: string
		}[]
		is_admin: boolean
		is_application_admin: boolean
		is_complete_info: boolean
		state_code: string
		identifications: string[]
	}

	/** User account (supports multiple environments: each account corresponds to one environment) */
	export interface UserAccount {
		/** Current environment */
		deployCode: string
		/** Delightful user UnionID (unique across entire Delightful ecosystem) */
		delightful_id: string
		/** Delightful user OpenId (unique within current organization) */
		delightful_user_id: string
		/** User name */
		nickname: string
		/** Current user organization */
		organizationCode: string
		/** User avatar */
		avatar: string
		/** User token */
		access_token: string
		organizations: Array<DelightfulOrganization>
		teamshareOrganizations: Array<UserOrganization>
	}

	export interface UserInfo {
		delightful_id: string
		user_id: string
		status: string | number
		nickname: string
		avatar: string
		organization_code: string
		phone?: string
	}

	/** Login device */
	export interface UserDeviceInfo {
		id: number
		device_id: string
		os: string
		os_version: string
		device_name: string
		user_id: number
		online: 0 | 1
		created_at: string
		updated_at: string
	}
}
