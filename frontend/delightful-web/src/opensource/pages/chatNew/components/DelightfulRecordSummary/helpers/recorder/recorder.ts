/* eslint-disable class-methods-use-this */
/* eslint-disable @typescript-eslint/no-unused-expressions */
import { compress, encodePCM, encodeWAV } from "./transform"
import Player from "./player"
import BaseRecorder from "./base"
import { download, downloadWAV } from "./download"

// Constructor function parameter format
interface RecorderConfig {
	sampleBits?: number // sample bit depth
	sampleRate?: number // sample rate
	numChannels?: number // number of channels
	compiling?: boolean // whether to record and playback simultaneously
}

class Recorder extends BaseRecorder {
	private isrecording: boolean = false // whether currently recording

	private ispause: boolean = false // whether paused

	private isplaying: boolean = false // whether currently playing

	/**
	 * @param {Object} options contains the following three parameters:
	 * sampleBits, sample bit depth, typically 8 or 16, default is 16
	 * sampleRate, sample rate, typically 11025, 16000, 22050, 24000, 44100, 48000, defaults to browser's native sample rate
	 * numChannels, channels, 1 or 2
	 */
	constructor(options: RecorderConfig = {}) {
		super(options)
	}

	/**
	 * Re-configure settings
	 *
	 * @param {RecorderConfig} [options={}]
	 * @memberof Recorder
	 */
	public setOption(options: RecorderConfig = {}) {
		this.setNewOption(options)
	}

	/**
	 * Start the recording
	 */
	async start() {
		if (this.isrecording) {
			// already recording, not allowed
			return
		}

		this.isrecording = true

		await this.startRecord()
	}

	/**
	 * Pause the recording
	 */
	pause(): void {
		if (this.isrecording && !this.ispause) {
			this.ispause = true
			// can only pause when currently not paused
			this.pauseRecord()
		}
	}

	/**
	 * Resume recording
	 */
	resume(): void {
		if (this.isrecording && this.ispause) {
			this.ispause = false
			this.resumeRecord()
		}
	}

	/**
	 * Stop recording
	 *
	 * @memberof Recorder
	 */
	stop(): void {
		if (this.isrecording) {
			this.isrecording = false
			this.ispause = false
			this.stopRecord()
		}
	}

	/**
	 * Play recording
	 */
	play(): void {
		this.stop()
		// close previous audio playback
		this.isplaying = true

		if (this.onplay) this.onplay()
		Player.addPlayEnd(this.onplayend) // register callback event after playback completes

		const dataV = this.getWAV()

		if (dataV.byteLength > 44) {
			Player.play(dataV.buffer) // play
		}
	}

	/**
	 * Get how long has been played
	 */
	getPlayTime(): number {
		return Player.getPlayTime()
	}

	/**
	 * Pause playing recording
	 *
	 * @memberof Recorder
	 */
	pausePlay(): void {
		if (this.isrecording || !this.isplaying) {
			// currently recording or not playing, pause is invalid
			return
		}

		this.isplaying = false
		this.onpauseplay && this.onpauseplay()
		Player.pausePlay()
	}

	/**
	 * Resume playing recording
	 *
	 * @memberof Recorder
	 */
	resumePlay(): void {
		if (this.isrecording || this.isplaying) {
			// currently recording or already playing or not started, resume is invalid
			return
		}

		this.isplaying = true
		this.onresumeplay && this.onresumeplay()
		Player.resumePlay()
	}

	/**
	 * Stop playback
	 *
	 * @memberof Recorder
	 */
	stopPlay(): void {
		if (this.isrecording) {
			// currently recording, stop playback is invalid
			return
		}

		this.isplaying = false
		this.onstopplay && this.onstopplay()
		Player.stopPlay()
	}

	destroy(): Promise<object> {
		Player.destroyPlay()

		return this.destroyRecord()
	}

	/**
	 * Get current recorded PCM audio data
	 *
	 * @returns[DataView]
	 * @memberof Recorder
	 */
	// getWholeData() {
	//     return this.tempPCM;
	// }

	/**
	 * Get remaining new data, excluding previous data obtained by getNextData
	 *
	 * @returns [DataView]
	 * @memberof Recorder
	 */
	// getNextData() {
	//     let length = this.tempPCM.length,
	//         data = this.tempPCM.slice(this.offset);

	//     this.offset = length;

	//     return data;
	// }

	/**
	 * Get current recording waveform data,
	 * call frequency controlled externally.
	 *
	 * @memberof Recorder
	 */
	getRecordAnalyseData(): any {
		return this.getAnalyseData()
	}

	/**
	 * Get waveform data during recording playback,
	 *
	 * @memberof Recorder
	 */
	getPlayAnalyseData(): any {
		// recording and playback are not allowed simultaneously, so the recording's analyser node is reused.
		return Player.getAnalyseData()
	}

	getPCM(): any {
		// get PCM data
		let data: any = this.getData()
		// compress or expand based on input/output ratio
		data = compress(data, this.inputSampleRate, this.outputSampleRate)
		// re-encode according to sample bit depth
		return encodePCM(data, this.oututSampleBits, this.littleEdian)
	}

	/**
	 * Get PCM format blob data
	 *
	 * @returns { blob }  PCM format blob data
	 * @memberof Recorder
	 */
	getPCMBlob(): any {
		return new Blob([this.getPCM()])
	}

	/**
	 * Get WAV encoded binary data (dataview)
	 *
	 * @returns {dataview}  WAV encoded binary data
	 * @memberof Recorder
	 */
	getWAV(): any {
		const pcmTemp = this.getPCM()

		// PCM with 44-byte header becomes WAV format
		return encodeWAV(
			pcmTemp,
			this.inputSampleRate,
			this.outputSampleRate,
			this.config.numChannels!,
			this.oututSampleBits,
			this.littleEdian,
		)
	}

	/**
	 * Get WAV audio blob data
	 *
	 * @returns { blob }    WAV format blob data
	 * @memberof Recorder
	 */
	getWAVBlob(): any {
		const result = new Blob([this.getWAV()], { type: "audio/wav" })
		return result
	}

	getWavBlobByData(data: any): any {
		// compress or expand based on input/output ratio
		data = compress(data, this.inputSampleRate, this.outputSampleRate)
		// re-encode according to sample bit depth
		const pcmTemp = encodePCM(data, this.oututSampleBits, this.littleEdian)
		// PCM with 44-byte header becomes WAV format
		const WAV = encodeWAV(
			pcmTemp,
			this.inputSampleRate,
			this.outputSampleRate,
			this.config.numChannels!,
			this.oututSampleBits,
			this.littleEdian,
		)
		const WAVBlob = new Blob([WAV], { type: "audio/wav" })
		return WAVBlob
	}

	/**
	 * Get single WAV audio blob data
	 */
	getOnceWAVBlob(): any {
		const data = this.getOnceData()
		return this.getWavBlobByData(data)
	}

	/**
	 * Get left and right channel data
	 *
	 * @returns [DataView]
	 */
	getChannelData(): any {
		const all = this.getPCM()
		const length = all.byteLength
		const { littleEdian } = this
		const res = { left: null, right: null } as { left: DataView | null; right: DataView | null }

		if (this.config.numChannels === 2) {
			// dual channel, split
			const lD = new DataView(new ArrayBuffer(length / 2))
			const rD = new DataView(new ArrayBuffer(length / 2))
			// dual channel, need to split data

			if (this.config.sampleBits === 16) {
				for (let i = 0; i < length / 2; i += 2) {
					lD.setInt16(i, all.getInt16(i * 2, littleEdian), littleEdian)
					rD.setInt16(i, all.getInt16(i * 2 + 2, littleEdian), littleEdian)
				}
			} else {
				for (let i = 0; i < length / 2; i += 2) {
					lD.setInt8(i, all.getInt8(i * 2))
					rD.setInt8(i, all.getInt8(i * 2 + 1))
				}
			}

			res.left = lD
			res.right = rD
		} else {
			// single channel
			res.left = all
		}

		return res
	}

	/**
	 * Download recording WAV data
	 *
	 * @param {string} [name='recorder']    renamed filename
	 * @memberof Recorder
	 */
	downloadWAV(name: string = "recorder"): void {
		// console.log("Compare lengths:",this.cacheBuffer.length, this.lBuffer.length)
		const wavBlob = this.getWAVBlob()

		downloadWAV(wavBlob, name)
	}

	/**
	 * Download cached WAV data (debug whether data transmission is normal)
	 */
	downloadCacheWAV(name = "once-recorder"): void {
		// console.log("Compare lengths:",this.cacheBuffer.length, this.lBuffer.length)
		const totalLength = this.cacheBuffer.reduce((total, segment) => total + segment.length, 0)
		const lData = new Float32Array(totalLength)
		const rData = new Float32Array(0)
		let offset = 0
		for (let i = 0; i < this.cacheBuffer.length; i += 1) {
			lData.set(this.cacheBuffer[i], offset)
			offset += this.cacheBuffer[i].length
		}
		const wavBlob = this.getWavBlobByData({
			left: lData,
			right: rData,
		})

		downloadWAV(wavBlob, name)
	}

	/**
	 * Generic download interface
	 */
	download(blob: Blob[], name: string, type: string): void {
		download(blob, name, type)
	}
}

export default Recorder
