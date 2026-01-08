import Recorder from "./recorder/recorder"
import { safeBinaryToBtoa } from "@/utils/encoding"

export const recorder = new Recorder({
	sampleBits: 16, // Sample bits, supports 8 or 16, default is 16
	sampleRate: 16000, // Sample rate, supports 11025, 16000, 22050, 24000, 44100, 48000, browser default, my Chrome is 48000
	numChannels: 1, // Number of channels, supports 1 or 2, default is 1
	// compiling: false,(works in 0.x version, adding in 1.x)  // Whether to convert while recording, default is false
})

export async function startRecording() {
	await recorder.start()
}

export function stopRecording() {
	recorder.stop()
}

// Pad with zero to ensure two digits
function pad(num: number): string {
	return num < 10 ? `0${num}` : `${num}`
}

export function formatTime(seconds: number): string {
	const hours = Math.floor(seconds / 3600) // Calculate hours
	const minutes = Math.floor((seconds % 3600) / 60) // Calculate minutes
	const remainingSeconds = Math.floor(seconds % 60) // Remaining seconds

	// Format as HH:MM:SS, ensuring each part is two digits
	return `${pad(hours)}:${pad(minutes)}:${pad(remainingSeconds)}`
}

/**
 * Convert Blob or Uint8Array to Base64 string
 * @param input - Blob or Uint8Array object to be converted
 * @returns Promise<string> - Base64 string
 */
export async function blobToBase64Async(input: Blob | Uint8Array): Promise<string> {
	if (input instanceof Blob) {
		return new Promise((resolve, reject) => {
			const reader = new FileReader()

			reader.onloadend = () => {
				if (reader.result && typeof reader.result === "string") {
					// Extract Base64 part from DataURL
					resolve(reader.result.split(",")[1])
				} else {
					reject(new Error("Failed to convert Blob to Base64"))
				}
			}

			reader.onerror = () => reject(new Error("Error reading Blob as Base64"))

			reader.readAsDataURL(input)
		})
	}
	if (input instanceof Uint8Array) {
		// Convert Uint8Array to Base64
		return new Promise((resolve) => {
			const base64Result = safeBinaryToBtoa(input)
			resolve(base64Result)
		})
	}
	throw new Error("Unsupported input type. Only Blob or Uint8Array is allowed.")
}
