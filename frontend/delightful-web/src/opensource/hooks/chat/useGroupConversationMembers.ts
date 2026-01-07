import { RequestUrl } from "@/opensource/apis/constant"
import type { GroupConversationMember } from "@/types/chat/conversation"
import { fetchPaddingData } from "@/utils/request"
import type { SWRResponse } from "swr"
import useSWRImmutable from "swr/immutable"
import { ChatApi } from "@/opensource/apis"

/**
 * Get group chat members
 * @param group_id Group ID
 * @returns
 */
const useGroupConversationMembers = (group_id?: string): SWRResponse<GroupConversationMember[]> => {
	return useSWRImmutable(
		group_id ? [group_id, `${RequestUrl.getGroupConversationMembers}/${group_id}`] : false,
		([groupId]) =>
			fetchPaddingData<GroupConversationMember>((params) =>
				ChatApi.getGroupConversationMembers({
					group_id: groupId,
					...params,
				}),
			).then((data) => {
				return data.sort((a, b) => {
					if (a.user_role === b.user_role) {
						return a.created_at - b.created_at
					}
					return a.user_role - b.user_role
				})
			}),
	)
}

export default useGroupConversationMembers
