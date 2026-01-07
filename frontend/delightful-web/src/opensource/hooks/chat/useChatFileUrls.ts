import ChatFileService, { FileCacheData } from "@/opensource/services/chat/file/ChatFileService"
import { computed } from "mobx"
import { useMemo } from "react"

/**
 * Batch get file information
 * @param data File information
 * @returns File information
 */
const useChatFileUrls = (data?: { file_id: string; message_id: string }[]) => {
	const fileUrls = useMemo(() => {
		return computed(() => {
			return data?.reduce((prev, item) => {
				prev[item.file_id] = ChatFileService.getFileInfoCache(item.file_id)
				return prev
			}, {} as Record<string, FileCacheData | undefined>)
		})
	}, [data]).get()

	return { data: fileUrls, isLoading: false }
}

export default useChatFileUrls
