import type { DataContextDb } from "@/opensource/database/data-context/types"
import type { StructureUserItem } from "@/types/organization"
import { fetchPaddingData } from "@/utils/request"
import type { IndexableType, Table } from "dexie"
import { getAvatarUrl } from "@/utils/avatar"
import userInfoStore from "@/opensource/stores/userInfo"
import { ContactApi } from "@/apis"

/**
 * User info context
 */
class UserInfoService {
	static STORE_NAME = "user_info"

	initd = false

	table: Table<StructureUserItem, IndexableType, StructureUserItem> | undefined

	database: DataContextDb | undefined

	promise: Promise<void> | undefined

	constructor(db?: DataContextDb) {
		this.loadData(db)
	}

	/**
	 * Fetch user info
	 * @param userIds User IDs
	 * @param queryType Query type
	 * @returns User info
	 */
	fetchUserInfos(userIds: string[], queryType: 1 | 2) {
		return fetchPaddingData((params) =>
			ContactApi.getUserInfos({
				user_ids: userIds,
				query_type: queryType ?? 2,
				...params,
			}),
		).then((data) => {
			data.forEach((item) => {
				userInfoStore.set(item.user_id, { ...userInfoStore.get(item.user_id), ...item })
			})
			return data
		})
	}

	/**
	 * Load data from the database
	 * @param db Database
	 * @returns Data
	 */
	loadData(db?: DataContextDb) {
		this.database = db
		this.promise = this.database?.user_info
			.each((item) => {
				userInfoStore.set(item.user_id, item)
			})
			.then(() => {
				this.initd = true
			})

		return this.promise
	}

	/**
	 * Set user info
	 * @param key User ID
	 * @param value User info
	 */
	set(key: string, value: StructureUserItem) {
		const avatarUrl = getAvatarUrl(value.avatar_url)
		const relatedAvatarUrl = value.avatar_url

		const info = {
			...value,
			avatar_url: avatarUrl,
			related_avatar_url: relatedAvatarUrl,
		}

		userInfoStore.set(key, info)
		this.database?.user_info.put(info)
	}
}

const userInfoService = new UserInfoService()

export default userInfoService
