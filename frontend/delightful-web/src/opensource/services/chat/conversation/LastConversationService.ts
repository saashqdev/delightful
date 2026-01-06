import { platformKey } from "@/utils/storage"

class LastConversationService {
	/**
	 * 组织编码 -> 会话ID
	 */
	private lastConversationMap: Map<string, string> = new Map()

	/**
	 * 空闲回调
	 */
	private idleCallback: number | undefined

	get cacheKey() {
		return platformKey("lastConversation")
	}

	constructor() {
		this.lastConversationMap = new Map(JSON.parse(localStorage.getItem(this.cacheKey) || "[]"))
	}

	/**
	 * 缓存到本地存储
	 */
	cacheToLocalStorage() {
		localStorage.setItem(
			this.cacheKey,
			JSON.stringify(Array.from(this.lastConversationMap.entries())),
		)
	}

	/**
	 * 设置会话ID
	 */
	setLastConversation(
		delightfulId: string | undefined,
		organizationCode: string | undefined,
		conversationId: string | undefined,
	) {
		if (!delightfulId || !organizationCode || !conversationId) {
			return
		}
		this.lastConversationMap.set(`${delightfulId}/${organizationCode}`, conversationId)
		if (this.idleCallback) {
			cancelIdleCallback(this.idleCallback)
		}
		this.idleCallback = requestIdleCallback(() => {
			this.cacheToLocalStorage()
			this.idleCallback = undefined
		})
	}

	/**
	 * 获取会话ID
	 */
	getLastConversation(delightfulId?: string, organizationCode?: string) {
		if (!delightfulId || !organizationCode) {
			return undefined
		}
		return this.lastConversationMap.get(`${delightfulId}/${organizationCode}`)
	}
}

export default new LastConversationService()
