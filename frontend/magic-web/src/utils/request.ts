import type { PaginationResponse } from "@/types/request"

/**
 * Paginated data request
 * @param fetch Request function
 * @param count Items per page
 * @param options Config options
 * @param initPageToken Initial page token
 * @param prevData Previously fetched data
 * @returns
 */
export async function fetchPaddingData<I>(
	fetch: (params: { page_token: string }) => Promise<PaginationResponse<I>>,
	prevData: I[] = [],
	initPageToken = "",
): Promise<I[]> {
	let allData = [...prevData]
	let page_token = initPageToken
	let hasMore = true

	while (hasMore) {
		// eslint-disable-next-line no-await-in-loop
		const response = await fetch({ page_token })

		const { items, has_more, page_token: nextPageToken } = response

		allData = [...allData, ...items]

		hasMore = has_more
		page_token = nextPageToken
	}

	return allData
}
