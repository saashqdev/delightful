import localstorage from "@/utils/localstorage"
import { bigNumCompare } from "@/utils/string"
import { userStore } from "@/opensource/models/user"
import { platformKey } from "@/utils/storage"

class MessageSeqIdService {
	seqIdMap: Record<string, string | Record<string, string>> = {}

	get delightfulId() {
		return userStore.user.userInfo?.delightful_id
	}

	/**
	 * Get storage key for global last pulled message sequence number
	 */
	private get globalPullSeqIdKey() {
		return platformKey(`pullLastSeqId/${this.delightfulId}`)
	}

	/**
	 * Get storage key for conversation-level last pulled message sequence number
	 */
	private get conversationPullSeqIdKey() {
		return platformKey(`conversationPullSeqId/${this.delightfulId}`)
	}

	/**
	 * Get storage key for organization-level last rendered message sequence number
	 */
	private get renderLastSeqIdKey() {
		return platformKey(`renderLastSeqId/${this.delightfulId}`)
	}

	/**
	 * Get storage key for conversation-level last pulled message sequence number
	 */
	private get conversationdRenerSeqIdKey() {
		return platformKey(`conversationRenderSeqId/${this.delightfulId}`)
	}

	// ========== Global pull sequence number management ==========
	private getGlobalPullSeqIdFromLocalStorage(): string {
		return localstorage.get(this.globalPullSeqIdKey) ?? ""
	}

	private getGlobalPullSeqIdFromMemory(): string {
		return this.seqIdMap[this.globalPullSeqIdKey] as string
	}

	/**
	 * Get global last pulled sequence number
	 */
	public getGlobalPullSeqId(): string {
		// Get from memory first
		if (this.seqIdMap[this.globalPullSeqIdKey]) {
			return this.getGlobalPullSeqIdFromMemory()
		}
		// Get from local storage
		return this.getGlobalPullSeqIdFromLocalStorage()
	}

	/**
	 * Update global last pulled sequence number
	 */
	public updateGlobalPullSeqId(seqId: string): void {
		// Check seqId
		const globalSeqId = this.getGlobalPullSeqIdFromLocalStorage()
		if (bigNumCompare(seqId, globalSeqId) > 0) {
			// Update local storage
			localstorage.set(this.globalPullSeqIdKey, seqId)
		}

		const sessionSeqId = this.getGlobalPullSeqIdFromMemory()
		if (!sessionSeqId || (sessionSeqId && bigNumCompare(seqId, sessionSeqId) > 0)) {
			// Update memory
			this.seqIdMap[this.globalPullSeqIdKey] = seqId
		}
	}

	// ========== Conversation-level pull sequence number management ==========
	private getConversationPullSeqIdFromLocalStorage(): Record<string, string> {
		return localstorage.get(this.conversationPullSeqIdKey, true) ?? {}
	}

	private getConversationPullSeqIdFromMemory(): Record<string, string> {
		if (!this.seqIdMap[this.conversationPullSeqIdKey]) {
			this.seqIdMap[this.conversationPullSeqIdKey] = {}
		}
		return this.seqIdMap[this.conversationPullSeqIdKey] as Record<string, string>
	}

	/**
	 * Get pull sequence number mapping for all conversations
	 */
	public getConversationPullSeqIds(): Record<string, string> {
		if (this.seqIdMap[this.conversationPullSeqIdKey]) {
			return this.getConversationPullSeqIdFromMemory()
		}
		return this.getConversationPullSeqIdFromLocalStorage()
	}

	/**
	 * Get pull sequence number for specified conversation
	 */
	public getConversationPullSeqId(conversationId: string): string {
		if (this.seqIdMap[this.conversationPullSeqIdKey]) {
			const seqIds = this.getConversationPullSeqIdFromMemory()
			return seqIds[conversationId] ?? ""
		}

		return this.getConversationPullSeqIds()[conversationId] ?? ""
	}

	// /**
	//  * Set pull sequence number mapping for all conversations
	//  */
	// public setConversationPullSeqIds(seqIds: Record<string, string>): void {
	// 	// Check seqIds
	// 	const conversationSeqIds = this.getConversationPullSeqIdFromLocalStorage()
	// 	Object.entries(seqIds).forEach(([conversationId, seqId]) => {
	// 		if (bigNumCompare(seqId, conversationSeqIds[conversationId] ?? "0") > 0) {
	// 			conversationSeqIds[conversationId] = seqId
	// 		}
	// 	})

	// 	// Update local storage
	// 	localstorage.set(this.conversationPullSeqIdKey, conversationSeqIds)

	// 	const sessionSeqIds = this.getConversationPullSeqIdFromMemory()
	// 	Object.entries(seqIds).forEach(([conversationId, seqId]) => {
	// 		if (bigNumCompare(seqId, sessionSeqIds[conversationId] ?? "0") > 0) {
	// 			sessionSeqIds[conversationId] = seqId
	// 		}
	// 	})
	// 	this.seqIdMap[this.conversationPullSeqIdKey] = sessionSeqIds
	// }
	private setGlobalConversationPullSeqIds(seqIds: Record<string, string>): void {
		// Update global cache
		localstorage.set(this.conversationPullSeqIdKey, seqIds)
	}

	private setSessionConversationPullSeqIds(seqIds: Record<string, string>): void {
		// Update temporary cache
		this.seqIdMap[this.conversationPullSeqIdKey] = seqIds
	}

	/**
	 * Update pull sequence number for specified conversation
	 */
	public updateConversationPullSeqId(conversationId: string, seqId: string): void {
		// Check seqId, update separately
		const globalSeqIds = this.getConversationPullSeqIdFromLocalStorage()

		if (bigNumCompare(seqId, globalSeqIds[conversationId] ?? "0") > 0) {
			globalSeqIds[conversationId] = seqId
			this.setGlobalConversationPullSeqIds(globalSeqIds)
		}

		const sessionSeqIds = this.getConversationPullSeqIdFromMemory()
		if (bigNumCompare(seqId, sessionSeqIds[conversationId] ?? "0") > 0) {
			sessionSeqIds[conversationId] = seqId
			this.setSessionConversationPullSeqIds(sessionSeqIds)
		}
	}

	/**
	 * Delete pull sequence number for specified conversation
	 */
	public deleteConversationPullSeqId(conversationId: string): void {
		// Update global cache
		const globalSeqIds = this.getConversationPullSeqIdFromLocalStorage()
		delete globalSeqIds[conversationId]
		this.setGlobalConversationPullSeqIds(globalSeqIds)

		// Update temporary cache
		const sessionSeqIds = this.getConversationPullSeqIdFromMemory()
		delete sessionSeqIds[conversationId]
		this.setSessionConversationPullSeqIds(sessionSeqIds)
	}

	// ========== Organization-level render sequence number management ==========
	private getOrganizationRenderObjectFromLocalStorage(): Record<string, string> {
		return JSON.parse(localstorage.get(this.renderLastSeqIdKey) ?? "{}")
	}

	private getOrganizationRenderObjectFromMemory(): Record<string, string> {
		if (!this.seqIdMap[this.renderLastSeqIdKey]) {
			this.seqIdMap[this.renderLastSeqIdKey] = {}
		}
		return this.seqIdMap[this.renderLastSeqIdKey] as Record<string, string>
	}

	/**
	 * Get organization-level render object
	 */
	public getOrganizationRenderObject(): Record<string, string> {
		if (this.seqIdMap[this.renderLastSeqIdKey]) {
			return this.getOrganizationRenderObjectFromMemory()
		}
		return this.getOrganizationRenderObjectFromLocalStorage()
	}

	private setGlobalOrganizationRenderObject(object: Record<string, string>): void {
		localstorage.set(this.renderLastSeqIdKey, object)
	}

	private setSessionOrganizationRenderObject(object: Record<string, string>): void {
		this.seqIdMap[this.renderLastSeqIdKey] = object
	}

	/**
	 * Get organization-level render sequence number
	 */
	public getOrganizationRenderSeqId(organization_code: string) {
		if (!organization_code) {
			return ""
		}

		if (this.seqIdMap[this.renderLastSeqIdKey]) {
			return this.getOrganizationRenderObject()[organization_code] ?? ""
		}

		return this.getOrganizationRenderObject()[organization_code] ?? ""
	}

	public updateOrganizationRenderSeqId(organization_code: string, seq_id: string): void {
		const globalSeqIds = this.getOrganizationRenderObjectFromLocalStorage()

		if (bigNumCompare(seq_id, globalSeqIds[organization_code] ?? "0") > 0) {
			globalSeqIds[organization_code] = seq_id
			this.setGlobalOrganizationRenderObject(globalSeqIds)
		}

		const sessionSeqIds = this.getOrganizationRenderObjectFromMemory()
		if (bigNumCompare(seq_id, sessionSeqIds[organization_code] ?? "0") > 0) {
			sessionSeqIds[organization_code] = seq_id
			this.setSessionOrganizationRenderObject(sessionSeqIds)
		}
	}

	// ========== Conversation-level render sequence number management ==========
	private getConversationRenderSeqIdFromLocalStorage(): Record<string, string> {
		return localstorage.get(this.conversationdRenerSeqIdKey, true) ?? {}
	}

	private getConversationRenderSeqIdFromMemory(): Record<string, string> {
		if (!this.seqIdMap[this.conversationdRenerSeqIdKey]) {
			this.seqIdMap[this.conversationdRenerSeqIdKey] = {}
		}
		return this.seqIdMap[this.conversationdRenerSeqIdKey] as Record<string, string>
	}

	/**
	 * Get render sequence number mapping for all conversations
	 */
	public getConversationRenderSeqIds(): Record<string, string> {
		if (this.seqIdMap[this.conversationdRenerSeqIdKey]) {
			return this.getConversationRenderSeqIdFromMemory()
		}
		return this.getConversationRenderSeqIdFromLocalStorage()
	}

	/**
	 * Get render sequence number for specified conversation
	 */
	public getConversationRenderSeqId(conversationId: string): string {
		if (this.seqIdMap[this.conversationdRenerSeqIdKey]) {
			return this.getConversationRenderSeqIdFromMemory()[conversationId] ?? ""
		}

		return this.getConversationRenderSeqIds()[conversationId] ?? ""
	}

	private setGlobalConversationRenderSeqIds(seqIds: Record<string, string>): void {
		localstorage.set(this.conversationdRenerSeqIdKey, seqIds)
	}

	private setSessionConversationRenderSeqIds(seqIds: Record<string, string>): void {
		this.seqIdMap[this.conversationdRenerSeqIdKey] = seqIds
	}

	/**
	 * Update render sequence number for specified conversation
	 */
	public updateConversationRenderSeqId(conversationId: string, seqId: string): void {
		const globalSeqIds = this.getConversationRenderSeqIdFromLocalStorage()
		if (bigNumCompare(seqId, globalSeqIds[conversationId] ?? "0") > 0) {
			globalSeqIds[conversationId] = seqId
			this.setGlobalConversationRenderSeqIds(globalSeqIds)
		}

		const sessionSeqIds = this.getConversationRenderSeqIdFromMemory()
		if (bigNumCompare(seqId, sessionSeqIds[conversationId] ?? "0") > 0) {
			sessionSeqIds[conversationId] = seqId
			this.setSessionConversationRenderSeqIds(sessionSeqIds)
		}
	}

	/**
	 * Delete render sequence number for specified conversation
	 */
	public deleteConversationRenderSeqId(conversationId: string): void {
		const globalSeqIds = this.getConversationRenderSeqIdFromLocalStorage()
		delete globalSeqIds[conversationId]
		this.setGlobalConversationRenderSeqIds(globalSeqIds)

		const sessionSeqIds = this.getConversationRenderSeqIdFromMemory()
		delete sessionSeqIds[conversationId]
		this.setSessionConversationRenderSeqIds(sessionSeqIds)
	}

	// ========== Batch operations ==========
	/**
	 * Clear all sequence numbers for specified conversation
	 */
	public clearConversationSeqIds(conversationId: string): void {
		this.deleteConversationPullSeqId(conversationId)
		this.deleteConversationRenderSeqId(conversationId)
	}

	/**
	 * Clear all sequence numbers
	 */
	public clearAllSeqIds(): void {
		localstorage.remove(this.globalPullSeqIdKey)
		localstorage.remove(this.conversationPullSeqIdKey)
		localstorage.remove(this.renderLastSeqIdKey)
	}

	/**
	 * Initialize render sequence numbers for all organizations
	 */
	public initAllOrganizationRenderSeqId(seqId: string) {
		const allOrganization = userStore.user.delightfulOrganizationMap
		this.setGlobalOrganizationRenderObject(
			Object.values(allOrganization).reduce((prev, current) => {
				prev[current.delightful_organization_code] = seqId
				return prev
			}, {} as Record<string, string>),
		)
		this.setSessionOrganizationRenderObject(
			Object.values(allOrganization).reduce((prev, current) => {
				prev[current.delightful_organization_code] = seqId
				return prev
			}, {} as Record<string, string>),
		)
	}

	/**
	 * Check render sequence numbers for all organizations (avoid missing render sequence numbers when new organizations are added)
	 */
	checkAllOrganizationRenderSeqId() {
		const allOrganization = userStore.user.delightfulOrganizationMap
		const allOrganizationRenderSeqId = this.getOrganizationRenderObject()

		Object.values(allOrganization).forEach((organization) => {
			if (!allOrganizationRenderSeqId[organization.delightful_organization_code]) {
				allOrganizationRenderSeqId[organization.delightful_organization_code] =
					this.getGlobalPullSeqId()
			}
		})

		this.setGlobalOrganizationRenderObject(allOrganizationRenderSeqId)
		this.setSessionOrganizationRenderObject(allOrganizationRenderSeqId)
	}
}

export default new MessageSeqIdService()
