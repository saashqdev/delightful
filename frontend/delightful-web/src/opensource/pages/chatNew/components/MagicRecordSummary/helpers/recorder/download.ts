/* eslint-disable no-underscore-dangle */
/* eslint-disable @typescript-eslint/naming-convention */
/**
 * Download audio recording file
 * @private
 * @param {*} blob      blob data
 * @param {string} name filename for download
 * @param {string} type file extension for download
 */
function _download(blob: Blob | MediaSource, name: string, type: string): void {
	const oA = document.createElement("a")

	oA.href = window.URL.createObjectURL(blob)
	oA.download = `${name}.${type}`
	oA.click()
}

/**
 * Download WAV data of the recording
 *
 * @param {blob}   blob data type to download
 * @param {string} [name='recorder']    renamed filename
 */
export function downloadWAV(wavblob: any, name: string = "recorder"): void {
	_download(wavblob, name, "wav")
}

/**
 * Download PCM data of the recording
 *
 * @param {blob}   blob data type to download
 * @param {string} [name='recorder']    renamed filename
 * @memberof Recorder
 */
export function downloadPCM(pcmBlob: any, name: string = "recorder"): void {
	_download(pcmBlob, name, "pcm")
}

// Generic download interface
export function download(blob: any, name: string, type: string) {
	return _download(blob, name, type)
}
