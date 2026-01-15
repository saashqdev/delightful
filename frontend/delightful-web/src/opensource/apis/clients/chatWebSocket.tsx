/* eslint-disable @typescript-eslint/no-this-alias */
import { EngineIoPacketType } from "@/const/socketio"
import type { EventType } from "@/types/chat"
import type { CommonResponse, WebsocketOpenResponse } from "@/types/request"
import type { SendResponse, WebSocketMessage } from "@/types/websocket"
import { WebSocketReadyState } from "@/types/websocket"
import { env } from "@/utils/env"
import { decodeSocketIoMessage } from "@/utils/socketio"
import { isString, isUndefined } from "lodash-es"
import Logger from "@/utils/log/Logger"
import EventBus from "@/utils/eventBus"
import { interfaceStore } from "@/opensource/stores/interface"
import { UrlUtils } from "../utils"
import { userService } from "@/services"

const logger = new Logger("chat websocket")

export type ChatWebSocketEventMap = {
	businessMessage: [{ type: EventType; payload: unknown }]
	message: [MessageEvent<any>]
	open: [Event]
	close: [CloseEvent]
	error: [Event]
	login: [
		{
			type: EventType.Login
			payload: { authorization: string; delightfulOrganizationCode: string }
		},
	]
}

/**
 * Chat WebSocket Connection Class
 * Manages WebSocket connections, heartbeat detection, and automatic reconnection
 */
export class ChatWebSocket extends EventBus {
	// WebSocket instance for maintaining connection with the server
	private socket: WebSocket | null = null

	// WebSocket server connection URL
	private url: string = env("DELIGHTFUL_SOCKET_BASE_URL") || ""

	// Current reconnection attempt counter
	private reconnectAttempts = 0

	// Maximum reconnection attempts, will stop reconnecting after exceeding this number
	private maxReconnectAttempts = 10

	// Reconnection interval time (milliseconds), wait time between each reconnection
	private reconnectInterval = 3000

	// Heartbeat detection interval time (milliseconds), periodically send ping to maintain connection
	private heartbeatInterval = 10000

	// Heartbeat timeout time (milliseconds), connection will be closed if timeout is exceeded
	private heartbeatTimeout = 2000

	// Last heartbeat time
	private lastHeartbeatTime = 0

	// Heartbeat detection timer reference, used for resource cleanup
	private heartbeatTimer: NodeJS.Timeout | null = null

	// Reconnection timer reference, used for resource cleanup
	private reconnectTimer: NodeJS.Timeout | null = null

	/**
	 * Initialize WebSocket connection
	 * @param url WebSocket server address
	 */
	constructor(url?: string) {
		super()
		if (url) this.url = url
	}

	/**
	 * Establish WebSocket connection
	 * Initialize event listeners and heartbeat detection
	 * Trigger reconnection mechanism when connection fails
	 */
	public connect(reconnect: boolean = false) {
		const that = this

		return new Promise<WebSocket | null>((resolve) => {
			interfaceStore.setIsConnecting(true)
			interfaceStore.setShowReloadButton(false)

			try {
				if (that.isConnected) {
					resolve(that.socket)
					return
				}

				const socketIoUrl = UrlUtils.transformToSocketIoUrl(that.url)
				that.socket = new WebSocket(socketIoUrl)
				that.initEventHandlers(reconnect)
				that.startHeartbeat()

				const callback = () => {
					that.socket?.removeEventListener("open", callback)
					resolve(that.socket)
				}
				that.socket?.addEventListener("open", callback)
			} catch (error) {
				resolve(that.reconnect())
				// reject(error)
			}
		}).then((res) => {
			interfaceStore.setIsConnecting(false)
			return res
		})
	}

	openCallback(event: Event) {
		this.emit("open", event)

		// Reset reconnection counter
		this.reconnectAttempts = 0
		// Clear reconnection timer if exists
		if (this.reconnectTimer) {
			clearTimeout(this.reconnectTimer)
			this.reconnectTimer = null
		}
		logger.log("Connection successful", event)

		// Trigger connection recovery event to handle message queue during offline period
		if (this.reconnectAttempts > 0) {
			// If reconnection is successful, trigger connection recovery event
			window.dispatchEvent(new CustomEvent("websocket:reconnected"))
		}
	}

	closeCallback(event: CloseEvent) {
		logger.log("Connection closed", event)
		this.reconnect()
		this.emit("close", event)
	}

	errorCallback(error: Event) {
		logger.error("Connection error", error)
		this.emit("error", error)
	}

	messageCallback(event: MessageEvent<any>) {
		this.emit("message", event)
		try {
			const engineIoPacketType = event.data.slice(0, 1)
			switch (engineIoPacketType) {
				case EngineIoPacketType.OPEN:
					this.handleOpenPacket(event)
					break
				case EngineIoPacketType.PONG:
					this.handlePongPacket()
					break
				case EngineIoPacketType.MESSAGE:
					this.receiveMessagePacket(event)
					break
				default:
					break
			}
		} catch (error) {
			logger.error("onmessage error:", error)
		}
	}

	/**
	 * Initialize WebSocket event handlers
	 * Includes handling logic for connection success, disconnection, errors, and message reception
	 */
	private initEventHandlers(reconnect: boolean) {
		if (!this.socket) return

		this.openCallback = (event: Event) => this.openCallback({ ...event, reconnect } as Event)

		this.socket.addEventListener("open", (event: Event) => {
			this.emit("open", { ...event, reconnect })

			// Reset reconnection counter
			this.reconnectAttempts = 0
			// Clear reconnection timer if exists
			if (this.reconnectTimer) {
				clearTimeout(this.reconnectTimer)
				this.reconnectTimer = null
			}
			logger.log("Connection successful", event)
		})
		// Connection closed callback: update state and attempt reconnection
		this.socket.addEventListener("close", (event: CloseEvent) => {
			logger.log("Connection closed", event)
			this.reconnect()
			this.emit("close", event)
		})
		// Error handler callback: log error and update state
		this.socket.addEventListener("error", (error: Event) => {
			logger.error("Connection error", error)
			this.emit("error", error)
		})
		// Message reception handler: parse message and dispatch to corresponding handler
		this.socket.addEventListener("message", (event: MessageEvent<any>) => {
			this.emit("message", event)
			try {
				const engineIoPacketType = event.data.slice(0, 1)
				switch (engineIoPacketType) {
					case EngineIoPacketType.OPEN:
						this.handleOpenPacket(event)
						break
					case EngineIoPacketType.PONG:
						this.handlePongPacket()
						break
					case EngineIoPacketType.MESSAGE:
						this.receiveMessagePacket(event)
						break
					default:
						break
				}
			} catch (error) {
				logger.error("onmessage error:", error)
			}
		})
	}

	// private removeEventHandlers() {
	// 	this.socket?.removeEventListener("open", this.openCallback)
	// 	this.socket?.removeEventListener("close", this.closeCallback)
	// 	this.socket?.removeEventListener("error", this.errorCallback)
	// 	this.socket?.removeEventListener("message", this.messageCallback)
	// }

	/**
	 * Handle message
	 * @param event Message event
	 */
	private receiveMessagePacket(event: MessageEvent<any>) {
		decodeSocketIoMessage(event.data.slice(1)).then((packet) => {
			const { data: packetData } = packet
			if (Array.isArray(packetData) && packetData.length === 2) {
				const [type, payload] = packetData as [EventType, string]
				const parsedPayload = isString(payload) ? JSON.parse(payload) : payload
				this.emit("businessMessage", { type, payload: parsedPayload })
			}
		})
	}

	/**
	 * Handle heartbeat response message
	 */
	private handlePongPacket() {
		if (this.lastHeartbeatTime) {
			const timeout = Date.now() - this.lastHeartbeatTime
			if (this.heartbeatTimeout && timeout > this.heartbeatTimeout) {
				logger.log("Heartbeat timeout", timeout)
				// this.socket?.close()
			}
			this.lastHeartbeatTime = 0
		}
	}

	/**
	 * Handle connection success packet
	 * @param event Message event
	 */
	private handleOpenPacket(event: MessageEvent<any>) {
		const data = JSON.parse(event.data.slice(1)) as WebsocketOpenResponse
		this.heartbeatInterval = data.pingInterval
		this.heartbeatTimeout = data.pingTimeout
		this.startHeartbeat()
	}

	/**
	 * Send heartbeat packet
	 */
	private sendHeartbeatPacket() {
		if (this.socket?.readyState === WebSocketReadyState.OPEN) {
			this.socket.send(EngineIoPacketType.PING) // Send heartbeat packet
			this.lastHeartbeatTime = Date.now()
		}
	}

	/**
	 * Start heartbeat detection mechanism
	 * Periodically send ping messages to maintain connection activity
	 * Interval time is controlled by heartbeatInterval configuration
	 */
	private startHeartbeat() {
		if (this.heartbeatTimer) {
			clearInterval(this.heartbeatTimer)
			this.heartbeatTimer = null
		}

		this.sendHeartbeatPacket()
		this.heartbeatTimer = setInterval(() => {
			this.sendHeartbeatPacket()
		}, this.heartbeatInterval)
	}

	/**
	 * Stop heartbeat detection
	 * Clean up heartbeat timer resources to prevent memory leaks
	 */
	private stopHeartbeat() {
		if (this.heartbeatTimer) {
			clearInterval(this.heartbeatTimer)
			this.heartbeatTimer = null // Release timer reference
		}
	}

	/**
	 * Execute automatic reconnection strategy
	 * When connection is abnormally disconnected, reconnect according to configured interval and count
	 * Stop attempting after reconnection count reaches the limit
	 */
	private reconnect() {
		return new Promise<WebSocket | null>((resolve, reject) => {
			userService.clearLastLogin()

			if (this.reconnectAttempts >= this.maxReconnectAttempts) {
				logger.log("Maximum reconnection attempts reached")
				interfaceStore.setShowReloadButton(true)
				interfaceStore.setIsConnecting(false)
				reject(new Error("Maximum reconnection attempts reached"))
				return
			}

			// Clear existing timer to avoid duplication
			if (this.reconnectTimer) {
				clearTimeout(this.reconnectTimer)
			}

			const that = this

			// Set new reconnection timer
			this.reconnectTimer = setTimeout(() => {
				logger.log(
					`Attempting to reconnect (${that.reconnectAttempts + 1}/${
						that.maxReconnectAttempts
					})`,
				)
				that.reconnectAttempts += 1
				resolve(that.connect(true)) // Execute actual connection operation
			}, this.reconnectInterval)
		})
	}

	/**
	 * Send message method
	 * @param message Message object to be sent (will be automatically serialized to JSON)
	 */
	public send(message: any) {
		if (this.isConnected) {
			this.socket!.send(message)
		} else {
			throw new Error("WebSocket not connected")
		}
	}

	/**
	 * Send message and wait for response
	 * @param message Message content
	 * @param ackId Response ID
	 * @returns Response data
	 */
	public async sendAsync<D>(message: WebSocketMessage, ackId?: number) {
		const that = this
		let { socket } = that

		if (!socket) {
			socket = await that.connect()
		}

		return new Promise<SendResponse<D>>((resolve, reject) => {
			async function handler(e: Event) {
				const event = e as MessageEvent
				const engineIoPacketType = event.data.slice(0, 1)
				if (engineIoPacketType === EngineIoPacketType.MESSAGE) {
					const { id: ackIdResponse, data: reponse } = await decodeSocketIoMessage(
						event.data.slice(1),
					)

					if (Array.isArray(reponse)) {
						if (
							reponse.length === 1 &&
							(!isUndefined(ackId) ? ackId === ackIdResponse : true)
						) {
							// Response after active send
							const data = reponse[0] as CommonResponse<D>
							if (data.code === 1000) {
								resolve({
									id: ackIdResponse,
									data: data.data,
								})
							} else {
								reject(data)
								logger.error("ws response error:", data)
							}
							socket?.removeEventListener("message", handler)
						}
					}
				}
			}

			socket?.addEventListener("message", handler)

			that.send(message)
		})
	}

	/**
	 * Actively close connection
	 * Clean up all timer resources and terminate WebSocket connection
	 * Used for page unload or user active disconnection scenarios
	 */
	public close() {
		this.stopHeartbeat()
		if (this.reconnectTimer) {
			clearTimeout(this.reconnectTimer)
			this.reconnectTimer = null
		}
		this.socket?.close() // Safely close connection
		this.socket = null
	}

	/**
	 * Get WebSocket connection status
	 * Returns false if WebSocket instance does not exist
	 * Returns the readyState of WebSocket instance if it exists
	 * If result is true, TypeScript will consider this.socket as WebSocket type
	 * @returns Connection status
	 */
	public get isConnected(): boolean {
		return !!this.socket && this.socket.readyState === WebSocketReadyState.OPEN
	}

	public getWebSocket() {
		return this.socket
	}

	async apiSend<D>(message: WebSocketMessage, ackId?: number) {
		if (!this.isConnected) {
			await Promise.race([
				this.connect(),
				new Promise((reject) =>
					setTimeout(() => reject("websocket connection timeout"), 3000),
				),
			])
		}

		return new Promise<SendResponse<D>>((resolve, reject) => {
			const socket = this.getWebSocket()
			let timeoutId: NodeJS.Timeout | null = null

			async function handler(e: Event) {
				const event = e as MessageEvent
				const engineIoPacketType = event.data.slice(0, 1)
				if (engineIoPacketType === EngineIoPacketType.MESSAGE) {
					const { id: ackIdResponse, data: reponse } = await decodeSocketIoMessage(
						event.data.slice(1),
					)

					// Only handle corresponding response messages
					if (!ackId || (ackId && ackIdResponse === ackId)) {
						try {
							if (
								Array.isArray(reponse) &&
								(reponse[0] as CommonResponse<D>).code === 1000
							) {
								socket?.removeEventListener("message", handler)
								if (timeoutId) {
									clearTimeout(timeoutId)
								}
								resolve({
									id: ackIdResponse,
									data: reponse[0].data,
								})
							}
						} catch (error) {
							socket?.removeEventListener("message", handler)
							if (timeoutId) {
								clearTimeout(timeoutId)
							}
							reject(error)
						}
					}
				}
			}

			socket?.addEventListener("message", handler)

			this.send(message)

			// Set timeout timer
			timeoutId = setTimeout(() => {
				socket?.removeEventListener("message", handler)
				reject(new Error("Send timeout, request did not receive response"))
			}, 3000)
		})
	}
}

const chatWebSocket = new ChatWebSocket()

chatWebSocket.on("open", () => {
	interfaceStore.setReadyState(WebSocketReadyState.OPEN)
})

chatWebSocket.on("close", () => {
	interfaceStore.setReadyState(WebSocketReadyState.CLOSED)
})

chatWebSocket.on("error", () => {
	interfaceStore.setReadyState(WebSocketReadyState.CLOSED)
})

export default chatWebSocket
