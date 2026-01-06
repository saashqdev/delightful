import delightfulFetcher from "@/opensource/apis/clients/delightful"
// Check if share needs password
export const getShareIsNeedPassword = async ({ resource_id }: { resource_id: string }) => {
	const response = await delightfulFetcher.post(`/api/v1/share/resources/${resource_id}/check`)
	return response
}

// Get share details
export const getShareDetail = async ({
	resource_id,
	password,
	page = 1,
	page_size = 500,
}: {
	resource_id: string
	password?: string
	page?: number
	page_size?: number
}) => {
	const url = `/api/v1/share/resources/${resource_id}/detail`

	const response = await delightfulFetcher.post(url, { pwd: password, page, page_size })
	return response
}
