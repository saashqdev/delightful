import { userStore } from "@/opensource/models/user"
import { SocketIoPacketType, EngineIoPacketType } from "@/const/socketio"
import type { Packet } from "socket.io-parser"
import { Decoder } from "socket.io-parser"
import { configStore } from "@/opensource/models/config"
import { getCurrentLang, normalizeLocale } from "./locale"

export const decoder = new Decoder()

export function decodeSocketIoMessage(data: string) {
	return new Promise<Packet>((resolve) => {
		decoder.on("decoded", (packet) => {
			resolve(packet)
		})

		decoder.add(data)
	})
}

/**
 * Build a custom payload
 * @param data Payload data
 * @returns Payload with context
 */
function genPayload(data: unknown, context: Record<string, unknown> = {}) {
	const { organizationCode } = userStore.user
	return {
		context: {
			// request_id: nanoid(),
			timestamp: Date.now(),
			authorization: userStore.user.authorization ?? "",
			organization_code: organizationCode ?? "",
			language: getCurrentLang(normalizeLocale(configStore.i18n.language)),
			// Reserved for future message encryption/decryption
			signature: "",
			...context,
		},
		data,
	}
}

/**
 * Wrap a WebSocket message
 * @param type WebSocket event type
 * @param data JSON payload
 * @param ackId ACK ID
 * @param context Context
 * @param socketioPacketType Socket.IO packet type
 * @param nsp Namespace
 * @param engineioPackType Engine.IO packet type
 * @returns Encoded packet
 */
export const encodeSocketIoMessage = (
	type: string,
	data: unknown,
	ackId: number = 1,
	context: Record<string, unknown> = {},
	socketioPacketType: SocketIoPacketType = SocketIoPacketType.EVENT,
	nsp: string = "/im",
	engineioPackType: EngineIoPacketType = EngineIoPacketType.MESSAGE,
) => {
	// Socket.IO packet structure
	// <packet type>[<# of binary attachments>-][<namespace>,][<acknowledgment id>][JSON-stringified payload without binary]
	// Each Socket.IO packet is wrapped in an Engine.IO message packet, so they are prefixed with the character "4" on the wire.
	// More Info: https://github.com/socketio/socket.io-protocol?tab=readme-ov-file#sending-and-receiving-data-1
	const namespace = nsp ? `${nsp},` : ""
	const payload = genPayload(data, context)

	// @ts-ignore

	// console.log(
	// 	"%c Send message: ",
	// 	"background-color: yellow; color: #000;",
	// 	payload?.data?.message?.type,
	// 	payload,
	// )

	return `${engineioPackType}${socketioPacketType}${namespace}${ackId}${JSON.stringify([
		type,
		payload,
	])}`
}
