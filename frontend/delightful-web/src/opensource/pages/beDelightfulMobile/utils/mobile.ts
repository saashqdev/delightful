/**
 * Check if current environment is an APP
 * @returns {boolean} Whether it's an APP environment
 */
export function isInApp(): boolean {
	// Detect APP environment via user agent string
	const userAgent = window.navigator.userAgent.toLowerCase()

	// Check for specific APP identifiers
	// Can be adjusted based on actual APP identifiers
	const appIdentifiers = [
		"delightful-android", // Android APP identifier
		"delightful-ios", // iOS APP identifier
		// Add other possible APP identifiers
	]

	// Check for webview identifier
	const isWebView = userAgent.includes("wv") || userAgent.includes("webview")

	// Check if contains specific APP identifier
	const hasAppIdentifier = (identifier: string) => userAgent.includes(identifier)

	// Can also determine via custom protocols or global variables
	// For example: APP may set a specific variable on the window object
	const hasCustomAppFlag =
		typeof (window as any).isNativeApp !== "undefined" ||
		typeof (window as any).ReactNativeWebView !== "undefined"
	return isWebView || appIdentifiers.some(hasAppIdentifier) || hasCustomAppFlag
}
