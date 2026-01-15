import OrganizationDotsStore from "@/opensource/stores/chatNew/dots/OrganizationDotsStore"
import OrganizationDotsDbService from "./OrganizationDotsDbService"
import { bigNumCompare } from "@/utils/string"
import { BroadcastChannelSender } from "@/opensource/broadcastChannel"
import { userStore } from "@/opensource/models/user"

/**
 * Organization new message notification service
 */
class OrganizationDotsService {
	/**
	 * Add organization new message notification
	 * @param organizationCode Organization code
	 * @param count Count
	 * @param seqId Sequence ID
	 */
	addOrganizationDot(organizationCode: string, seqId: string, count: number = 1) {
		console.log("Adding organization new message notification", organizationCode, seqId, count)

		if (
			bigNumCompare(OrganizationDotsStore.getOrganizationDotSeqId(organizationCode), seqId) >=
			0
		) {
			console.log(
				"addOrganizationDot",
				OrganizationDotsStore.getOrganizationDotSeqId(organizationCode),
				seqId,
			)
			return
		}

		const newCount = OrganizationDotsStore.getOrganizationDots(organizationCode) + count

		OrganizationDotsStore.setOrganizationDots(organizationCode, newCount)
		OrganizationDotsStore.setOrganizationDotSeqId(organizationCode, seqId)

		BroadcastChannelSender.updateOrganizationDot({
			delightfulId: userStore.user.userInfo?.delightful_id || "",
			organizationCode,
			count: newCount,
			seqId,
		})

		const timer = setTimeout(() => {
			OrganizationDotsDbService.setPersistenceData(OrganizationDotsStore.dots)
			OrganizationDotsDbService.setDotSeqIdData(OrganizationDotsStore.dotSeqId)
			clearTimeout(timer)
		}, 0)
	}

	/**
	 * Reduce organization new message notification
	 * @param organizationCode Organization code
	 */
	reduceOrganizationDot(organizationCode: string, count: number = 1) {
		const newCount = Math.max(
			OrganizationDotsStore.getOrganizationDots(organizationCode) - count,
			0,
		)
		OrganizationDotsStore.setOrganizationDots(organizationCode, newCount)

		BroadcastChannelSender.updateOrganizationDot({
			delightfulId: userStore.user.userInfo?.delightful_id || "",
			organizationCode,
			count: newCount,
		})

		setTimeout(() => {
			OrganizationDotsDbService.setPersistenceData(OrganizationDotsStore.dots)
		}, 0)
	}

	/**
	 * Get organization new message notification
	 * @param organizationCode Organization code
	 * @returns New message notification
	 */
	getOrganizationDot(organizationCode: string) {
		return OrganizationDotsStore.getOrganizationDots(organizationCode)
	}

	/**
	 * Get organization new message notification sequence ID
	 * @param organizationCode Organization code
	 * @returns New message notification sequence ID
	 */
	getOrganizationDotSeqId(organizationCode: string) {
		return OrganizationDotsStore.getOrganizationDotSeqId(organizationCode)
	}

	/**
	 * Clear organization new message notification
	 * @param organizationCode Organization code
	 */
	clearOrganizationDot(organizationCode: string) {
		OrganizationDotsStore.clearOrganizationDots(organizationCode)
		OrganizationDotsDbService.setPersistenceData(OrganizationDotsStore.dots)
	}

	/**
	 * Clear all organization new message notifications
	 */
	clearAllOrganizationDots() {
		OrganizationDotsStore.clearAllOrganizationDots()
		OrganizationDotsDbService.setPersistenceData(OrganizationDotsStore.dots)
	}
}

export default new OrganizationDotsService()
