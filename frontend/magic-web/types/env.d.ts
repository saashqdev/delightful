// types/env.d.ts
interface ImportMetaEnv {
	/** Current environment */
	readonly MAGIC_APP_ENV?: "saas-test" | "saas-pre" | "saas-prod"
	/** Whether private deployment */
	readonly MAGIC_IS_PRIVATE_DEPLOY?: "true" | "false"
	/** WebSocket endpoint */
	readonly MAGIC_SOCKET_BASE_URL?: string
	/** SSO login URL */
	readonly MAGIC_TEAMSHARE_BASE_URL?: string
	/** Backend service URL */
	readonly MAGIC_SERVICE_BASE_URL?: string
	/** Keewood backend service URL */
	readonly MAGIC_SERVICE_KEEWOOD_BASE_URL?: string
	/** Teamshare backend service URL */
	readonly MAGIC_SERVICE_TEAMSHARE_BASE_URL?: string
	/** Teamshare Amap key */
	readonly MAGIC_AMAP_KEY?: string
	/** Teamshare Amap secret */
	readonly MAGIC_GATEWAY_ADDRESS?: string
	/** Magic app sha */
	readonly MAGIC_APP_SHA?: string
	/** Magic app version */
	readonly MAGIC_APP_VERSION?: string
	readonly MAGIC_TEAMSHARE_WEB_URL?: string
	readonly MAGIC_KEEWOOD_WEB_URL?: string
	readonly MAGIC_EDITION?: string
	/** Copyright info */
	readonly MAGIC_COPYRIGHT?: string
	/** ICP filing number */
	readonly MAGIC_ICP_CODE?: string
}

interface ImportMeta {
	readonly env: ImportMetaEnv
}

interface Window {
	CONFIG: ImportMetaEnv
}
