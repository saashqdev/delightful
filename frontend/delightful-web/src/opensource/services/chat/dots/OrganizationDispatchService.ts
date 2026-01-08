import { userStore } from "@/opensource/models/user"
import OrganizationDotsStore from "@/opensource/stores/chatNew/dots/OrganizationDotsStore"

const OrganizationDispatchService = {
	/**
	 * Update organization badge
	 * @param organizationCode Organization code
	 * @param count Count
	 * @param seqId Sequence ID
	 */
	updateOrganizationDot({
		delightfulId,
		organizationCode,
		count,
		seqId,
	}: {
		delightfulId: string
		organizationCode: string
		count: number
		seqId?: string
	}) {
		if (delightfulId === userStore.user.userInfo?.delightful_id) {
			OrganizationDotsStore.setOrganizationDots(organizationCode, count)

			if (seqId) {
				OrganizationDotsStore.setOrganizationDotSeqId(organizationCode, seqId)
			}
		}
	},
}

export default OrganizationDispatchService
