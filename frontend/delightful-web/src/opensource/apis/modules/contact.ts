import type { Friend, SquareData } from "@/types/contact"
import { genRequestUrl } from "@/utils/http"
import type { StructureItem, StructureUserItem } from "@/types/organization"
import { SumType } from "@/types/organization"
import type { CommonResponse, PaginationResponse } from "@/types/request"
import type { GroupConversationDetail } from "@/types/chat/conversation"
import { RequestUrl } from "../constant"
import type { HttpClient } from "../core/HttpClient"

export const generateContactApi = (fetch: HttpClient) => ({
	/**
	 * Add friend
	 * @param friend_id
	 * @returns
	 */
	addPropmtFriend(friend_id: string) {
		return fetch.post<{
			success: boolean
		}>(genRequestUrl(RequestUrl.addPropmtFriend, { friendId: friend_id }))
	},

	/**
	 * Get friends list
	 * @param data
	 * @returns
	 */
	getFriends(data: { page_token?: string }) {
		return fetch.get<PaginationResponse<Friend>>(
			genRequestUrl(RequestUrl.getFriends, {}, { page_token: data.page_token }),
		)
	},

	/**
	 * Get organization structure
	 * @param data
	 * @param data.department_id Department ID, -1 represents root department
	 * @param data.sum_type 1: Return direct users count of department 2: Return users count of this department + all sub-departments
	 * @param data.page_token Pagination token
	 * @returns
	 */
	getOrganization(data: { department_id?: string; sum_type?: 1 | 2; page_token?: string }) {
		return fetch.get<PaginationResponse<StructureItem>>(
			genRequestUrl(
				RequestUrl.getOrganization,
				{ id: data.department_id ?? -1 },
				{
					sum_type: data.sum_type,
					page_token: data.page_token,
				},
			),
		)
	},

	/**
	 * Get organization members
	 * @param data
	 * @returns
	 */
	getOrganizationMembers({
		department_id,
		count = 50,
		page_token = "",
		is_recursive = 0,
	}: {
		department_id: string
		count?: number
		page_token?: string
		is_recursive?: 0 | 1
	}) {
		return fetch.get<PaginationResponse<StructureUserItem>>(
			genRequestUrl(
				RequestUrl.getDepartmentUsers,
				{ id: department_id ?? "-1" },
				{
					count,
					page_token,
					is_recursive,
				},
			),
		)
	},

	/**
	 * Search user
	 * @param data
	 * @returns
	 */
	searchUser(data: { query: string; query_type?: 1 | 2; page_token?: string }) {
		return fetch.get<PaginationResponse<StructureUserItem>>(
			genRequestUrl(
				RequestUrl.searchUser,
				{},
				{
					query: data.query,
					query_type: data.query_type,
					page_token: data.page_token,
				},
			),
		)
	},

	/**
	 * Fuzzy search department list
	 * @param data
	 * @returns
	 */
	searchOrganizations(
		data: { name: string; sum_type?: SumType; page_token?: string } = {
			name: "",
			sum_type: SumType.CountDirectDepartment,
		},
	) {
		return fetch.get<PaginationResponse<StructureItem>>(
			genRequestUrl(
				RequestUrl.getDepartmentInfo,
				{},
				{
					name: data.name,
					sum_type: data.sum_type,
					page_token: data.page_token,
				},
			),
		)
	},

	/**
	 * Get all types of user info by IDs
	 * @param data
	 * @param init
	 * @returns
	 */
	getUserInfos(
		data: { user_ids: string[]; page_token?: string; query_type?: 1 | 2 },
		init?: RequestInit,
	) {
		return fetch.post<PaginationResponse<StructureUserItem>>(
			genRequestUrl(RequestUrl.getUserInfoByIds),
			data,
			{
				...init,
			},
		)
	},

	/**
	 * Get all types of conversation info by IDs
	 * @param data
	 * @returns
	 */
	getConversationInfos(data: { group_ids: string[]; page_token?: string }) {
		return fetch.post<PaginationResponse<GroupConversationDetail>>(
			genRequestUrl(RequestUrl.getGroupConversationDetail),
			data,
		)
	},

	/**
	 * Get user groups
	 * @returns
	 */
	getUserGroups(params: { page_token?: string }) {
		return fetch.get<PaginationResponse<GroupConversationDetail & { conversation_id: string }>>(
			genRequestUrl(
				RequestUrl.getUserGroups,
				{},
				{
					page_token: params.page_token,
				},
			),
		)
	},

	/**
	 * Get department info
	 * @param data
	 * @returns
	 */
	getDepartmentInfo(data: { department_id: string }) {
		return fetch.get<StructureItem>(
			genRequestUrl(RequestUrl.getDepartmentInfo, { id: data.department_id }),
		)
	},

	/**
	 * Get user manual
	 * @param data
	 * @returns
	 */
	getUserManual(data: { user_id: string }) {
		return fetch.post<CommonResponse<string>>(genRequestUrl(RequestUrl.getUserManual), data)
	},

	/**
	 * Get department document
	 * @param data
	 * @returns
	 */
	getDepartmentDocument(data: { department_id: string }) {
		return fetch.get<CommonResponse<string>>(
			genRequestUrl(RequestUrl.getDepartmentDocument, { id: data.department_id }),
		)
	},

	/**
	 * @description Get square prompts
	 */
	getSquarePrompts() {
		return fetch.get<SquareData>(genRequestUrl(RequestUrl.getSquarePrompts))
	},
})
