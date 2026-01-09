/**
 * Check if a string is possibly JSON
 * @param str The string to check
 * @returns Whether it is possibly JSON format
 */
export const isJsonString = (str: string): boolean => {
    if (typeof str !== "string") return false
    str = str.trim()
    return (
        (str.startsWith("{") && str.endsWith("}")) || (str.startsWith("[") && str.endsWith("]"))
    )
}

/**
 * Format a value that might be a JSON string
 * @param value The string that might be JSON
 * @returns Formatted JSON string
 */
export const formatValue = (value: string): string => {
    if (!isJsonString(value)) return value

    try {
        // Try to parse JSON
        const parsed = JSON.parse(value)
        // Reformat to beautified JSON string
        return JSON.stringify(parsed, null, 2)
    } catch {
        // If parsing fails, return original value
        return value
    }
}

/**
 * Check if it's a complex JSON string (object or array)
 * @param value The string to check
 * @returns Whether it's a complex JSON structure
 */
export const isComplexValue = (value: string): boolean => {
    return isJsonString(value) && value.length > 20
} 
