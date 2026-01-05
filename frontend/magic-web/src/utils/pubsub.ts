/**
 * Simple publish-subscribe system
 */

type Listener = (...args: any[]) => void

class PubSub {
	private events: Record<string, Listener[]> = {}

	/**
	 * Subscribe to an event
	 * @param event Event name
	 * @param callback Listener callback
	 */
	subscribe(event: string, callback: Listener): void {
		if (!this.events[event]) {
			this.events[event] = []
		}
		this.events[event].push(callback)
	}

	/**
	 * Unsubscribe from an event
	 * @param event Event name
	 * @param callback Optional specific callback. If omitted, all subscriptions for the event are removed
	 */
	unsubscribe(event: string, callback?: Listener): void {
		if (!this.events[event]) {
			return
		}

		if (callback) {
			this.events[event] = this.events[event].filter((cb) => cb !== callback)
		} else {
			delete this.events[event]
		}
	}

	/**
	 * Publish an event
	 * @param event Event name
	 * @param args Arguments passed to listeners
	 */
	publish(event: string, ...args: any[]): void {
		if (!this.events[event]) {
			return
		}

		this.events[event].forEach((callback) => {
			try {
				callback(...args)
			} catch (err) {
				console.error(`Error in pubsub event handler for "${event}":`, err)
			}
		})
	}

	/**
	 * Clear all event subscriptions
	 */
	clear(): void {
		this.events = {}
	}
}

// Create and export singleton instance
const pubsub = new PubSub()
export default pubsub
