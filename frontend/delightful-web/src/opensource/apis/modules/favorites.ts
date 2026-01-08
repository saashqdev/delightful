import { genRequestUrl } from "@/utils/http"
import { RequestUrl } from "../constant"
import type { DriveItem } from "@/types/drive"
import type { HttpClient } from "../core/HttpClient"

export interface FavoritesListRequest {
	page?: number
	page_size?: number
	tag_id?: string
	/**
	 * Default is all, -1: All, 1: Message records, 2: Group chats, 3: Contacts, 4: Cloud drive
	 */
	type?: number
}

export interface DeleteFavoritesRequest {
	ids: string[]
}

export interface SearchFavoritesRequest {
	content: string
}

export interface RelationLabelRequest {
	// File ID
	target_ids: Array<string>
	// Default is 1: Favorites
	target_type: number | string
	tag_ids: Array<string>
}

export interface AddLabelRequest {
	name: string
	/**
	 * Tag type: 1-Favorites
	 */
	type: number
}

export const generateFavoritesApi = (fetch: HttpClient) => ({
	/**
	 * Get favorites list
	 */
	getFavoritesList(data: FavoritesListRequest): Promise<any> {
		return fetch.post<DriveItem[]>(genRequestUrl(RequestUrl.getFavorites), data)
	},

	/**
	 * Delete favorites
	 */
	deleteFavorites(data: DeleteFavoritesRequest) {
		return fetch.delete<DriveItem[]>(genRequestUrl(RequestUrl.deleteFavorites), data)
	},

	/**
	 * Search favorites
	 */
	searchFavorites(data: SearchFavoritesRequest) {
		return fetch.post<DriveItem[]>(genRequestUrl(RequestUrl.searchFavorites), data)
	},

	/**
	 * Add favorites
	 */
	addFavorites(data: SearchFavoritesRequest) {
		return fetch.post<DriveItem[]>(genRequestUrl(RequestUrl.addFavorites), data)
	},

	/**
	 * Associate label
	 */
	relationLabel(data: RelationLabelRequest) {
		return fetch.put<DriveItem[]>(genRequestUrl(RequestUrl.relationLabel), data)
	},

	/**
	 * Add label
	 */
	addLabel(data: AddLabelRequest[]) {
		return fetch.post<DriveItem[]>(genRequestUrl(RequestUrl.addLabel), data)
	},

	/**
	 * Edit label
	 */
	editLabel(id: string, data: Record<"name", string>) {
		return fetch.put<DriveItem[]>(genRequestUrl(RequestUrl.editLabel, { id }), data)
	},

	/**
	 * Delete label
	 */
	deleteLabel(data: Record<"ids", Array<string>>) {
		return fetch.delete<DriveItem[]>(genRequestUrl(RequestUrl.deleteLabel), data)
	},

	/**
	 * Get label list
	 */
	getLabelList(): any {
		return fetch.get<DriveItem[]>(genRequestUrl(RequestUrl.getLabelList))
	},
})
