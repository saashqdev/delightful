declare let window: any

let source: any = null
let playTime: number = 0 // Relative time, records pause position
let playStamp: number = 0 // Timestamp (absolute) at start or after pause
let context: any = null
let analyser: any = null

let audioData: any = null
// let hasInit: boolean = false;           // Whether already initialized
let isPaused: boolean = false
let totalTime: number = 0
let endplayFn: any = () => {}

/**
 * Initialize
 */
function init(): void {
	context = new (window.AudioContext || window.webkitAudioContext)()
	analyser = context.createAnalyser()
	analyser.fftSize = 2048 // Represents the size of the frequency domain storage
}

/**
 * play
 * @returns {Promise<{}>}
 */
function playAudio(): Promise<{}> {
	isPaused = false

	return context.decodeAudioData(
		audioData.slice(0),
		(buffer: any) => {
			source = context.createBufferSource()

			// Bind event for playback end
			source.onended = () => {
				if (!isPaused) {
					// This event is also triggered when paused
					// Calculate total audio duration
					totalTime = context.currentTime - playStamp + playTime
					endplayFn()
				}
			}

			// Set data
			source.buffer = buffer
			// Connect to analyser, still use the recording one, because playback cannot record simultaneously
			source.connect(analyser)
			analyser.connect(context.destination)
			source.start(0, playTime)

			// Record current timestamp for use when pausing
			playStamp = context.currentTime
		},
		(e: any) => {
			throw new Error(e)
		},
	)
}

// Destroy source, since the source produced by decodeAudioData cannot be used after each stop, pausing also means destroying, and needs to restart next time.
function destroySource() {
	if (source) {
		source.stop()
		source = null
	}
}

export default class Player {
	/**
	 * play record
	 * @static
	 * @param {ArrayBuffer} arraybuffer
	 * @memberof Player
	 */
	static play(arraybuffer: any): Promise<{}> {
		if (!context) {
			// Initialize on first playback
			init()
		}
		this.stopPlay()
		// Cache playback data
		audioData = arraybuffer
		totalTime = 0

		return playAudio()
	}

	/**
	 * Pause playback of recording
	 * @memberof Player
	 */
	static pausePlay(): void {
		destroySource()
		// Multiple pauses need to be accumulated
		playTime += context.currentTime - playStamp
		isPaused = true
	}

	/**
	 * Resume playback of recording
	 * @memberof Player
	 */
	static resumePlay(): Promise<{}> {
		return playAudio()
	}

	/**
	 * Stop playback
	 * @memberof Player
	 */
	static stopPlay() {
		playTime = 0
		audioData = null

		destroySource()
	}

	static destroyPlay() {
		this.stopPlay()
	}

	static getAnalyseData() {
		const dataArray = new Uint8Array(analyser.frequencyBinCount)
		// Copy data into dataArray.
		analyser.getByteTimeDomainData(dataArray)

		return dataArray
	}

	/**
	 * Add event binding for recording playback completion
	 *
	 * @static
	 * @param {*} [fn=function() {}]
	 * @memberof Player
	 */
	static addPlayEnd(fn: any = () => {}) {
		endplayFn = fn
	}

	// Get the duration already played
	static getPlayTime(): number {
		const pTime = isPaused ? playTime : context.currentTime - playStamp + playTime

		return totalTime || pTime
	}
}
