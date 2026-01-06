import { RoutePath } from "@/const/routes"
import { userStore } from "@/opensource/models/user"
import { LoginValueKey } from "@/opensource/pages/login/constants"
import { userService } from "@/services"
import { User } from "@/types/user"
import { cloneDeep } from "lodash-es"

const UserDispatchService = {
	/**
	 * @description Switch organization
	 * @param data
	 */
	switchOrganization: async (data: {
		userInfo: User.UserInfo
		delightfulOrganizationCode: string
	}) => {
		const oldUserInfo = cloneDeep(userStore.user.userInfo)
		const oldDelightfulOrganizationCode = userStore.user.organizationCode

		try {
			userService.setUserInfo(data.userInfo)
			userService.setDelightfulOrganizationCode(data.delightfulOrganizationCode)
			await userService.loadUserInfo(data.userInfo, { showSwitchLoading: true })
		} catch (err) {
			// Switch failed, recover current organization
			userService.setUserInfo(oldUserInfo)
			userService.setDelightfulOrganizationCode(oldDelightfulOrganizationCode)
		}
	},

	/**
	 * @description Switch account
	 * @param data
	 */
	switchAccount: async (data: {
		delightfulId: string
		delightfulUserId: string
		delightfulOrganizationCode: string
	}) => {
		await userService.switchAccount(data.delightfulId, data.delightfulUserId, data.delightfulOrganizationCode)
	},

	/**
	 * @description Add account
	 * @param data
	 */
	addAccount: async (data: { userAccount: User.UserAccount }) => {
		// If current page is login page, refresh page
		if (location.pathname === RoutePath.Login) {
			const url = new URL(window.location.href)
			const { searchParams } = url
			/** From redirect URL, need to consider if external site requires it */
			const redirectURI = searchParams.get(LoginValueKey.REDIRECT_URL)
			if (redirectURI) {
				window.location.assign(decodeURIComponent(redirectURI))
			} else {
				window.location.assign(RoutePath.Chat)
			}
		} else {
			// Sync in-memory state
			userStore.account.setAccount(data.userAccount)

			await userService.switchAccount(
				data.userAccount.delightful_id,
				data.userAccount.delightful_user_id,
				data.userAccount.organizationCode,
			)
		}
	},

	/**
	 * @description Delete account
	 * @param data
	 */
	deleteAccount: async (data: { delightfulId?: string; navigateToLogin?: boolean }) => {
		userService.deleteAccount(data.delightfulId)
		if (data.navigateToLogin) {
			window.location.assign(RoutePath.Login)
		}
	},
}

export default UserDispatchService
