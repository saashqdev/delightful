/** Event name */
type EventName = string

export default class EventEmitter<T extends (...args: any[]) => void> {
	observers: Record<EventName, T>

	constructor() {
		// Store event listener key-value pairs
		this.observers = {}
	}

	// Register event listener
	on(eventName: EventName, listener: T) {
		if (!this.observers[eventName]) {
			this.observers[eventName] = listener
		}
	}

	// Trigger event, callback all listeners
	emit(eventName: EventName, ...args: Array<any>) {
		const listener = this.observers[eventName]
		if (listener) {
			listener.apply(this, args)
		}
	}

	// Remove specified listener for specified event
	off(eventName: EventName) {
		if (Object.keys(this.observers).includes(eventName)) {
			delete this.observers[eventName]
		}
	}
}




