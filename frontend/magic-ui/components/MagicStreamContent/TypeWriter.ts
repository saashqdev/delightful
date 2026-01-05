// Typewriter queue
export class Typewriter {
	private queue: string[] = []

	public consuming = false

	private timer: ReturnType<typeof setTimeout> | undefined

	constructor(private onConsume: (str: string) => void) {}

	// Dynamically adjust output speed
	dynamicSpeed() {
		const speed = 2000 / this.queue.length
		if (speed > 200) {
			return 200
		}
		return speed
	}

	// Speed control for fast output mode
	fastSpeed() {
		const speed = 500 / this.queue.length
		if (speed > 50) {
			return 50
		}
		return speed < 10 ? 10 : speed // Ensure at least a 10ms delay
	}

	// Add a string to the queue
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

	// Consume the next character
	next(fast = false) {
		this.consume()
		if (this.queue.length === 0) {
			this.consuming = false
			clearTimeout(this.timer)
			return
		}
		// Set per-frame speed based on queue size and consume via timer
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
		// Instead of consuming all at once, consume one by one at higher speed
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
