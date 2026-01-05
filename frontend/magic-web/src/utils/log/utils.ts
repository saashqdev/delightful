import { last } from "lodash-es"

/**
 * @description Token encoding
 * @param value
 */
function encryptValue(value: string): string | undefined {
	const temp: Array<string> = value?.split(".") ?? []
	return last(temp)
}

/**
 * @description Log data filter
 * @param logs
 */
export function transformer(logs: any): any {
	try {
		if (Array.isArray(logs)) {
			// Process array elements
			return logs.map((item) => transformer(item))
		}
		const newObj: { [key: string]: any } = {}
		// eslint-disable-next-line no-restricted-syntax
		for (const [key, value] of Object.entries(logs)) {
			if (
				["authorization", "token", "access_token"].includes(key) &&
				typeof value === "string"
			) {
				// Target field found; obfuscate its value
				newObj[key] = encryptValue(value)
			} else {
				// Recurse into nested objects
				newObj[key] = transformer(value)
			}
		}
		return newObj
	} catch (error) {
		console.error("Log processing error", error)
	}
	return logs
}
