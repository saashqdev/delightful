import { GlobalBaseRepository } from "@/opensource/models/repository/GlobalBaseRepository"
import type { User } from "@/types/user"

export class AccountRepository extends GlobalBaseRepository<User.UserAccount> {
	static tableName = "account"

	constructor() {
		super(AccountRepository.tableName)
	}
	
	// Query or remove a single account
}
