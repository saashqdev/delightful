import { useClientDataSWR } from "@/utils/swr"
import type { User } from "@/types/user"
import { RequestUrl } from "@/opensource/apis/constant"
import { UserApi } from "@/opensource/apis"
import { useMemo } from "react"
import { useOrganization } from "./useOrganization"

/**
 * @description Get devices logged in with the current account
 */
export const useUserDevices = () => {
	return useClientDataSWR<User.UserDeviceInfo[]>(RequestUrl.getUserDevices, () =>
		UserApi.getUserDevices(),
	)
}

/**
 * @description Hook to get the organization for the current account
 * @return {User.UserOrganization | undefined}
 */
export const useCurrentDelightfulOrganization = (): User.DelightfulOrganization | null => {
	const { organizationCode, magicOrganizationMap } = useOrganization()

	return useMemo(() => {
		return magicOrganizationMap[organizationCode]
	}, [organizationCode, magicOrganizationMap])
}

export * from "./useAccount"
export * from "./useOrganization"
export * from "./useAuthorization"
export * from "./useUserInfo"
