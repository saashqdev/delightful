/**
 * Check whether debug mode is enabled
 * @returns
 */
export const isDebug = () => {
	const urlParams = new URLSearchParams(window.location.search)
	return urlParams.get("debug") === "true"
}
