import { genRequestUrl } from "@/utils/http"
import { RequestUrl } from "../constant"
import type { Common } from "@/types/common"
import { env } from "@/utils/env"
import type { HttpClient } from "@/opensource/apis/core/HttpClient"

export const generateCommonApi = (fetch: HttpClient) => ({
	/**
	 * Get application internationalization language, country codes, and other configurations (obtained locally for open source version)
	 * @returns
	 */
	getInternationalizedSettings() {
		return {
			phone_area_codes: [
				{
					code: "+86",
					name: "China",
					locale: "zh_CN",
					translations: {
						zh_CN: "China",
						en_US: "China",
					},
				},
				{
					code: "+60",
					name: "Malaysia",
					locale: "ms_MY",
					translations: {
						zh_CN: "Malaysia",
						en_US: "Malaysia",
					},
				},
				{
					code: "+84",
					name: "Vietnam",
					locale: "vi_VN",
					translations: {
						zh_CN: "Vietnam",
						en_US: "Vietnam",
					},
				},
				{
					code: "+66",
					name: "Thailand",
					locale: "th_TH",
					translations: {
						zh_CN: "Thailand",
						en_US: "Thailand",
					},
				},
				{
					code: "+63",
					name: "Philippines",
					locale: "fil_PH",
					translations: {
						zh_CN: "Philippines",
						en_US: "Philippines",
					},
				},
				{
					code: "+65",
					name: "Singapore",
					locale: "en_SG",
					translations: {
						zh_CN: "Singapore",
						en_US: "Singapore",
					},
				},
			],
			languages: [
				{
					name: "Simplified Chinese",
					locale: "zh_CN",
					translations: {
						zh_CN: "Simplified Chinese",
						en_US: "Simplified Chinese",
					},
				},
				{
					name: "English",
					locale: "en_US",
					translations: {
						zh_CN: "English",
						en_US: "English",
					},
				},
			],
		}
	},

	/**
	 * @description Get private deployment login environment configuration
	 * @param {string} code Private deployment authorization code
	 */
	async getPrivateConfigure(code: string): Promise<{ config: Common.PrivateConfig }> {
		// When and only when code is empty or does not exist, return the teamshare and keewood configuration in the same environment as the current delightful deployment environment
		if (!code || code === "") {
			return {
				config: {
					deployCode: "",
					services: {
						keewoodAPI: {
							url: env("DELIGHTFUL_SERVICE_KEEWOOD_BASE_URL", true) as string,
						},
						teamshareAPI: {
							url: env("DELIGHTFUL_TEAMSHARE_WEB_URL", true) as string,
						},
						teamshareWeb: {
							url: env("DELIGHTFUL_SERVICE_TEAMSHARE_BASE_URL", true) as string,
						},
						keewoodWeb: {
							url: env("DELIGHTFUL_KEEWOOD_WEB_URL", true) as string,
						},
					},
				},
			}
		}
		return fetch.post<{ config: Common.PrivateConfig }>(
			env("DELIGHTFUL_SERVICE_KEEWOOD_BASE_URL", true) +
				genRequestUrl(RequestUrl.getPrivateConfigure),
			{ identifier: code || "" },
		)
	},
})
