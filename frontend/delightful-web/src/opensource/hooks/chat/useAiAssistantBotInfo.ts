import { RequestUrl } from "@/opensource/apis/constant"
import { ChatApi } from "@/opensource/apis"
import type { Bot } from "@/types/bot"
import type { SWRMutationResponse } from "swr/mutation"
import useSWRMutation from "swr/mutation"

/**
 * Get the bot information corresponding to the AI assistant
 * @param user_id User ID
 * @returns
 */
const useAiAssistantBotInfo = (
	user_id?: string,
): SWRMutationResponse<
	Bot.Detail["botEntity"],
	Error,
	[string, RequestUrl.getAiAssistantBotInfo],
	{ user_id?: string }
> => {
	return useSWRMutation(user_id ? [user_id, RequestUrl.getAiAssistantBotInfo] : false, ([id]) =>
		ChatApi.getAiAssistantBotInfo({ user_id: id }),
	)
}

export default useAiAssistantBotInfo
