import { ReflectMessageType, MessageType } from "./const"
import { generateUUID, getLatestAppVersion } from "./utils"

export const portList: any[] = [] // Store ports
export const visiblePorts: any[] = [] // Track visibility state per port

// Wrap intervalId to keep it mutable
export const state = {
	intervalId: null as NodeJS.Timeout | null,
}

// Send message to all other windows
function sendMessage(port: { id: any }, message: { type: string }) {
	portList.forEach((o) => {
		if (o.id !== port.id) {
			o.postMessage(message)
		}
	})
}

// Broadcast to all windows
function broadcast(message: { type: string; data?: any; message?: string }) {
	portList.forEach((port) => {
		port.postMessage(message)
	})
}

declare global {
	interface Window {
		onconnect: (e: { ports: any[] }) => void
	}
}

export const onconnect = function onconnect(e: { ports: any[] }) {
	const port = e.ports[0]
	port.id = generateUUID()
	// Store port
	portList.push(port)
	// Listen for incoming messages
	// eslint-disable-next-line @typescript-eslint/no-shadow
	port.onmessage = async function onmessage(e: any) {
		// Retrieve data
		const data = e.data || {}
		const type = data.type || ""
		switch (type) {
			case MessageType.START: // Start polling
				// Prevent duplicate additions
				if (!visiblePorts.find((o) => o === port.id)) {
					visiblePorts.push(port.id)
				}
				if (state.intervalId !== null) {
					clearInterval(state.intervalId)
				}
				try {
					const res = await getLatestAppVersion()
					broadcast({
						type: ReflectMessageType.REFLECT_GET_LATEST_VERSION,
						data: res,
					})
					state.intervalId = setInterval(async () => {
						try {
							const latestVersion = await getLatestAppVersion()
							broadcast({
								type: ReflectMessageType.REFLECT_GET_LATEST_VERSION,
								data: latestVersion,
							})
						} catch (error) {
							broadcast({
								type: ReflectMessageType.REFLECT_GET_LATEST_VERSION,
								data: undefined,
								message: error instanceof Error ? error.message : "Unknown error",
							})
						}
					}, 30000)
				} catch (error) {
					broadcast({
						type: ReflectMessageType.REFLECT_GET_LATEST_VERSION,
						data: undefined,
						message: error instanceof Error ? error.message : "Unknown error",
					})
				}
				break
			case MessageType.STOP: // Stop polling
				{
					const visibleIndex = visiblePorts.indexOf(port.id)
					if (visibleIndex > -1) visiblePorts.splice(visibleIndex, 1)
				}
				// Stop polling only when all pages are hidden
				if (state.intervalId !== null && visiblePorts.length === 0) {
					clearInterval(state.intervalId)
					state.intervalId = null
				}
				break
			case MessageType.CLOSE: // Close current port
				{
					const index = portList.indexOf(port)
					if (index > -1) {
						portList.splice(index, 1)
					}
				}
				break
			case MessageType.REFRESH: // Proactively notify other pages to refresh
				sendMessage(port, {
					type: ReflectMessageType.REFLECT_REFRESH,
				})
				break
			default:
				broadcast({ type: "error", message: "Unknown message type" })
				break
		}
	}
}
