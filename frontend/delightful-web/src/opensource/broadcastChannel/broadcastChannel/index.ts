// import { EventEmitter } from "events"
import EventFactory from "../eventFactory"
import { v4 as uuidv4 } from "uuid"

/**
 * BroadcastChannel message type
 */
export interface BroadcastMessage<T = any> {
	/**
	 * Message type
	 */
	type: string
	/**
	 * Message payload
	 */
	payload: T
	/**
	 * Message ID for identifying messages
	 */
	id?: string
	/**
	 * Message send time
	 */
	timestamp?: number
	/**
	 * Message source
	 */
	source?: string
}

/**
 * Event listener type
 */
type EventListener<T = any> = (data: T) => void

/**
 * BroadcastChannel event type
 */
export type BroadcastEventMap = {
	message: BroadcastMessage
	error: any
	close: void
}

/**
 * BroadcastChannel class for implementing communication between multiple tabs
 * Based on browser native BroadcastChannel API
 */
export class DelightfulBroadcastChannel {
	private channel: BroadcastChannel | null = null
	private channelName: string
	private connected: boolean = false
	private tabId: string
	private eventListeners: Record<string, Array<EventListener>> = {}

	/**
	 * Create a broadcast channel instance
	 * @param channelName Channel name, channels with the same name can communicate with each other
	 */
	constructor(channelName: string) {
		this.channelName = channelName
		this.tabId = uuidv4()
		this.init()
	}

	/**
	 * Initialize broadcast channel
	 */
	private init() {
		try {
			this.channel = new BroadcastChannel(this.channelName)
			console.log("channel", this.channel)
			this.connected = true

			// Listen to message events
			this.channel.onmessage = (event) => {
				console.log("onmessage", event)
				this.handleMessage(event)
			}

			// Listen to error events
			this.channel.onmessageerror = (event) => {
				this.dispatchEvent("error", event)
			}

			// 发送连接事件，通知其他标签页
			this.send({
				type: "system:connected",
				payload: {
					tabId: this.tabId,
					timestamp: Date.now(),
				},
			})
		} catch (error: unknown) {
			const errorMessage = error instanceof Error ? error.message : String(error)
			console.error(`[BroadcastChannel] Failed to initialize channel: ${errorMessage}`)
		}
	}

	/**
	 * Handle received messages
	 * @param event Message event
	 */
	private handleMessage(event: MessageEvent) {
		const message = event.data as BroadcastMessage

		// If it is a message sent by itself, ignore it
		if (message.source === this.tabId) {
			return
		}

		// If message specifies type, trigger corresponding type event
		if (message.type) {
			EventFactory.dispatch(message.type, message.payload)
		}
	}

	/**
	 * Send message to broadcast channel
	 * @param message Message to send
	 */
	send(message: BroadcastMessage) {
		if (!this.connected || !this.channel) {
			console.error("[BroadcastChannel] Channel not connected or not initialized")
			return
		}

		// Add source identifier and timestamp
		const enrichedMessage: BroadcastMessage = {
			...message,
			source: this.tabId,
			timestamp: message.timestamp || Date.now(),
			id: message.id || uuidv4(),
		}

		this.channel.postMessage(enrichedMessage)
	}

	/**
	 * Send message of a specific type
	 * @param type Message type
	 * @param payload Message payload
	 */
	sendMessage<T>(type: string, payload: T) {
		this.send({
			type,
			payload,
		})
	}

	/**
	 * Internal event dispatch method
	 * @param type Event type
	 * @param data Event data
	 */
	private dispatchEvent(type: string, data?: any): boolean {
		if (!this.eventListeners[type]) {
			return false
		}

		this.eventListeners[type].forEach((listener) => listener(data))
		return true
	}

	/**
	 * Listen to specific type of message
	 * @param type Message type
	 * @param listener Listener callback
	 */
	on(type: string, listener: EventListener): this {
		if (!this.eventListeners[type]) {
			this.eventListeners[type] = []
		}
		this.eventListeners[type].push(listener)
		return this
	}

	/**
	 * Listen to specific type of message once
	 * @param type Message type
	 * @param listener Listener callback
	 */
	once(type: string, listener: EventListener): this {
		const onceWrapper: EventListener = (data) => {
			listener(data)
			this.off(type, onceWrapper)
		}
		return this.on(type, onceWrapper)
	}

	/**
	 * Remove specific event listener
	 * @param type Event type
	 * @param listener Listener function
	 */
	off(type: string, listener: EventListener): this {
		if (this.eventListeners[type]) {
			this.eventListeners[type] = this.eventListeners[type].filter((l) => l !== listener)
		}
		return this
	}

	/**
	 * Close broadcast channel
	 */
	close() {
		if (this.connected && this.channel) {
			// Send disconnect notification
			this.send({
				type: "system:disconnected",
				payload: {
					tabId: this.tabId,
					timestamp: Date.now(),
				},
			})

			this.channel.close()
			this.connected = false
			this.dispatchEvent("close")
		}
	}

	/**
	 * Get current tab ID
	 */
	getTabId(): string {
		return this.tabId
	}

	/**
	 * Get connection status
	 */
	isConnected(): boolean {
		return this.connected
	}
}
