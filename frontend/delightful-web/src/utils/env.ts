import { AppEnv } from "@/types/env"
import { configStore } from "@/opensource/models/config"

/** Whether development environment */
export const isDev = process.env.NODE_ENV === "development"

console.log("magic sha: ", window?.CONFIG?.DELIGHTFUL_APP_SHA)
console.log("magic version: ", window?.CONFIG?.DELIGHTFUL_APP_VERSION)

/**
 * @description Get environment variables (normalize to current environment using global PrivateDeployment config)
 * @param {keyof ImportMetaEnv} key
 * @param {boolean} isCurrentEnv Return current deployment env vars instead of account env
 * @param {string} deployCode Custom private deployment code
 */
export const env = (
	key: keyof ImportMetaEnv,
	isCurrentEnv?: boolean,
	deployCode?: string | null,
) => {
	const { clusterConfig } = configStore.cluster

	if (deployCode && clusterConfig?.[deployCode]?.services && !isCurrentEnv) {
		return {
			...import.meta.env,
			...(window?.CONFIG ?? {}),
			DELIGHTFUL_SERVICE_KEEWOOD_BASE_URL:
				clusterConfig?.[deployCode]?.services?.keewoodAPI?.url ||
				window?.CONFIG?.DELIGHTFUL_SERVICE_KEEWOOD_BASE_URL,
			DELIGHTFUL_SERVICE_TEAMSHARE_BASE_URL:
				clusterConfig?.[deployCode]?.services?.teamshareAPI?.url ||
				window?.CONFIG?.DELIGHTFUL_SERVICE_TEAMSHARE_BASE_URL,
			DELIGHTFUL_TEAMSHARE_WEB_URL:
				clusterConfig?.[deployCode]?.services?.teamshareWeb?.url ||
				window?.CONFIG?.DELIGHTFUL_TEAMSHARE_WEB_URL,
			DELIGHTFUL_KEEWOOD_WEB_URL:
				clusterConfig?.[deployCode]?.services?.keewoodWeb?.url ||
				window?.CONFIG?.DELIGHTFUL_KEEWOOD_WEB_URL,
		}[key]
	}

	return {
		...import.meta.env,
		...(window?.CONFIG ?? {}),
	}[key]
}

/**
 * @description Whether production environment
 * @returns {boolean} Is production environment
 */
export const isProductionEnv = (): boolean => env("DELIGHTFUL_APP_ENV") === AppEnv.Production

/** Commercial edition */
export const isCommercial = () => env("DELIGHTFUL_EDITION") === "ENTERPRISE"
