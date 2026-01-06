//@ts-ignore
import SnowFlakeId from "snowflake-id"

/**
 * Random integer generator
 */
export const random = (min: number, max: number) => Math.floor(Math.random() * (max - min + 1)) + min

/**
 * Generate a random string of a given length and character set
 */
export const randomString = (len = 64, type = "default", prexfix = "") => {
	const strMap: Record<string, string> = {
		default: "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz",
		number: "0123456789",
		lowerCase: "abcdefghijklmnopqrstuvwxyz",
		upperCase: "ABCDEFGHIJKLMNOPQRSTUVWXYZ"
	}
	let result = ""

	const str = strMap[type]
	len -= prexfix.length
	while (len--) {
		result += str[random(0, str.length - 1)]
	}
	return prexfix + result
}

// Snowflake ID generator
const snowflake = new SnowFlakeId({
	mid: Math.floor(Math.random() * 1e10),
	offset: (2021 - 1970) * 365 * 24 * 3600 * 1000
})

export function generateSnowFlake (prefix = "") {
	if (prefix) {
		// return prefix + md5(snowflake.generate())
		// Stop using snowflake + md5 combo (too long); switch to 8-char random string
		return randomString(8)
	} else {
		return snowflake.generate()
	}
}

export default snowflake
