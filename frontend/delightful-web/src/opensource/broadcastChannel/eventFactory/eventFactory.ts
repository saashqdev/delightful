import type { BroadcastMessage } from "../broadcastChannel"

/**
 * Event data interface
 */
export interface EventData<T = any> {
	/**
	 * Event type
	 */
	type: string
	/**
	 * Event data
	 */
	data: T
	/**
	 * Metadata
	 */
	meta?: Record<string, any>
}

/**
 * Event handler type
 */
export type EventHandler<T = any> = (data: T, meta?: Record<string, any>) => void

/**
 * Event listener type
 */
export interface EventListener<T = any> {
	/**
	 * Event handler
	 */
	handler: EventHandler<T>
	/**
	 * Whether to trigger only once
	 */
	once?: boolean
}

/**
 * Event factory class
 * Used to standardize event creation and handling
 */
export class EventFactory {
	/**
	 * Event handler collection
	 */
	private eventHandlers: Map<string, EventListener[]> = new Map()

	/**
	 * Create event factory instance
	 */
	constructor() {
		// No need for event type prefix parameters
	}

	/**
	 * Check if event handler is registered
	 * @param type Event type
	 * @returns Whether the event handler is registered
	 */
	public hasEventHandler(type: string): boolean {
		return this.eventHandlers.has(type) && this.eventHandlers.get(type)!.length > 0
	}

	/**
	 * Create event object
	 * @param type Event type
	 * @param data Event data
	 * @param meta Event metadata
	 * @returns Event object
	 */
	public createEvent<T>(type: string, data: T, meta?: Record<string, any>): EventData<T> {
		return {
			type,
			data,
			meta,
		}
	}

	/**
	 * Convert to broadcast message
	 * @param event Event object
	 * @returns Broadcast message
	 */
	public toBroadcastMessage<T>(event: EventData<T>): BroadcastMessage<T> {
		return {
			type: event.type,
			payload: event.data,
		}
	}

	/**
	 * Create and convert to broadcast message
	 * @param type Event type
	 * @param data Event data
	 * @param meta Event metadata
	 * @returns Broadcast message
	 */
	public createBroadcastMessage<T>(
		type: string,
		data: T,
		meta?: Record<string, any>,
	): BroadcastMessage<T> {
		const event = this.createEvent(type, data, meta)
		return this.toBroadcastMessage(event)
	}

	/**
	 * Handle received broadcast message
	 * @param message Broadcast message
	 * @returns Processed event object, returns null if not belonging to this factory
	 */
	public handleBroadcastMessage<T>(message: BroadcastMessage<T>): EventData<T> {
		return {
			type: message.type,
			data: message.payload,
			meta: {
				timestamp: message.timestamp,
				source: message.source,
				id: message.id,
			},
		}
	}

	/**
	 * Check if message belongs to specified subtype
	 * @param message Broadcast message
	 * @param type Subtype
	 * @returns Whether it belongs to the specified subtype
	 */
	public isEventType(message: BroadcastMessage, type: string): boolean {
		return message.type === type
	}

	/**
	 * Register event handler
	 * @param type Event type
	 * @param handler Event handler
	 * @param options Options
	 */
	public on<T>(type: string, handler: EventHandler<T>, options: { once?: boolean } = {}): void {
		if (!this.eventHandlers.has(type)) {
			this.eventHandlers.set(type, [])
		}

		this.eventHandlers.get(type)!.push({
			handler: handler as EventHandler<any>,
			once: options.once,
		})
	}

	/**
	 * Register one-time event handler
	 * @param type Event type
	 * @param handler Event handler
	 */
	public once<T>(type: string, handler: EventHandler<T>): void {
		this.on(type, handler, { once: true })
	}

	/**
	 * Remove event handler
	 * @param type Event type
	 * @param handler Event handler
	 */
	public off<T>(type: string, handler?: EventHandler<T>): void {
		if (!this.eventHandlers.has(type)) {
			return
		}

		if (!handler) {
			this.eventHandlers.delete(type)
			return
		}

		const handlers = this.eventHandlers.get(type)!
		const index = handlers.findIndex((h) => h.handler === handler)
		if (index !== -1) {
			handlers.splice(index, 1)
		}

		if (handlers.length === 0) {
			this.eventHandlers.delete(type)
		}
	}

	/**
	 * Clear all event handlers
	 */
	public clearAllHandlers(): void {
		this.eventHandlers.clear()
	}

	/**
	 * Dispatch event
	 * @param type Event type
	 * @param data Event data
	 * @param meta Event metadata
	 * @returns Whether the event was successfully dispatched
	 */
	public dispatch<T>(type: string, data: T, meta?: Record<string, any>): boolean {
		if (!this.eventHandlers.has(type)) {
			return false
		}

		const handlers = [...this.eventHandlers.get(type)!]
		let hasDispatched = false

		for (const listener of handlers) {
			try {
				listener.handler(data, meta)
				hasDispatched = true

				if (listener.once) {
					this.off(type, listener.handler)
				}
			} catch (error) {
				console.error(`[EventFactory] Error dispatching event ${type}:`, error)
			}
		}

		return hasDispatched
	}

	/**
	 * Process broadcast message and dispatch to event handlers
	 * @param message Broadcast message
	 * @returns Whether the broadcast message was successfully processed
	 */
	public processMessage<T>(message: BroadcastMessage<T>): boolean {
		const event = this.handleBroadcastMessage(message)
		return this.dispatch(event.type, event.data, event.meta)
	}

	/**
	 * Get registered event types
	 * @returns Array of registered event types
	 */
	public getRegisteredEventTypes(): string[] {
		return Array.from(this.eventHandlers.keys())
	}
}
