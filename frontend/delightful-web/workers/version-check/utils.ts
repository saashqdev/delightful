/**
 * Generate a UUID using function
 * @returns string
 */
export function generateUUID() {
	function s4() {
		return Math.floor((1 + Math.random()) * 0x10000)
			.toString(16)
			.substring(1)
	}
	return `${s4() + s4()}-${s4()}-${s4()}-${s4()}-${s4()}${s4()}${s4()}`
}

/**
 * Get current etag
 * @returns string
 */
export const getETag = async () => {
	try {
		// eslint-disable-next-line no-restricted-globals
		const response = await fetch(self.location.origin, {
			method: "HEAD",
			cache: "no-cache",
		})
		return response.headers.get("etag") || response.headers.get("last-modified")
	} catch (error: any) {
		throw new Error(`Fetch failed: ${error?.message}`)
	}
}

/**
 * Get latest app version
 * @description Get the latest version of the application via network request
 * @returns {Promise<string|undefined>} Returns version string on success, undefined on failure
 */

export const getLatestAppVersion = async () => {
	try {
		// eslint-disable-next-line no-restricted-globals
		const response = await fetch(`${self.location.origin}/config.js`, {
			method: "GET",
			cache: "no-cache",
		})
		const jsContent = await response.text()
		const config = JSON.parse(jsContent.replace("window.CONFIG = ", "")) as ImportMetaEnv
		return config.DELIGHTFUL_APP_VERSION
	} catch (error: any) {
		console.error(error)
		return undefined
	}
}

// Cache regex to avoid repeated creation
const VERSION_REGEX = /^\d+\.\d+(\.\d+)?$/

/**
 * Check if there is a breaking version update between two versions
 * @param currentVersion Current version, format: x.y or x.y.z
 * @param newVersion New version, format: x.y or x.y.z
 * @returns boolean True if breaking update, false otherwise
 * @throws Error When version format is invalid
 */
export const isBreakingVersion = (currentVersion: string, newVersion: string): boolean => {
	// Validate version format
	if (!VERSION_REGEX.test(currentVersion) || !VERSION_REGEX.test(newVersion)) {
		throw new Error("Invalid version format. Expected format: x.y[.z]")
	}

	// Split version and convert to numbers
	const [currentMajor = 0, currentMinor = 0] = currentVersion.split(".").map(Number)
	const [newMajor = 0, newMinor = 0] = newVersion.split(".").map(Number)

	// Breaking update only if new version is greater than current
	return newMajor > currentMajor || (newMajor === currentMajor && newMinor > currentMinor)
}
