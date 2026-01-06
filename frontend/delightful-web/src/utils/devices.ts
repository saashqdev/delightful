import UAParser from "ua-parser-js"
import CryptoJS from "@/utils/crypto"
import type { i18n } from "i18next"
import { magic } from "@/enhance/magicElectron"

/** * Whether macOS system (including iPhone) * */
export const isMac = (() => {
	return /macintosh|mac os x/i.test(navigator.userAgent)
})()

/** * Whether Windows system * */
export const isWindows = (() => {
	return /windows|win32/i.test(navigator.userAgent)
})()

/** * Whether mobile device * */
export const isMobile = (() => {
	return /android|ios|iphone|ipad/i.test(navigator.userAgent)
})()

/**
 * @description Get browser fingerprint, device info, OS name and version
 */
export async function getDeviceInfo(i18n: i18n) {
	// Platform mapping
	const platformMapping = {
		DingTalk: i18n.t("device.dingTalk", { ns: "interface" }),
		DingTalkAvoid: i18n.t("device.dingTalk", { ns: "interface" }),
		TeamshareWebAPP: `Teamshare {${i18n.t("device.app")}}`,
	}

	// Instantiate UA parser
	const ua = new UAParser()
	const { userAgent } = window.navigator
	const currentPlatform = Object.keys(platformMapping).find(
		(platform) => userAgent.indexOf(platform) > -1,
	)
	const { device, browser, os } = ua.getResult()

	// Build device info
	let deviceInfo = [
		device.vendor,
		device.model,
		`${currentPlatform ? platformMapping[currentPlatform as keyof typeof platformMapping] : ""}`,
	]
		.filter((attr) => !!attr)
		.join(" ")

	if (currentPlatform === "TeamshareWebAPP") deviceInfo = platformMapping[currentPlatform]

	// Build browser info
	const browserInfo = `${browser.name} ${browser.version}`

	if (magic?.os?.getSystemInfo) {
		const systemInfo = await magic?.os?.getSystemInfo?.()
		return {
			id: systemInfo?.mac,
			name: systemInfo?.systemVersion,
			os: os.name || "",
			os_version: os.version || "",
		}
	}

	return {
		id: CryptoJS.MD5encryption(JSON.stringify(ua.getResult())),

		// On mobile/tablet/platform, prefer device info; otherwise show browser info
		name:
			device.type === "mobile" || device.type === "tablet" || currentPlatform
				? deviceInfo || i18n.t("device.unknown")
				: browserInfo,
		os: os.name || "",
		os_version: os.version || "",
	}
}
