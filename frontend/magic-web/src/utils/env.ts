import { AppEnv } from "@/types/env"
import { configStore } from "@/opensource/models/config"

/** Whether development environment */
export const isDev = process.env.NODE_ENV === "development"

console.log("magic sha: ", window?.CONFIG?.MAGIC_APP_SHA)
console.log("magic version: ", window?.CONFIG?.MAGIC_APP_VERSION)

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
			MAGIC_SERVICE_KEEWOOD_BASE_URL:
				clusterConfig?.[deployCode]?.services?.keewoodAPI?.url ||
				window?.CONFIG?.MAGIC_SERVICE_KEEWOOD_BASE_URL,
			MAGIC_SERVICE_TEAMSHARE_BASE_URL:
				clusterConfig?.[deployCode]?.services?.teamshareAPI?.url ||
				window?.CONFIG?.MAGIC_SERVICE_TEAMSHARE_BASE_URL,
			MAGIC_TEAMSHARE_WEB_URL:
				clusterConfig?.[deployCode]?.services?.teamshareWeb?.url ||
				window?.CONFIG?.MAGIC_TEAMSHARE_WEB_URL,
			MAGIC_KEEWOOD_WEB_URL:
				clusterConfig?.[deployCode]?.services?.keewoodWeb?.url ||
				window?.CONFIG?.MAGIC_KEEWOOD_WEB_URL,
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
export const isProductionEnv = (): boolean => env("MAGIC_APP_ENV") === AppEnv.Production

/** Commercial edition */
export const isCommercial = () => env("MAGIC_EDITION") === "ENTERPRISE"
