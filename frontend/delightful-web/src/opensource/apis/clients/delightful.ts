import { userStore } from "@/opensource/models/user"
import { env } from "@/utils/env"
import { getCurrentLang } from "@/utils/locale"
import { configStore } from "@/opensource/models/config"
import { HttpClient } from "../core/HttpClient"
import generatorUnionRequest from "@/opensource/apis/core/unionRequest"

export class DelightfulHttpClient extends HttpClient {
	constructor() {
		super(env("DELIGHTFUL_SERVICE_BASE_URL"))
		this.setupInterceptors()
	}

	private setupInterceptors() {
		// Request interceptor
		this.addRequestInterceptor(function request(config) {
			const headers = new Headers(config.headers)

			// Set common request headers
			headers.set("Content-Type", "application/json")
			headers.set("language", getCurrentLang(configStore.i18n.language))

			if (!headers.get("authorization")) {
				headers.set("authorization", userStore.user.authorization ?? "")
			}

			// If organization code is not in headers, set organization code
			if (!headers.get("organization-code")) {
				// For delightful API requests, organization Code needs to be converted to the organization Code in the delightful ecosystem, not teamshare organization Code
				const delightfulOrganizationCode = userStore.user.organizationCode
				headers.set("organization-code", delightfulOrganizationCode ?? "")
			}

			return {
				...config,
				headers,
			}
		})

		// Error interceptor
		this.addErrorInterceptor(function errHandler(error) {
			console.error("Request failed:", error)
			if (error.code === 2185 && window.location.pathname.startsWith("/admin/")) {
				window.history.pushState({}, "", "/admin/no-authorized")
				window.dispatchEvent(new Event("popstate"))
			}
			return Promise.reject(error)
		})
	}
}

const delightfulClient = new DelightfulHttpClient()

const unionRequestDecorator = generatorUnionRequest()

export default unionRequestDecorator(delightfulClient)
