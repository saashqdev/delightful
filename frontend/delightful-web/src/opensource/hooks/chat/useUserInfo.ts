import { useMemo } from "react"
import userInfoStore from "@/opensource/stores/userInfo"
import { computed } from "mobx"

/**
 * Get multiple user information
 * @param uid User ID
 */
const useUserInfo = (uid?: string | null) => {
	const userInfo = useMemo(() => {
		return computed(() => (uid ? userInfoStore.get(uid) : undefined))
	}, [uid]).get()

	return { userInfo }
}

export default useUserInfo
