import { WebSocketReadyState } from "@/types/websocket"
import { platformKey } from "@/utils/storage"
import { makeAutoObservable } from "mobx"

class InterfaceStore {
	readyState: WebSocket["readyState"] = WebSocketReadyState.CLOSED
	isSwitchingOrganization: boolean = false
	isConnecting: boolean = false
	showReloadButton: boolean = false
	isShowStartPageKey = platformKey("isShowStartPage")
	chatPanelSizeKey = platformKey("chatPanel")

	/**
	 * Default height for the chat input
	 */
	chatInputDefaultHeight = 240

	/**
	 * Default width for the chat sidebar
	 */
	chatSiderDefaultWidth = 240

	/**
	 * Whether to show the start page
	 */
	isShowStartPage: boolean = JSON.parse(localStorage.getItem(this.isShowStartPageKey) ?? "true")

	constructor() {
		const chatPanelSize = localStorage.getItem(this.chatPanelSizeKey)
		if (chatPanelSize) {
			const json = JSON.parse(chatPanelSize)
			this.chatInputDefaultHeight = json.chatInputDefaultHeight
			this.chatSiderDefaultWidth = json.chatSiderDefaultWidth
		}

		makeAutoObservable(this)
	}

	closeStartPage() {
		this.isShowStartPage = false
		localStorage.setItem(this.isShowStartPageKey, "false")
	}

	setReadyState(readyState: WebSocket["readyState"]) {
		this.readyState = readyState
	}

	setIsSwitchingOrganization(isSwitchingOrganization: boolean) {
		this.isSwitchingOrganization = isSwitchingOrganization
	}

	setIsConnecting(isConnecting: boolean) {
		this.isConnecting = isConnecting
	}

	setShowReloadButton(showReloadButton: boolean) {
		this.showReloadButton = showReloadButton
	}

	/**
	 * Persist chat panel dimensions
	 */
	cacheChatPanelSize() {
		localStorage.setItem(
			this.chatPanelSizeKey,
			JSON.stringify({
				chatInputDefaultHeight: this.chatInputDefaultHeight,
				chatSiderDefaultWidth: this.chatSiderDefaultWidth,
			}),
		)
	}

	/**
	 * Set default chat input height
	 */
	setChatInputDefaultHeight(height: number) {
		this.chatInputDefaultHeight = height
		this.cacheChatPanelSize()
	}

	/**
	 * Set default chat sidebar width
	 */
	setChatSiderDefaultWidth(width: number) {
		this.chatSiderDefaultWidth = width
		this.cacheChatPanelSize()
	}
}

// Create global singleton
export const interfaceStore = new InterfaceStore()
