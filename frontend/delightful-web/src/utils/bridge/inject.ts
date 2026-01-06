import { nanoid } from "nanoid"
import type { BridgeEvent, WebViewJavascriptBridge } from "./types"
import eventBus from "./eventBus"

function DelightfulJSBridge(callback: (bridge: WebViewJavascriptBridge) => void) {
	try {
		if (window.WebViewJavascriptBridge) {
			console.warn("WebViewJavascriptBridge is already loaded")
			callback(window.WebViewJavascriptBridge)
		} else {
			console.warn("WebViewJavascriptBridge is not loaded")
			document.addEventListener(
				"WebViewJavascriptBridgeReady",
				() => {
					callback(window.WebViewJavascriptBridge)
				},
				false,
			)
		}
	} catch (ex) {
		console.error("Failed to register WebViewJavascriptBridge", ex)
	}
}

DelightfulJSBridge((bridge) => {
	bridge.init(() => {
		console.log("bridge init")
	})

	/* Register nativeResponse so the app can reply to JS-triggered nativeRequest APIs */
	bridge.registerHandler("nativeResponse", (data) => {
		const request = JSON.parse(data)

		console.warn("Received app message", request)
		// responseCallback(`Return data here: ${data}`);
	})

	/* Register nativeCall so the app can proactively invoke related APIs */
	bridge.registerHandler("nativeCall", (data: string) => {
		console.warn("Received app message", data)
		try {
			const event: BridgeEvent = JSON.parse(data)
			console.log("Parsed data: ", event)
			console.log("eventBus", eventBus)
			eventBus.emit(event.nativeConfig.appCallName, event.nativeDataResponse)
		} catch (error) {
			console.error("Bridge event JSON parse failed", error)
		}
		// responseCallback?.("Return data here");
	})
})

window.bridge = {
	callNative(nativeCallName, data) {
		const requestData = {
			nativeConfig: {
				nativeCallName,
				requestId: nanoid(),
				timeStamp: new Date().getTime(),
				nativeCallType: "request",
			},
			nativeRequest: data ?? {},
		}

		DelightfulJSBridge((bridge) => {
			if (typeof bridge === "undefined") {
				return
			}
			console.log("bridge", bridge)
			bridge.callHandler("nativeRequest", JSON.stringify(requestData))
		})
	},
}
