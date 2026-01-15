import type Dexie from "dexie"
import { DatabaseManager } from "./DatabaseManager"
import { AbstractBaseRepository } from "./AbstractBaseRepository"

/**
 * @description User-related repository base class
 */
export class BaseRepository<T> extends AbstractBaseRepository<T> {
	constructor(protected userId: string, tableName: string) {
		super(tableName)
	}

	protected async getDB(): Promise<Dexie> {
		return DatabaseManager.getInstance().getDatabase(this.userId)
	}
}
