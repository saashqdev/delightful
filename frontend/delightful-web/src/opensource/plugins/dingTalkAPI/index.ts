/** Load dingTalk plugin on demand */
export async function getDingTalkApi(): Promise<typeof import("dingtalk-jsapi")> {
	return import("dingtalk-jsapi")
}

/**
 * Compare two versions
 * @param version1 Version 1
 * @param version2 Version 2
 * @returns {number}
 * - 1: version1 > version2
 * - -1: version1 < version2
 * - 0: version1 = version2
 */
export function compareVersions(version1: string, version2: string): number {
	// Normalize version strings, removing prefixes like 'v'
	const v1 = version1.replace(/^v/, "")
	const v2 = version2.replace(/^v/, "")

	// Split into numeric segments
	const v1Parts = v1.split(".").map(Number)
	const v2Parts = v2.split(".").map(Number)

	// Ensure both arrays have equal length
	const maxLength = Math.max(v1Parts.length, v2Parts.length)
	while (v1Parts.length < maxLength) v1Parts.push(0)
	while (v2Parts.length < maxLength) v2Parts.push(0)

	// Compare segment by segment
	for (let i = 0; i < maxLength; i += 1) {
		if (v1Parts[i] > v2Parts[i]) return 1
		if (v1Parts[i] < v2Parts[i]) return -1
	}

	return 0
}
