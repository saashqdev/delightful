import type { Login } from "@/types/login"

export namespace Common {
	export interface InternationalizedSettingsResponse {
		languages: Array<{
			name: string
			locale: string
			translations: Record<string, string>
		}>
		phone_area_codes: Array<{
			code: string
			name: string
			locale: string
			translations: Record<string, string>
		}>
	}

	/** Private login - third-party login data (silent login, QR code login) */
	interface PrivateConfigSignInValues {
		enable: boolean
		/** App Id */
		appId?: string
		/** App Key */
		appKey?: string
		/** App Secret */
		appSecret?: string
		/** Redirect URL */
		redirectUrl?: string
		/** DingTalk organization ID */
		corpId?: string
	}

	/** Private login - service configuration */
	interface ServiceConfig {
		/** Service address */
		url?: string
	}

	/** Private deployment configuration */
	export interface PrivateConfig {
		/** Private deployment access code */
		deployCode: string
		/** Current environment name */
		name?: string
		/** Micro-application/micro-service (http, websocket, etc. config in teamshare, keewood, delightful and other services) */
		services: Record<string, ServiceConfig>
		/** Third-party login */
		loginConfig?: Record<Login.LoginType, PrivateConfigSignInValues>
	}
}
