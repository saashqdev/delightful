/**
 * Format a number string with thousand separators
 * @param number Numeric value
 * @returns Formatted value
 */
export function splitNumber(
	number: number,
	delimiterPosition: number = 3,
	char: string = ",",
): string {
	// Convert the numeric value to a string
	const numStr: string = number.toString()
	// Split integer and fractional parts
	const parts: string[] = numStr.split(".")

	// Add thousand separators to the integer part
	parts[0] = parts[0].replace(new RegExp(`\\B(?=(\\d{${delimiterPosition}})+(?!\\d))`, "g"), char)

	// Return the formatted value
	return parts.join(".")
}

/**
 * Format values above 1000 with a K suffix
 * @param number Value to format
 * @returns Formatted string, empty for invalid input
 */
export function formatToK(number: number): string {
	// Ensure the parameter is a valid number
	if (Number.isNaN(number) || !Number.isFinite(number)) {
		return ""
	}

	// Return as-is if under 1000
	if (number < 1000) {
		return number.toString()
	}

	// Calculate value in K units (floored)
	const kValue = Math.floor(number / 1000)

	// Return the formatted value with K suffix
	return `${kValue}K`
}
