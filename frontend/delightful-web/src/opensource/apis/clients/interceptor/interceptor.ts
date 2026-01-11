import type { UserService } from "@/opensource/services/user/UserService"
import { userStore } from "@/opensource/models/user/stores"
import type { Container } from "@/opensource/services/ServiceContainer"
import { RoutePath } from "@/const/routes"
import { message } from "antd"
import type { ResponseData } from "../../core/HttpClient"
import { LoginValueKey } from "@/opensource/pages/login/constants"

/** HTTP status code enumeration (RFC 7231, RFC 7233, RFC 7540) */
const enum HttpStatusCode {
	/** 200 OK - Request successful */
	Ok = 200,
	/** 302 Found - Resource temporarily redirected */
	Found = 302,
	/** 400 Bad Request - Request syntax error */
	BadRequest = 400,
	/** 401 Unauthorized - Not authenticated */
	Unauthorized = 401,
	/** 403 Forbidden - No permission to access */
	Forbidden = 403,
	/** 404 Not Found - Resource does not exist */
	NotFound = 404,
	/** 500 Internal Server Error - Server internal error */
	InternalServerError = 500,
}

const enum BusinessResponseCode {
	/** Response successful */
	Success = 1000,
	/** Invalid organization */
	InvalidOrganization = 40101,
}

/**
 * Generate login redirect URL
 * @returns Login redirect URL
 */
export const genLoginRedirectUrl = () => {
	const redirectUrl = new URL(RoutePath.Login, window.location.origin)
	if (window.location.pathname !== RoutePath.Login) {
		const redirectTarget = new URLSearchParams(window.location.search).get(
			LoginValueKey.REDIRECT_URL,
		)

		// Get current page address
		redirectUrl.searchParams.set(
			LoginValueKey.REDIRECT_URL,
			redirectTarget ?? window.location.href,
		)
	}
	return redirectUrl.toString()
}

/** Login invalid */
export function generateUnauthorizedResInterceptor(service: Container) {
	return async function unauthorized(response: ResponseData) {
		const { enableAuthorizationVerification = true } = response.options
		if (
			(enableAuthorizationVerification && response.status === HttpStatusCode.Unauthorized) ||
			response?.data?.code === 3103
		) {
			await service.get<UserService>("userService").deleteAccount()
			if (window.location.pathname !== RoutePath.Login) {
				window.history.pushState({}, "", genLoginRedirectUrl())
			}
			throw new Error("Unauthorized")
		}
		return response
	}
}

/** Invalid organization */
export function generateInvalidOrgResInterceptor(service: Container) {
	return async function invalidOr(response: ResponseData) {
		const jsonResponse = response.data
		if (jsonResponse?.code === BusinessResponseCode.InvalidOrganization) {
			service
				.get<UserService>("userService")
				.setDelightfulOrganizationCode(userStore.user.organizations?.[0]?.organization_code)
			window.location.reload()
		}
		return response
	}
}

/** Successful response */
export function generateSuccessResInterceptor() {
	return async function success(response: ResponseData) {
		const jsonResponse = response.data
		if (jsonResponse?.code !== BusinessResponseCode.Success) {
			if (response.options?.enableErrorMessagePrompt && jsonResponse?.message) {
				message.error(jsonResponse.message)
			}
			throw jsonResponse
		}

		// Unwrap data
		if (response.options?.unwrapData) {
			return jsonResponse.data
		}

		return response
	}
}
