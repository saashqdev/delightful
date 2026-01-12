/* eslint-disable @typescript-eslint/naming-convention */
interface dataview {
	byteLength: number
	buffer: {
		byteLength: number
	}
	getUint8: any
}

/**
 * Data merge and compress
 * Compress data according to input and output sample rates,
 * for example, if input sample rate is 48k and we need (output) 16k, since 48k and 16k have a 3x relationship,
 * we take 1 sample every 3 samples from input data
 *
 * @param {float32array} data       PCM data in range [-1, 1]
 * @param {number} inputSampleRate  input sample rate
 * @param {number} outputSampleRate output sample rate
 * @returns  {float32array}         compressed binary data
 */
export function compress(
	data: { left: any; right: any },
	inputSampleRate: number,
	outputSampleRate: number,
) {
	// compress based on sample rate
	const rate = inputSampleRate / outputSampleRate
	const compression = Math.max(rate, 1)
	const lData = data.left
	const rData = data.right
	const length = Math.floor((lData.length + rData.length) / rate)
	const result = new Float32Array(length)
	let index = 0
	let j = 0

	// loop to take one sample every compression interval
	while (index < length) {
		// floor because compression ratio may not be an integer
		const temp = Math.floor(j)

		result[index] = lData[temp]
		index += 1

		if (rData.length) {
			/*
			 * dual channel handling
			 * e.inputBuffer.getChannelData(0) gets left channel 4096 sample data, 1 is right channel data,
			 * here we need to combine into LRLRLR format for proper playback, so processing is needed
			 */
			result[index] = rData[temp]
			index += 1
		}

		j += compression
	}
	// return compressed one-dimensional data
	return result
}

/**
 * Write str string starting at offset position in data
 * @param {TypedArrays} data    binary data
 * @param {Number}      offset  offset
 * @param {String}      str     string
 */
function writeString(data: DataView, offset: number, str: string): void {
	for (let i = 0; i < str.length; i += 1) {
		data.setUint8(offset + i, str.charCodeAt(i))
	}
}

/**
 * Convert to encoding in the corresponding format we need
 *
 * @param {float32array} bytes      PCM binary data
 * @param {number}  sampleBits      sample bit depth
 * @param {boolean} littleEdian     whether little-endian byte order
 * @returns {dataview}              PCM binary data
 */
export function encodePCM(bytes: string | any[], sampleBits: number, littleEdian: boolean = true) {
	let offset = 0
	const dataLength = bytes.length * (sampleBits / 8)
	const buffer = new ArrayBuffer(dataLength)
	const data = new DataView(buffer)

	// write sampling data
	if (sampleBits === 8) {
		for (let i = 0; i < bytes.length; i += 1, offset += 1) {
			// range [-1, 1]
			const s = Math.max(-1, Math.min(1, bytes[i]))
			// 8-bit sampling is divided into 2^8=256 parts, range is 0-255;
			// for 8-bit, negative numbers *128, positive numbers *127, then shift up by 128 (+128) to get data in [0,255] range.
			let val = s < 0 ? s * 128 : s * 127
			val = +val + 128
			data.setInt8(offset, val)
		}
	} else {
		for (let i = 0; i < bytes.length; i += 1, offset += 2) {
			const s = Math.max(-1, Math.min(1, bytes[i]))
			// 16-bit is divided into 2^16=65536 parts, range is -32768 to 32767
			// since our collected data is in range [-1,1], to convert to 16-bit, multiply negative numbers by 32768, positive numbers by 32767, to get data in range [-32768,32767].
			data.setInt16(offset, s < 0 ? s * 0x8000 : s * 0x7fff, littleEdian)
		}
	}

	return data
}

/**
 * Encode WAV, generally WAV format adds a 44-byte file header before PCM file,
 * so here we just need to add it before PCM data.
 *
 * @param {DataView} bytes           PCM binary data
 * @param {number}  inputSampleRate  input sample rate
 * @param {number}  outputSampleRate output sample rate
 * @param {number}  numChannels      number of channels
 * @param {number}  oututSampleBits  output sample bit depth
 * @param {boolean} littleEdian      whether little-endian byte order
 * @returns {DataView}               WAV binary data
 */
export function encodeWAV(
	bytes: dataview,
	inputSampleRate: number,
	outputSampleRate: number,
	numChannels: number,
	oututSampleBits: number,
	littleEdian: boolean = true,
) {
	const sampleRate = outputSampleRate > inputSampleRate ? inputSampleRate : outputSampleRate // when output sample rate is larger, still use input value
	const sampleBits = oututSampleBits
	const buffer = new ArrayBuffer(44 + bytes.byteLength)
	const data = new DataView(buffer)
	const channelCount = numChannels // channels
	let offset = 0

	// resource exchange file identifier
	writeString(data, offset, "RIFF")
	offset += 4
	// total bytes from next address to end of file, i.e., file size - 8
	data.setUint32(offset, 36 + bytes.byteLength, littleEdian)
	offset += 4
	// WAV file flag
	writeString(data, offset, "WAVE")
	offset += 4
	// waveform format flag
	writeString(data, offset, "fmt ")
	offset += 4
	// filter bytes, generally 0x10 = 16
	data.setUint32(offset, 16, littleEdian)
	offset += 4
	// format category (PCM form sampling data)
	data.setUint16(offset, 1, littleEdian)
	offset += 2
	// number of channels
	data.setUint16(offset, channelCount, littleEdian)
	offset += 2
	// sample rate, samples per second, indicates playback speed of each channel
	data.setUint32(offset, sampleRate, littleEdian)
	offset += 4
	// waveform data transfer rate (average bytes per second) channels × sample rate × sample bits / 8
	data.setUint32(offset, channelCount * sampleRate * (sampleBits / 8), littleEdian)
	offset += 4
	// block data adjustment number, bytes per sample, channels × sample bits / 8
	data.setUint16(offset, channelCount * (sampleBits / 8), littleEdian)
	offset += 2
	// sample bit depth
	data.setUint16(offset, sampleBits, littleEdian)
	offset += 2
	// data identifier
	writeString(data, offset, "data")
	offset += 4
	// total sample data, i.e., total data size - 44
	data.setUint32(offset, bytes.byteLength, littleEdian)
	offset += 4

	// add PCM body to WAV header
	for (let i = 0; i < bytes.byteLength; ) {
		data.setUint8(offset, bytes.getUint8(i))
		offset += 1
		i += 1
	}

	return data
}
