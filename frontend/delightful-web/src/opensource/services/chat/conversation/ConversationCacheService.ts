import type { ConversationGroupKey } from "@/const/chat"
import { platformKey, unwrapperVersion, wrapperVersion } from "@/utils/storage"

class ConversationCacheServices {
	static version = 0

	/**
	 * Get sidebar conversation group cache key
	 * @returns Cache key
	 */
	static getConversationSiderbarGroupsKey(delightfulId: string, organizationCode: string) {
		return platformKey(`conversation_siderbar/${delightfulId}/${organizationCode}`)
	}

	/**
	 * Cache sidebar conversation group
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
	 * Get cached sidebar conversation group
	 * @param delightfulId Delightful ID
	 * @param organizationCode Organization code
	 * @returns Cached sidebar conversation group
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
