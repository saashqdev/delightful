import { groupBy } from "lodash-es"
import type { ChatFileUrlData } from "@/types/chat/conversation_message"
import { makeAutoObservable } from "mobx"
import chatDb from "@/opensource/database/chat"
import { ChatApi } from "@/apis"

export interface FileCacheData extends ChatFileUrlData {
	file_id: string
	message_id: string
	url: string
	expires: number
}

/**
 * Chat file services
 */
class ChatFileService {
	fileInfoCache: Map<string, FileCacheData> = new Map()

	constructor() {
		makeAutoObservable(this, {}, { autoBind: true })
	}

	/**
	 * Initialize
	 */
	init() {
		chatDb
			?.getFileUrlsTable()
			?.toArray()
			.then((res) => {
				console.log("res =======> ", res)
				res.forEach((item) => {
					this.fileInfoCache.set(item.file_id, item)
				})
			})
	}

	/**
	 * Get file info cache
	 */
	getFileInfoCache(fileId?: string) {
		if (!fileId) return undefined
		return this.fileInfoCache.get(fileId)
	}

	/**
	 * Cache file information
	 */
	cacheFileUrl(fileInfo: ChatFileUrlData & { file_id: string; message_id: string }) {
		this.fileInfoCache.set(fileInfo.file_id, fileInfo)
		chatDb?.getFileUrlsTable()?.put(fileInfo)
	}

	/**
	 * Check if file has expired
	 */
	checkFileExpired(fileId: string, expiredTime: number = 30 * 60 * 1000) {
		const fileInfo = this.fileInfoCache.get(fileId)

		if (!fileInfo) return true

		// Consider expired if less than 30 minutes remaining
		return fileInfo.expires * 1000 < Date.now() + expiredTime
	}

	/**
	 * Get file information
	 */
	fetchFileUrl(
		datas?: { file_id: string; message_id: string }[],
	): Promise<Record<string, FileCacheData>> {
		if (!datas || !datas.length) return Promise.resolve({})

		// Detect if expired
		const { true: expired = [], false: notExpired = [] } = groupBy(datas, (item) => {
			return this.checkFileExpired(item.file_id)
		})

		if (expired.length > 0) {
			const messageIdMap = new Map(datas.map((item) => [item.file_id, item.message_id]))
			return ChatApi.getChatFileUrls(expired).then((res) => {
				const resArray = Object.entries(res)
				for (let i = 0; i < resArray.length; i += 1) {
					const [fileId, fileInfo] = resArray[i]
					this.cacheFileUrl({
						...fileInfo,
						file_id: fileId,
						message_id: messageIdMap.get(fileId) || "",
					})
				}

				// Return file information
				return datas.reduce((acc, item) => {
					acc[item.file_id] = this.fileInfoCache.get(item.file_id)!
					return acc
				}, {} as Record<string, FileCacheData>)
			})
		}

		return Promise.resolve(
			notExpired.reduce((acc, item) => {
				acc[item.file_id] = this.fileInfoCache.get(item.file_id)!
				return acc
			}, {} as Record<string, FileCacheData>),
		)
	}
}

export default new ChatFileService()
