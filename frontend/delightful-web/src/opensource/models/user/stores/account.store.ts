import { makeAutoObservable } from "mobx"
import type { User } from "@/types/user"

export class AccountStore {
	accounts: Array<User.UserAccount> = []

	constructor() {
		makeAutoObservable(this, {}, { autoBind: true })
	}

	/**
	 * @description Create account record
	 */
	setAccount = (account: User.UserAccount) => {
		const exists = this.accounts.some((acc) => acc.delightful_id === account.delightful_id)
		if (!exists) {
			this.accounts.push(account)
		}
	}

	/**
	 * @description Remove account record
	 */
	deleteAccount = (unionId: string) => {
		const index = this.accounts.findIndex((acc) => acc.delightful_id === unionId)
		if (index !== -1) {
			this.accounts.splice(index, 1)
		}
	}

	/**
	 * @description Update account record
	 */
	updateAccount = (unionId: string, updatedAccount: Partial<User.UserAccount>) => {
		const index = this.accounts.findIndex((acc) => acc.delightful_id === unionId)
		if (index !== -1) {
			this.accounts[index] = {
				...this.accounts[index],
				...updatedAccount,
			}
		}
	}
}

export const accountStore = new AccountStore()
