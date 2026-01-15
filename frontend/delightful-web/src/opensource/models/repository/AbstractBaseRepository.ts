import type { IndexableType, Table, UpdateSpec } from "dexie"
import type Dexie from "dexie"

/**
 * @description Repository layer abstract base class
 */
export abstract class AbstractBaseRepository<T> {
	protected db: Dexie | undefined

	protected tableName: string

	constructor(tableName: string) {
		this.tableName = tableName
	}

	/**
	 * @description Get database instance
	 */
	protected abstract getDB(): Promise<Dexie>

	/**
	 * @description Get table object
	 */
	protected async getTable(): Promise<Table<T, IndexableType>> {
		const db = await this.getDB()
		return db.table<T>(this.tableName)
	}

	/**
	 * @description Add or update data
	 * @param data
	 */
	async put(data: T): Promise<void> {
		const table = await this.getTable()
		try {
			await table.put(data)
		} catch (error) {
			console.log("-----", data)
			console.error(error)
		}
	}

	/**
	 * @description Get a single record
	 * @param key
	 */
	async get(key: IndexableType): Promise<T | undefined> {
		const table = await this.getTable()
		return table.get(key)
	}

	/**
	 * @description Get all records
	 */
	async getAll(): Promise<T[]> {
		const table = await this.getTable()
		return table.toArray()
	}

	/**
	 * @description Query by index
	 * @param indexName
	 * @param value
	 */
	async getByIndex(indexName: string, value: IndexableType): Promise<T[]> {
		const table = await this.getTable()
		return table.where(indexName).equals(value).toArray()
	}

	/**
	 * @description Delete record
	 * @param key
	 */
	async delete(key: IndexableType): Promise<void> {
		const table = await this.getTable()
		await table.delete(key)
	}

	/**
	 * @description Clear table
	 */
	async clear(): Promise<void> {
		const table = await this.getTable()
		await table.clear()
	}

	/**
	 * @description Update record
	 * @param key Primary key
	 * @param changes Fields to update
	 */
	async update(key: IndexableType, changes: UpdateSpec<T>): Promise<number> {
		const table = await this.getTable()
		return table.update(key, changes)
	}
}
