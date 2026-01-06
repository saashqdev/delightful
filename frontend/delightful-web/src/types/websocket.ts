export const enum WebSocketReadyState {
	/** Connecting */
	CONNECTING = 0,
	/** Connection open */
	OPEN = 1,
	/** Closing */
	CLOSING = 2,
	/** Connection closed */
	CLOSED = 3,
}
export type WebSocketMessage = string | ArrayBuffer | SharedArrayBuffer | Blob | ArrayBufferView
export interface SendResponse<D> {
	id: number | undefined
	data: D
}
