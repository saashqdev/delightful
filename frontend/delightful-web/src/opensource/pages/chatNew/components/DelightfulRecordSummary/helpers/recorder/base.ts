/* eslint-disable */

import { message } from "antd"

declare let window: any
declare let Math: any
declare let navigator: any
declare let Promise: any

// Constructor function parameter format
interface recorderConfig {
	sampleBits?: number // sample bit depth
	sampleRate?: number // sample rate
	numChannels?: number // number of channels
	compiling?: boolean // whether to record and playback simultaneously
}

export default class BaseRecorder {
	private context: any

	protected config!: recorderConfig // configuration

	private analyser: any

	private audioInput: any

	protected inputSampleRate: number // input sample rate

	protected inputSampleBits: number = 16 // input sample bit depth

	protected outputSampleRate!: number // output sample rate

	protected oututSampleBits!: number // output sample bit depth

	private source: any // audio input

	private recorder: any

	private stream: any // stream

	protected littleEdian: boolean // whether little-endian byte order

	protected fileSize: number = 0 // recording size in bytes

	protected duration: number = 0 // recording duration

	private needRecord: boolean = true // due to Safari issues, this approach is used instead of disconnect/connect

	public size: number = 0 // total length of recording file

	public lBuffer: Array<Float32Array> = [] // PCM audio data collector (left channel)

	public rBuffer: Array<Float32Array> = [] // PCM audio data collector (right channel)

	public cacheBuffer: Array<Float32Array> = [] // PCM cache data

	// recording time, parameter is the duration that has been recorded
	public onprocess!: (duration: number) => void

	// alternative function for onprocess, keeps original onprocess for backward compatibility
	public onprogress!: (payload: { duration: number; fileSize: number; vol: number }) => void

	public onplay!: () => void // audio playback callback

	public onpauseplay!: () => void // audio pause callback

	public onresumeplay!: () => void // audio resume playback callback

	public onstopplay!: () => void // audio stop playback callback

	public onplayend!: () => void // audio normal playback end

	public lastSliceIndex?: number // last slice position, defaults to start position

	/**
	 * @param {Object} options contains the following three parameters:
	 * sampleBits, sample bit depth, typically 8 or 16, default is 16
	 * sampleRate, sample rate, typically 11025, 16000, 22050, 24000, 44100, 48000, defaults to browser's native sample rate
	 * numChannels, channels, 1 or 2
	 */
	constructor(options: recorderConfig = {}) {
		// temporary audioContext to get input sample rate
		const context = new (window.AudioContext || window.webkitAudioContext)()

		this.inputSampleRate = context.sampleRate // get current input sample rate

		// set output configuration
		this.setNewOption(options)

		// determine byte order
		this.littleEdian = (function () {
			const buffer = new ArrayBuffer(2)
			new DataView(buffer).setInt16(0, 256, true)
			return new Int16Array(buffer)[0] === 256
		})()
		// getUserMedia compatibility
		BaseRecorder.initUserMedia()
	}

	clearBuffers(): void {
		this.size = 0
		this.lBuffer.length = 0
		this.rBuffer.length = 0
		this.lastSliceIndex = 0
	}

	protected setNewOption(options: recorderConfig = {}) {
		this.config = {
			// sample bit depth: 8, 16
			sampleBits: ~[8, 16].indexOf(options.sampleBits!) ? options.sampleBits : 16,
			// sample rate
			sampleRate: ~[8000, 11025, 16000, 22050, 24000, 44100, 48000].indexOf(
				options.sampleRate!,
			)
				? options.sampleRate
				: this.inputSampleRate,
			// number of channels: 1 or 2
			numChannels: ~[1, 2].indexOf(options.numChannels!) ? options.numChannels : 1,
			// whether real-time encoding is needed, disabled by default, to be handled with web worker in future
			// compiling: !!options.compiling || false,   // removed for now
		}
		// configure sampling parameters
		this.outputSampleRate = this.config.sampleRate! // output sample rate
		this.oututSampleBits = this.config.sampleBits! // output sample bit depth: 8, 16
	}

	handleMediaError(err: any) {
		if (err.name === "NotFoundError" || err.name === "DevicesNotFoundError") {
			message.warning("No microphone device found")
		} else if (err.name === "NotAllowedError" || err.name === "PermissionDeniedError") {
			message.warning("Please enable microphone permission in your browser to record audio")
		} else {
			message.warning(
				"Recording feature is temporarily unavailable, please check your device",
			)
		}
	}

	/**
	 * Start recording
	 *
	 * @memberof Recorder
	 */
	async startRecord() {
		if (this.context) {
			// close previous recording instance because it may cache a small amount of previous audio data
			this.destroyRecord()
		}
		// initialize
		this.initRecorder()
		let stream = null

		navigator.getUserMedia =
			navigator.getUserMedia ||
			navigator.webkitGetUserMedia ||
			navigator.mozGetUserMedia ||
			navigator.msGetUserMedia
		if ((!navigator.mediaDevices?.getUserMedia && !navigator.getUserMedia) || !this.context) {
			// message.warning(`${i18next.t("chat.recording_summary.title")}`)
			message.warning(
				"Current browser does not have recording permission, please use a different browser",
			)
			return
		}
		if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
			try {
				stream = await navigator.mediaDevices.getUserMedia({
					audio: true,
					video: false,
				})
			} catch (e) {
				this.handleMediaError(e)
				return
			}
		} else {
			try {
				stream = await navigator.getUserMedia({
					audio: true,
					video: false,
				})
			} catch (e) {
				this.handleMediaError(e)
				return
			}
		}

		// audioInput represents audio source node
		// stream is the external (e.g., microphone) audio output obtained via navigator.getUserMedia, which serves as input for us
		this.audioInput = this.context.createMediaStreamSource(stream)
		this.stream = stream

		// audioInput is the audio source, connected to the recorder processing node
		this.audioInput.connect(this.analyser)
		this.analyser.connect(this.recorder)
		// this.audioInput.connect(this.recorder);
		// recorder processing node connected to speakers
		this.recorder.connect(this.context.destination)
	}

	/**
	 * Pause recording
	 *
	 * @memberof Recorder
	 */
	pauseRecord(): void {
		this.needRecord = false
	}

	/**
	 * Resume recording
	 *
	 * @memberof Recorder
	 */
	resumeRecord(): void {
		this.needRecord = true
	}

	/**
	 * Stop recording
	 *
	 */
	stopRecord(): void {
		this.audioInput && this.audioInput.disconnect()
		this.source && this.source.stop()
		this.recorder.disconnect()
		this.analyser.disconnect()
		this.needRecord = true
	}

	/**
	 * Destroy recording instance
	 *
	 */
	destroyRecord(): Promise<{}> {
		this.clearRecordStatus()
		// end stream
		this.stopStream()

		return this.closeAudioContext()
	}

	getAnalyseData() {
		const dataArray = new Uint8Array(this.analyser.frequencyBinCount)
		// copy data to dataArray
		this.analyser.getByteTimeDomainData(dataArray)

		return dataArray
	}

	// get recording data
	getData() {
		const data: any = this.flat()

		return data
	}

	// get sliced recording data
	getOnceData() {
		const data: any = this.flatOnce()

		return data
	}

	/**
	 * Clear recording status
	 *
	 */
	private clearRecordStatus() {
		this.lBuffer.length = 0
		this.rBuffer.length = 0
		this.size = 0
		this.fileSize = 0
		this.audioInput = null
		this.duration = 0
	}

	private flat() {
		let lData = null,
			rData = new Float32Array(0) // right channel defaults to 0

		// create container for storing data
		if (1 === this.config.numChannels) {
			lData = new Float32Array(this.size)
		} else {
			lData = new Float32Array(this.size / 2)
			rData = new Float32Array(this.size / 2)
		}
		// merge
		let offset = 0 // offset calculation

		// convert 2D data to 1D data
		// left channel
		for (let i = 0; i < this.lBuffer.length; i++) {
			lData.set(this.lBuffer[i], offset)
			this.cacheBuffer.push(this.lBuffer[i])
			offset += this.lBuffer[i].length
		}
		console.log("flat", this.cacheBuffer.length, this.lBuffer.length)

		offset = 0
		// right channel
		for (let i = 0; i < this.rBuffer.length; i++) {
			rData.set(this.rBuffer[i], offset)
			offset += this.rBuffer[i].length
		}

		return {
			left: lData,
			right: rData,
		}
	}

	/**
	 * Convert 2D array to 1D
	 *
	 * @private
	 * @returns  {float32array}     audio PCM binary data
	 * @memberof Recorder
	 */
	private flatOnce() {
		let lData = null
		let rData = new Float32Array(0) // right channel defaults to 0

		const start = this.lastSliceIndex || 0 // last slice position
		const end = this.lBuffer.length // end of current buffer

		console.log("slice index:", start, end)

		// create container for storing data
		const totalLength = this.lBuffer.slice(start, end).reduce((sum, segment) => {
			console.log("single segment length:", segment.length)
			return sum + segment.length
		}, 0)
		if (this.config.numChannels === 1) {
			lData = new Float32Array(totalLength)
		} else {
			lData = new Float32Array(totalLength / 2)
			rData = new Float32Array(totalLength / 2)
		}

		let offset = 0 // offset calculation

		// convert 2D data to 1D data
		// left channel
		for (let i = start; i < end; i++) {
			lData.set(this.lBuffer[i], offset)
			this.cacheBuffer.push(this.lBuffer[i])
			offset += this.lBuffer[i].length
		}
		console.log("flat", this.cacheBuffer.length, this.lBuffer.length)

		// right channel
		if (this.config.numChannels === 2) {
			offset = 0 // reset offset
			for (let i = start; i < end; i++) {
				rData.set(this.rBuffer[i], offset)
				offset += this.rBuffer[i].length
			}
		}

		// update last slice position
		this.lastSliceIndex = end

		return {
			left: lData,
			right: rData,
		}
	}

	/**
	 * Initialize recording instance
	 */
	private initRecorder(): void {
		// clear data
		this.clearRecordStatus()

		this.context = new (window.AudioContext || window.webkitAudioContext)()

		this.analyser = this.context.createAnalyser() // recording analysis node
		this.analyser.fftSize = 2048 // represents frequency domain storage size

		// first parameter indicates the sampling collection size; onaudioprocess will be triggered once after this amount is collected, typically 1024, 2048, 4096, etc., generally set to 4096
		// second and third parameters are input and output channel count respectively; keep them consistent
		const createScript = this.context.createScriptProcessor || this.context.createJavaScriptNode
		this.recorder = createScript.apply(this.context, [
			4096,
			this.config.numChannels,
			this.config.numChannels,
		])

		// audio collection
		this.recorder.onaudioprocess = (e: any) => {
			if (!this.needRecord) {
				return
			}
			// left channel data
			// getChannelData returns Float32Array type PCM data
			const lData = e.inputBuffer.getChannelData(0)
			let rData = null
			let vol = 0 // volume percentage

			this.lBuffer.push(new Float32Array(lData))
			// console.log("this.lBuffer.length",this.lBuffer.length);

			this.size += lData.length

			// check if right channel data exists
			if (this.config.numChannels === 2) {
				rData = e.inputBuffer.getChannelData(1)
				this.rBuffer.push(new Float32Array(rData))

				this.size += rData.length
			}

			// real-time encoding during recording is temporarily not supported
			// if (this.config.compiling) {
			//     let pcm = this.transformIntoPCM(lData, rData);

			//     this.tempPCM.push(pcm);
			//     // calculate recording size
			//     this.fileSize = pcm.byteLength * this.tempPCM.length;
			// } else {
			// calculate recording size
			this.fileSize =
				Math.floor(this.size / Math.max(this.inputSampleRate / this.outputSampleRate, 1)) *
				(this.oututSampleBits / 8)
			// }
			// why separate calculation here: during record-then-encode, all data is processed together,
			// but during record-and-encode, only 4096 samples are processed, causing fractional bit differences

			// calculate volume percentage
			vol = Math.max.apply(Math, lData) * 100
			// record duration statistics
			this.duration += 4096 / this.inputSampleRate
			// duration callback
			this.onprocess && this.onprocess(this.duration)
			// duration and volume callback
			this.onprogress &&
				this.onprogress({
					duration: this.duration,
					fileSize: this.fileSize,
					vol,
					// data: this.tempPCM,     // all current PCM data, caller controls increment
				})
		}
	}

	/**
	 * Stop stream (this removes the recording indicator from the browser)
	 * @private
	 * @memberof Recorder
	 */
	private stopStream() {
		if (this.stream && this.stream.getTracks) {
			this.stream.getTracks().forEach((track: { stop: () => any }) => track.stop())
			this.stream = null
		}
	}

	/**
	 * close compatibility solution
	 * older browsers like Firefox 30 may not have close method
	 */
	private closeAudioContext() {
		if (this.context && this.context.close && this.context.state !== "closed") {
			return this.context.close()
		}
		return new Promise((resolve: () => void) => {
			resolve()
		})
	}

	// getUserMedia version compatibility
	static initUserMedia() {
		if (navigator.mediaDevices === undefined) {
			navigator.mediaDevices = {}
		}

		if (navigator.mediaDevices.getUserMedia === undefined) {
			navigator.mediaDevices.getUserMedia = function (constraints: any) {
				const getUserMedia =
					navigator.getUserMedia ||
					navigator.webkitGetUserMedia ||
					navigator.mozGetUserMedia

				if (!getUserMedia) {
					return Promise.reject(new Error("Browser does not support getUserMedia!"))
				}

				return new Promise(function (resolve: any, reject: any) {
					getUserMedia.call(navigator, constraints, resolve, reject)
				})
			}
		}
	}

	static getPermission(): Promise<{}> {
		this.initUserMedia()

		return navigator.mediaDevices
			.getUserMedia({ audio: true })
			.then((stream: { getTracks: () => any[] }) => {
				stream && stream.getTracks().forEach((track) => track.stop())
			})
	}
}
