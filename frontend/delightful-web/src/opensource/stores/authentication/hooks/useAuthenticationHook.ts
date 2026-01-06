import { useMemoizedFn } from "ahooks"
import { useClusterCode } from "@/opensource/providers/ClusterProvider"
import { userStore } from "@/opensource/models/user"
import { userService } from "@/services"
import { BroadcastChannelSender } from "@/opensource/broadcastChannel"

export function useAccount() {
	const { setClusterCode } = useClusterCode()

	/**
	 * @description 账号切换
	 * @param {string} delightfulId delightful生态下的唯一Id
	 */
	const accountSwitch = useMemoizedFn(
		async (unionId: string, delightful_user_id: string, delightful_organization_code: string) => {
			const { accounts } = userStore.account
			const account = accounts.find((o) => o.delightful_id === unionId)
			if (account) {
				setClusterCode(account?.deployCode)
			}
			await userService.switchAccount(unionId, delightful_user_id, delightful_organization_code)
			/** 广播切换账号 */
			BroadcastChannelSender.switchAccount({
				delightfulId: unionId,
				delightfulUserId: delightful_user_id,
				delightfulOrganizationCode: delightful_organization_code,
			})
		},
	)

	/**
	 * @description 退出登录，不仅要销毁token，还需要移除帐号管理中的记录 (兼容指定 delightfulId 账号退出，不传则退出当前账号)
	 */
	const accountLogout = useMemoizedFn(async (delightfulId?: string) => {
		await userService.deleteAccount(delightfulId)
	})

	/** 账号初始化，每次应用初始化时都需要重新获取所有登录过账号的组织 */
	const accountFetch = useMemoizedFn(async () => {
		await userService.fetchAccount()
	})

	return {
		accountLogout,
		accountSwitch,
		accountFetch,
	}
}
