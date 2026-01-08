import { platformKey } from "@/utils/storage"

class LastConversationService {
	/**
	 * Organization code -> Conversation ID
	 */
	private lastConversationMap: Map<string, string> = new Map()

	/**
	 * Idle callback
	 */
	private idleCallback: number | undefined

	get cacheKey() {
		return platformKey("lastConversation")
	}

	constructor() {
		this.lastConversationMap = new Map(JSON.parse(localStorage.getItem(this.cacheKey) || "[]"))
	}

	/**
	 * Cache to local storage
	 */
	cacheToLocalStorage() {
		localStorage.setItem(
			this.cacheKey,
			JSON.stringify(Array.from(this.lastConversationMap.entries())),
		)
	}

	/**
	 * Set conversation ID
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
	 * Get conversation ID
	 */
	getLastConversation(delightfulId?: string, organizationCode?: string) {
		if (!delightfulId || !organizationCode) {
			return undefined
		}
		return this.lastConversationMap.get(`${delightfulId}/${organizationCode}`)
	}
}

export default new LastConversationService()
