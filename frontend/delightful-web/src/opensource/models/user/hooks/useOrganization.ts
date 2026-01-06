import { useEffect, useState } from "react"
import { reaction } from "mobx"
import { userStore } from "@/opensource/models/user"

/**
 * Get current user organization info
 */
export function useOrganization() {
	const [organizationMeta, setOrganizationMeta] = useState({
		organizations: userStore.user.organizations,
		organizationCode: userStore.user.organizationCode,
		delightfulOrganizationMap: userStore.user.delightfulOrganizationMap,
		teamshareOrganizationCode: userStore.user.teamshareOrganizationCode,
	})

	useEffect(() => {
		return reaction(
			() => ({
				organizationCode: userStore.user.organizationCode,
				organizations: userStore.user.organizations,
				delightfulOrganizationMap: userStore.user.delightfulOrganizationMap,
				teamshareOrganizationCode: userStore.user.teamshareOrganizationCode,
			}),
			(org) => setOrganizationMeta(org),
			{
				fireImmediately: true,
			},
		)
	}, [])

	return {
		organizationCode: organizationMeta.organizationCode,
		organizations: organizationMeta.organizations,
		delightfulOrganizationMap: organizationMeta.delightfulOrganizationMap,
		teamshareOrganizationCode: organizationMeta.teamshareOrganizationCode,
	}
}
