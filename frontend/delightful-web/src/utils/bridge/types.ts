/**
 * Bridge event types
 */
export const enum BridgeEventType {
	GetChartImage = "getChartImage",
	InjectChartOptions = "injectChartOptions",
	SwitchPage = "switchPage",
	InjectMindMapData = "injectMindMapData",
	GetMindMapImage = "getMindMapImage",
	InjectMermaidData = "injectMermaidData",
	GetMermaidImage = "getMermaidImage",
}

/**
 * Shared page types
 */
export const enum SharedPageType {
	Chart = "chart",
	MindMap = "mind_map",
	Mermaid = "mermaid",
}

/**
 * Bridge calls to native events
 */
export const enum BridgeCallNativeEvent {
	getChartImage = "GetChartImage",
	getMindMapImage = "GetMindMapImage",
	getMermaidImage = "GetMermaidImage",
}

/**
 * Bridge event payloads
 */
export type BridgeEventData = {
	[BridgeEventType.GetChartImage]: GetChartImageParams
	[BridgeEventType.InjectChartOptions]: InjectChartOptionsParams
	[BridgeEventType.SwitchPage]: SwitchPageParams
	[BridgeEventType.InjectMindMapData]: InjectMindMapDataParams
	[BridgeEventType.GetMindMapImage]: GetMindMapImageParams
	[BridgeEventType.InjectMermaidData]: InjectMermaidDataParams
	[BridgeEventType.GetMermaidImage]: GetMermaidImageParams
}

/**
 * Inject Mermaid data params
 */
export interface InjectMermaidDataParams {
	data: string
}

/**
 * Get Mermaid image params
 */
export interface GetMermaidImageParams {
	data: string
	width: number
	height: number
}

/**
 * Get mind map image params
 */
export interface GetMindMapImageParams {
	data: string
	width: number
	height: number
}

/**
 * Inject mind map data params
 */
export interface InjectMindMapDataParams {
	data: string
}

/**
 * Switch page params
 */
export interface SwitchPageParams {
	mode: SharedPageType
}

/**
 * Get chart image params
 */
export interface GetChartImageParams {
	chartOptions: object
	width: number
	height: number
}

/**
 * Inject chart options params
 */
export interface InjectChartOptionsParams {
	chartOptions: object
}

export interface BridgeEvent<T extends BridgeEventType = BridgeEventType> {
	nativeDataResponse: BridgeEventData[T]
	nativeConfig: {
		appCallName: T
		requestId: string
		timeStamp: number
	}
}
export interface WebViewJavascriptBridge {
	init: (callback: (message: any, responseCallback: any) => void) => void
	callHandler: (nativeCallName: string, data: string) => void
	registerHandler: (
		nativeCallName: string,
		callback: (data: string, responseCallback: any) => void,
	) => void
	send: (message: string) => void
	didReadyIframe: () => void
}

declare global {
	interface Window {
		WebViewJavascriptBridge: WebViewJavascriptBridge
		bridge?: {
			callNative: (nativeCallName: string, data: any) => void
		}
		_AMapSecurityConfig?: {
			securityJsCode?: string
			serviceHost?: string
		}
	}
}
