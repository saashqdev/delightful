// A simple CRC32 lookup table (a complete CRC32 implementation would be more complex)
const table: number[] = []
for (let i = 0; i < 256; i += 1) {
	let c = i
	for (let j = 0; j < 8; j += 1) {
		c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1
	}
	table[i] = c
}

function crc32(str: string) {
	let crc = 0
	for (let i = 0; i < str.length; i += 1) {
		crc = ((crc >>> 8) ^ table[(crc ^ str.charCodeAt(i)) & 0xff]) & 0xffffffff
	}
	return crc
}

export function textToColors(text: string) {
	const hash = crc32(text)

	const r = (hash >> 16) & 0xff

	const g = (hash >> 8) & 0xff

	const b = hash & 0xff

	// Define an array to store generated similar color values
	const colors: string[] = []

	// Generate 5 similar color values (can be adjusted as needed)
	for (let i = 0; i < 5; i += 1) {
		const newR = Math.max(0, Math.min(255, r + (i - 5) * 10))
		const newG = Math.max(0, Math.min(255, g + (i - 1) * 10))
		const newB = Math.max(0, Math.min(255, b + (i - 2) * 10))
		colors.push(`rgb(${newR},${newG},${newB})`)
	}

	return colors
}

export function textToBackgroundColor(name?: string) {
	if (!name) return ""

	let hash = 0
	for (let i = 0; i < name.length; i += 1) {
		hash += name.charCodeAt(i)
	}
	const hue = (hash % 360) + 10 // Ensure hue is between 10 and 360
	const saturation = 60 + (hash % 20) // Ensure saturation is between 60 and 80
	const lightness = 40 + (hash % 20) // Ensure lightness is between 40 and 60

	// Ensure the generated color is soft and comfortable
	return `hsl(${hue}, ${saturation}%, ${lightness}%)`
}

export function textToTextColor(name?: string) {
	if (!name) return ""
	// Determine if it's a Chinese or English name
	const isChinese = name.match(/[\u4e00-\u9fa5]/)
	if (isChinese) {
		// Take the last two characters of Chinese name
		return name.slice(-2)
	}
	// Take the first uppercase letter of English name
	return name[0]?.toUpperCase() ?? ""
}

export const isValidUrl = (url: string) => {
	return /^https?:\/\//.test(url)
}
