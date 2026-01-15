import { useMemoizedFn } from "ahooks"
import { useClusterCode } from "@/opensource/providers/ClusterProvider"
import { userStore } from "@/opensource/models/user"
import { userService } from "@/services"
import { BroadcastChannelSender } from "@/opensource/broadcastChannel"

export function useAccount() {
	const { setClusterCode } = useClusterCode()

	/**
	 * @description Account switch
	 * @param {string} delightfulId Unique ID in delightful ecosystem
	 */
	const accountSwitch = useMemoizedFn(
		async (
			unionId: string,
			delightful_user_id: string,
			delightful_organization_code: string,
		) => {
			const { accounts } = userStore.account
			const account = accounts.find((o) => o.delightful_id === unionId)
			if (account) {
				setClusterCode(account?.deployCode)
			}
			await userService.switchAccount(
				unionId,
				delightful_user_id,
				delightful_organization_code,
			)
			/** Broadcast switch account */
			BroadcastChannelSender.switchAccount({
				delightfulId: unionId,
				delightfulUserId: delightful_user_id,
				delightfulOrganizationCode: delightful_organization_code,
			})
		},
	)

	/**
	 * @description Logout, not only destroy token but also remove account records from account management (compatible with specifying delightfulId account logout, if not passed, logout current account)
	 */
	const accountLogout = useMemoizedFn(async (delightfulId?: string) => {
		await userService.deleteAccount(delightfulId)
	})

	/** Account initialization, need to refetch all organizations of accounts that have logged in during app initialization */
	const accountFetch = useMemoizedFn(async () => {
		await userService.fetchAccount()
	})

	return {
		accountLogout,
		accountSwitch,
		accountFetch,
	}
}
