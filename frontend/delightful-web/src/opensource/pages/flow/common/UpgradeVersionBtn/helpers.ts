/**
 * Compare the size of two version numbers
 * @param version1 Version number 1, e.g. "v0", "v1", "v2"
 * @param version2 Version number 2, e.g. "v0", "v1", "v2"
 * @returns
 *   - If version1 > version2, returns 1
 *   - If version1 < version2, returns -1
 *   - If version1 === version2, returns 0
 */
export function compareNodeVersion(version1: string, version2: string): number {
	// Extract the numeric part from the version number
	const v1Number = parseInt(version1.replace("v", ""), 10)
	const v2Number = parseInt(version2.replace("v", ""), 10)

	// Compare numeric values
	if (v1Number > v2Number) {
		return 1
	}
	if (v1Number < v2Number) {
		return -1
	}
	return 0
}
