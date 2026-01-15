import OrganizationDotsStore from "@/opensource/stores/chatNew/dots/OrganizationDotsStore"
import { platformKey } from "@/utils/storage"
import { userStore } from "@/opensource/models/user"
import { observe } from "mobx"

class OrganizationDotsDbService {
	delightfulId: string | undefined

	constructor() {
		// Observe user info changes
		observe(userStore.user, "userInfo", (change) => {
			const newUserInfo = change.newValue
			if (newUserInfo?.delightful_id && this.delightfulId !== newUserInfo.delightful_id) {
				this.delightfulId = newUserInfo.delightful_id
				// Load persisted data
				OrganizationDotsStore.reset(this.getPersistenceData(), this.getDotSeqIdData())
			}
		})
	}

	get dot_seqid_key() {
		return platformKey(`organization_dots_seqid/${this.delightfulId}`)
	}

	/**
	 * Persistence data key
	 */
	get persistence_key() {
		return platformKey(`organization_dots/${this.delightfulId}`)
	}

	/**
	 * Get persisted data
	 * @returns Persisted data
	 */
	getPersistenceData() {
		const data = localStorage.getItem(this.persistence_key)
		if (!data) return {}
		return JSON.parse(data)
	}

	/**
	 * Set persisted data
	 * @param data Persisted data
	 */
	setPersistenceData(data: Record<string, number>) {
		localStorage.setItem(this.persistence_key, JSON.stringify(data))
	}

	/**
	 * Get last-updated red-dot seq_id data
	 * @returns dot_seqid data
	 */
	getDotSeqIdData() {
		const data = localStorage.getItem(this.dot_seqid_key)
		if (!data) return {}
		return JSON.parse(data)
	}

	/**
	 * Set last-updated red-dot seq_id data
	 * @param data seq_id data
	 */
	setDotSeqIdData(data: Record<string, string>) {
		localStorage.setItem(this.dot_seqid_key, JSON.stringify(data))
	}
}

export default new OrganizationDotsDbService()
