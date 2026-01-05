// Typewriter queue
export class Typewriter {
	private queue: string[] = []

	public consuming = false

	private timer: NodeJS.Timeout | undefined

	constructor(private onConsume: (str: string) => void) {}

	// Dynamic output speed control
	dynamicSpeed() {
		const speed = 2000 / this.queue.length
		if (speed > 200) {
			return 200
		}
		return speed
	}

	// Speed control in fast output mode
	fastSpeed() {
		const speed = 500 / this.queue.length
		if (speed > 50) {
			return 50
		}
		return speed < 10 ? 10 : speed // Ensure at least 10ms delay
	}

	// Add string to queue
	add(str: string) {
		if (!str) return
		this.queue.push(...str.split(""))
	}

	// Consume one character
	consume() {
		if (this.queue.length > 0) {
			const str = this.queue.shift()
			if (str) {
				this.onConsume(str)
			}
		}
	}

	// Consume next character
	next(fast = false) {
		this.consume()
		if (this.queue.length === 0) {
			this.consuming = false
			clearTimeout(this.timer)
			return
		}
		// Timer-driven rate based on queue size
		this.timer = setTimeout(
			() => {
				this.consume()
				if (this.consuming) {
					this.next(fast)
				}
			},
			fast ? this.fastSpeed() : this.dynamicSpeed(),
		)
	}

	// Start consuming the queue
	start() {
		this.consuming = true
		this.next()
	}

	// Finish consuming the queue
	done() {
		this.consuming = true
		clearTimeout(this.timer)
		// Consume one-by-one at a faster rate instead of all at once
		this.next(true)
	}

	// Pause
	stop() {
		this.consuming = false
		clearTimeout(this.timer)
	}

	// Resume
	resume() {
		this.consuming = true
		this.next()
	}
}
