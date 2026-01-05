/**
 * Replace route parameters
 *
 * @param route Route template
 * @param params Param map
 * @returns Route with parameters substituted
 */
export const replaceRouteParams = (route: string, params: Record<string, string>) => {
	const reg = /:([^/]+)/g
	return route.replace(reg, (_, key) => params[key])
}

/**
 * Open a new tab
 * @param url Target URL
 */
export const openNewTab = (url?: string, base?: string) => {
	if (!url) return
	window.open(base ? `${base}${url}` : url, "_blank")
}

/**
 * Build URL with additional query params
 * @param query New query entries
 * @returns URL string
 */
export const getUrlWithNewSearchQuery = (url: string, query: Record<string, string>) => {
	const querys = new URLSearchParams(window.location.search)

	Object.entries(query).forEach(([key, value]) => {
		querys.append(key, value)
	})

	return `${url}?${querys.toString()}`
}
