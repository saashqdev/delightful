// types/env.d.ts
interface ImportMetaEnv {
	/** Current environment */
	readonly DELIGHTFUL_APP_ENV?: "saas-test" | "saas-pre" | "saas-prod"
	/** Whether private deployment */
	readonly DELIGHTFUL_IS_PRIVATE_DEPLOY?: "true" | "false"
	/** WebSocket endpoint */
	readonly DELIGHTFUL_SOCKET_BASE_URL?: string
	/** SSO login URL */
	readonly DELIGHTFUL_TEAMSHARE_BASE_URL?: string
	/** Backend service URL */
	readonly DELIGHTFUL_SERVICE_BASE_URL?: string
	/** Keewood backend service URL */
	readonly DELIGHTFUL_SERVICE_KEEWOOD_BASE_URL?: string
	/** Teamshare backend service URL */
	readonly DELIGHTFUL_SERVICE_TEAMSHARE_BASE_URL?: string
	/** Teamshare Amap key */
	readonly DELIGHTFUL_AMAP_KEY?: string
	/** Teamshare Amap secret */
	readonly DELIGHTFUL_GATEWAY_ADDRESS?: string
	/** Delightful app sha */
	readonly DELIGHTFUL_APP_SHA?: string
	/** Delightful app version */
	readonly DELIGHTFUL_APP_VERSION?: string
	readonly DELIGHTFUL_TEAMSHARE_WEB_URL?: string
	readonly DELIGHTFUL_KEEWOOD_WEB_URL?: string
	readonly DELIGHTFUL_EDITION?: string
	/** Copyright info */
	readonly DELIGHTFUL_COPYRIGHT?: string
	/** ICP filing number */
	readonly DELIGHTFUL_ICP_CODE?: string
}

interface ImportMeta {
	readonly env: ImportMetaEnv
}

interface Window {
	CONFIG: ImportMetaEnv
}
