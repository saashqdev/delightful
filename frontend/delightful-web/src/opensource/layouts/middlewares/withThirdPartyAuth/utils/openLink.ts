import dd from "dingtalk-jsapi"

/**
 * Open link in third-party platform
 * @param url Link URL
 * @param platform Platform
 */
export const thirdPartyOpenLink = (url: string, platform?: "dingtalk") => {
	switch (platform) {
		case "dingtalk":
			dd.openLink({
				url,
				onSuccess: () => {
					window.close()
				},
				onFail: () => {
					thirdPartyOpenLink(url)
				},
			})
			break
		default:
			window.open(url, "_blank")
			window.close()
	}
}
