import { useEffect, useState } from "react"
import { reaction } from "mobx"
import { userStore } from "@/opensource/models/user"

/**
 * Get current user accounts
 */
export function useAccount() {
	const [accounts, setAccounts] = useState(userStore.account.accounts)

	useEffect(() => {
		return reaction(
			() => userStore.account.accounts,
			(o) => setAccounts(o),
		)
	}, [])

	return { accounts }
}
