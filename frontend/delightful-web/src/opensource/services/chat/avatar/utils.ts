// A simple CRC32 lookup table (complete CRC32 implementation would be more complex)
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

	// Generate 5 similar color values (can adjust quantity as needed)
	for (let i = 0; i < 5; i += 1) {
		const newR = Math.max(0, Math.min(255, r + (i - 5) * 10))
		const newG = Math.max(0, Math.min(255, g + (i - 1) * 10))
		const newB = Math.max(0, Math.min(255, b + (i - 2) * 10))
		colors.push(`rgb(${newR},${newG},${newB})`)
	}

	return colors
}

export function textToBackgroundColor(name?: string) {
	if (!name) return "#ffffff"

	let hash = 0
	for (let i = 0; i < name.length; i += 1) {
		hash += name.charCodeAt(i)
	}
	const hue = (hash % 360) + 10 // Ensure hue stays between 10 and 360
	const saturation = 60 + (hash % 20) // Ensure saturation between 60 and 80
	const lightness = 40 + (hash % 20) // Ensure lightness between 40 and 60

	// Ensure generated colors are soft and comfortable
	return `hsl(${hue}, ${saturation}%, ${lightness}%)`
}

export function textToDisplayName(name?: string) {
	if (!name) return ""
	// Determine if it's a Chinese or English name
	const isChinese = name.match(/[\u4e00-\u9fa5]/)
	if (isChinese) {
		// Extract last two valid Chinese characters, excluding punctuation
		return name.replace(/[^\u4e00-\u9fa5]/g, "").slice(-2)
	}
	// Extract first uppercase letter from English name
	return name[0]?.toUpperCase() ?? ""
}

export const isValidUrl = (url: string) => {
	return /^https?:\/\//.test(url)
}
