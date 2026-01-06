import { makeAutoObservable } from "mobx"

class OrganizationDotsStore {
	dots: Record<string, number> = {}
	dotSeqId: Record<string, string> = {}

	constructor() {
		makeAutoObservable(this, {}, { autoBind: true })
	}

	/**
	 * Reset dots and sequence IDs
	 */
	reset(data: Record<string, number>, seqId: Record<string, string>) {
		this.dots = data
		this.dotSeqId = seqId
	}

	/**
	 * Set organization unread dots
	 * @param organizationCode Organization code
	 * @param dots Dot count
	 */
	setOrganizationDots(organizationCode: string, dots: number) {
		console.log("setOrganizationDots ====> ", organizationCode, dots)
		this.dots[organizationCode] = dots
	}

	/**
	 * Get organization unread dots
	 * @param organizationCode Organization code
	 * @returns Dot count
	 */
	getOrganizationDots(organizationCode: string) {
		return this.dots[organizationCode] || 0
	}

	/**
	 * Clear organization unread dots
	 * @param organizationCode Organization code
	 */
	clearOrganizationDots(organizationCode: string) {
		delete this.dots[organizationCode]
	}

	/**
	 * Clear all organization unread dots
	 */
	clearAllOrganizationDots() {
		this.dots = {}
	}

	/**
	 * Set organization dot seq_id
	 * @param organizationCode Organization code
	 * @param seqId Sequence ID
	 */
	setOrganizationDotSeqId(organizationCode: string, seqId: string) {
		this.dotSeqId[organizationCode] = seqId
	}

	/**
	 * Get organization dot seq_id
	 * @param organizationCode Organization code
	 * @returns Sequence ID
	 */
	getOrganizationDotSeqId(organizationCode: string) {
		return this.dotSeqId[organizationCode] || ""
	}

	/**
	 * Clear organization dot seq_id
	 * @param organizationCode Organization code
	 */
	clearOrganizationDotSeqId(organizationCode: string) {
		delete this.dotSeqId[organizationCode]
	}

	/**
	 * Clear all organization dot seq_ids
	 */
	clearAllOrganizationDotSeqId() {
		this.dotSeqId = {}
	}
}

export default new OrganizationDotsStore()
