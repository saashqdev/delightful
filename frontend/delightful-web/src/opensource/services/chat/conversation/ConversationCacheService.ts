import type { ConversationGroupKey } from "@/const/chat"
import { platformKey, unwrapperVersion, wrapperVersion } from "@/utils/storage"

class ConversationCacheServices {
	static version = 0

	/**
	 * 获取侧边栏会话组缓存key
	 * @returns 缓存key
	 */
	static getConversationSiderbarGroupsKey(delightfulId: string, organizationCode: string) {
		return platformKey(`conversation_siderbar/${delightfulId}/${organizationCode}`)
	}

	/**
	 * 缓存侧边栏会话组
	 */
	static cacheConversationSiderbarGroups(
		delightfulId: string | undefined,
		organizationCode: string | undefined,
		conversationSiderbarGroups: Record<ConversationGroupKey, string[]>,
	) {
		requestIdleCallback(() => {
			if (!delightfulId || !organizationCode) return
			const key = ConversationCacheServices.getConversationSiderbarGroupsKey(
				delightfulId,
				organizationCode,
			)
			const value = JSON.stringify(
				wrapperVersion(conversationSiderbarGroups, ConversationCacheServices.version),
			)
			localStorage.setItem(key, value)
		})
	}

	/**
	 * 获取缓存的侧边栏会话组
	 * @param delightfulId 魔法ID
	 * @param organizationCode 组织编码
	 * @returns 缓存的侧边栏会话组
	 */
	static getCacheConversationSiderbarGroups(delightfulId: string, organizationCode: string) {
		if (!delightfulId || !organizationCode) return undefined
		const key = ConversationCacheServices.getConversationSiderbarGroupsKey(
			delightfulId,
			organizationCode,
		)
		const value = localStorage.getItem(key)
		return value ? unwrapperVersion(JSON.parse(value)) : undefined
	}
}

export default ConversationCacheServices
