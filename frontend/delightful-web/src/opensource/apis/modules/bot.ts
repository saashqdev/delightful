import { genRequestUrl } from "@/utils/http"
import type {
	Bot,
	QuickInstructionList,
	RespondInstructType,
	SystemInstructMap,
	WithPage,
} from "@/types/bot"
import type { StatusIconKey } from "@/opensource/pages/flow/components/QuickInstructionButton/const"
import { RequestUrl } from "../constant"
import type { HttpClient } from "../core/HttpClient"

export const generateBotApi = (fetch: HttpClient) => ({
	/** Get market bots */
	getMarketBotList() {
		return fetch.get<WithPage<Bot.BotItem[]>>(genRequestUrl(RequestUrl.getMarketBotList))
	},

	/** Get organization bots */
	getOrgBotList({ page = 1, pageSize = 10, keyword }: Bot.GetUserBotListParams) {
		return fetch.get<WithPage<Bot.OrgBotItem[]>>(
			genRequestUrl(
				RequestUrl.getOrgBotList,
				{},
				{
					page,
					page_size: pageSize,
					robot_name: keyword,
				},
			),
		)
	},

	/** Get user personal bots */
	getUserBotList({ page, pageSize, keyword }: Bot.GetUserBotListParams) {
		return fetch.get<WithPage<Bot.BotItem[]>>(
			genRequestUrl(
				RequestUrl.getUserBotList,
				{},
				{
					page,
					page_size: pageSize,
					robot_name: keyword,
				},
			),
		)
	},

	/** Get bot version list */
	getBotVersionList(agentId: string) {
		return fetch.get<Bot.BotVersion[]>(genRequestUrl(RequestUrl.getBotVersionList, { agentId }))
	},

	/** Save bot */
	saveBot(params: Bot.SaveBotParams) {
		return fetch.post<Bot.Detail["botEntity"]>(genRequestUrl(RequestUrl.saveBot), params)
	},

	/** Delete bot */
	deleteBot(agentId: string) {
		return fetch.delete<null>(genRequestUrl(RequestUrl.deleteBot, { agentId }))
	},

	/** Update bot status */
	updateBotStatus(agentId: string, status: number) {
		return fetch.put<null>(genRequestUrl(RequestUrl.updateBotStatus, { agentId }, { status }))
	},

	/** Publish bot */
	publishBot(params: Bot.PublishBotParams) {
		return fetch.post<Bot.PublishBot>(genRequestUrl(RequestUrl.publishBot), params)
	},

	/** Get bot maximum version number */
	getMaxVersion(agentId: string) {
		return fetch.get<string>(genRequestUrl(RequestUrl.getBotMaxVersion, { agentId }))
	},

	/** Publish bot to organization */
	publishToOrg(agentId: string, status: number) {
		return fetch.get<null>(genRequestUrl(RequestUrl.publishBotToOrg, { agentId }, { status }))
	},

	/** Register bot and add as friend */
	registerAndAddFriend(agentVersionId: string) {
		return fetch.post<Bot.AddFriend>(
			genRequestUrl(RequestUrl.registerAndAddFriend, {
				agentVersionId,
			}),
		)
	},

	/** Get bot details */
	getBotDetail(agentId: string) {
		return fetch.get<Bot.Detail>(genRequestUrl(RequestUrl.getBotDetail, { agentId }))
	},

	/** Get bot version details */
	getBotVersionDetail(agentVersionId: string) {
		return fetch.post<Bot.Detail>(
			genRequestUrl(RequestUrl.getBotVersionDetail, {
				agentVersionId,
			}),
		)
	},

	/** Check if bot has been modified */
	isBotUpdate(agentId: string) {
		return fetch.get<boolean>(genRequestUrl(RequestUrl.isBotUpdate, { agentId }))
	},

	/** Get default icon */
	getDefaultIcon() {
		return fetch.get<Bot.DefaultIcon>(genRequestUrl(RequestUrl.getDefaultIcon))
	},

	/** Save interaction instruction */
	saveInstruct(params: Bot.SaveInstructParams) {
		return fetch.post<QuickInstructionList[]>(
			genRequestUrl(RequestUrl.saveInstruct, { agentId: params.bot_id }),
			params,
		)
	},

	/** Get interaction instruction type */
	getInstructTypeOption() {
		return fetch.get<RespondInstructType>(genRequestUrl(RequestUrl.getInstructTypeOption))
	},

	/** Get interaction instruction group type */
	getInstructGroupTypeOption() {
		return fetch.get<RespondInstructType>(genRequestUrl(RequestUrl.getInstructGroupTypeOption))
	},

	/** Get interaction instruction status type icons */
	getInstructStatusIcons() {
		return fetch.get<StatusIconKey[]>(genRequestUrl(RequestUrl.getInstructStatusIcons))
	},

	/** Get interaction instruction status type color groups */
	getInstructStatusColors() {
		return fetch.get<RespondInstructType>(genRequestUrl(RequestUrl.getInstructStatusColors))
	},

	/** Get system interaction instructions */
	getSystemInstruct() {
		return fetch.get<SystemInstructMap>(genRequestUrl(RequestUrl.getSystemInstruct))
	},

	/** Get configured third-party platforms */
	getThirdPartyPlatforms(botId: string) {
		return fetch.get<WithPage<Bot.ThirdPartyPlatform[]>>(
			genRequestUrl(
				RequestUrl.getThirdPartyPlatforms,
				{ botId },
				{
					page: 1,
					page_size: 10,
				},
			),
		)
	},
})
