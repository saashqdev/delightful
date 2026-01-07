import type { RequestUrl } from "@/opensource/apis/constant"
import { resolveToString } from "@delightful/es6-template-strings"
import { isUndefined } from "lodash-es"

/**
 * Generate a request URL
 * @param url URL template
 * @param params Path parameters
 * @returns Request URL
 */
export function genRequestUrl(
	url: RequestUrl | string,
	params: Record<string, string | number | null> = {},
	queries?: Record<string, string | number | null | undefined | any>,
) {
	const requestUrl = resolveToString(url, params)
	const stringifyQueries = Object.entries(queries ?? {}).reduce<string[][]>(
		(prev, [key, value]) => {
			if (!isUndefined(value)) {
				prev.push([key, `${value}`])
			}
			return prev
		},
		[],
	)

	const pars = new URLSearchParams(stringifyQueries)
	if (pars.size > 0) {
		return `${requestUrl}?${pars.toString()}`
	}
	return requestUrl
}

/**
 * Check if the string is a valid URL
 * @param url
 * @returns
 */
export function isValidUrl(url: string) {
	return /^https?:\/\//.test(url)
}

/**
 * Check if the text can be parsed as a valid URL
 * @param text
 * @returns
 */
export function isUrl(text: string) {
	if (text.match(/\n/)) {
		return false
	}

	try {
		const url = new URL(text)
		return url.hostname !== ""
	} catch (err) {
		return false
	}
}
