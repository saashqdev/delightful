import { platformKey } from "@/utils/storage"
import Dexie from "dexie"
import { DataContextDb } from "./types"

export const DataContextDbName = (delightfulId: string, userId: string) =>
	platformKey(`data-content/${delightfulId}/${userId}`)

export const initDataContextDb = (delightfulId: string, userId: string) => {
	const db = new Dexie(DataContextDbName(delightfulId, userId)) as DataContextDb

	db.version(1).stores({
		user_info: "&user_id",
		group_info: "&id",
	})

	return db
}
