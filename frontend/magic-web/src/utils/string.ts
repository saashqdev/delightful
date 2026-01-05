import dayjs from "dayjs"
import { t } from "i18next"
import { isNumber } from "lodash-es"
import RelativeTime from "dayjs/plugin/relativeTime"
import { configStore } from "@/opensource/models/config"

import "dayjs/locale/zh-cn"
import "dayjs/locale/en"
import { getCurrentLang } from "./locale"

dayjs.extend(RelativeTime)

// Each segment holds at most 15 digits; if there are two segments, the first starts at 0 and the second at str.length
const getMidNum = (str: string, start: number, len: number) => {
	if (start + len > 0) {
		return +str.substr(start < 0 ? 0 : start, start < 0 ? start + len : len)
	}
	return 0
}

/**
 * Compare two large integers; return -1, 0, 1. Returns -1 when a < b
 * @param {String} a
 * @param {String} b
 * @returns {number}
 */
export const bigNumCompare = (a: string, b: string): number => {
	let back = 0
	// Split the larger number into 15-digit chunks (ceil)
	const max = Math.ceil(Math.max(a.length, b.length) / 15)
	// Iterate from the leftmost chunk
	for (let i = max; i > 0; i -= 1) {
		const num1 = getMidNum(a, a.length - i * 15, 15)
		const num2 = getMidNum(b, b.length - i * 15, 15)
		// Subtract 15-digit chunks
		const cur = num1 - num2
		if (cur < 0) {
			back = -1
			break
		} else if (cur > 0) {
			back = 1
			break
		}
	}
	return back
}

/**
 * Default time formatting
 * @param time Time input
 * @returns Time string
 */
export const defaultTimeFormat = (() => {
	const currentDay = dayjs().format("YYYY-MM-DD")
	const currentYear = dayjs().format("YYYY")

	return (time: number | Date | dayjs.Dayjs | null | undefined) => {
		if (!time) {
			return ""
		}
		if (isNumber(time)) {
			time *= 1000
		}

		const day = dayjs(time)
		const year = day.format("YYYY")
		const d = day.format("YYYY-MM-DD")

		if (d === currentDay) {
			return day.format(t("format.time", { ns: "common" }))
		}
		if (year === currentYear) {
			return day.format(t("format.date", { ns: "common" }))
		}

		return day.format(t("format.dateWithYear", { ns: "common" }))
	}
})()

/**
 * Format time
 * @param time Time input
 * @param format Format string
 * @returns Formatted time string
 */
export const formatTime = (
	time: number | Date | dayjs.Dayjs | null | undefined | string,
	format?: string,
): string => {
	if (typeof time === "string") {
		time = dayjs(time)
	}
	if (!format) {
		return defaultTimeFormat(time)
	}
	if (!time) {
		return ""
	}
	if (isNumber(time)) {
		time *= 1000
	}
	const day = dayjs(time)
	return day.format(format)
}

/**
 * Format relative time
 * @param time Time input
 * @returns Relative time string
 */
export const formatRelativeTime = (lang?: string) => {
	const currentDay = dayjs().format("YYYY-MM-DD")

	return (time: number | Date | dayjs.Dayjs | null | undefined) => {
		if (!time) return ""

		if (isNumber(time)) {
			time *= 1000
		} else if (typeof time === "string") {
			time = dayjs(time)
		}

		const current = dayjs()
		const day = dayjs(time)
		const d = day.format("YYYY-MM-DD")

		// Same day
		if (d === currentDay) {
			return day.format(t("format.time", { ns: "common" }))
		}

		// Less than 24 hours apart
		if (current.diff(day, "hour") < 24) {
			return day.format(t("format.yesterday", { ns: "common" }))
		}

		dayjs.locale(
			(lang ?? getCurrentLang(configStore.i18n.language))
				.toLocaleLowerCase()
				.replace("_", "-"),
		)

		return day.fromNow()
	}
}

/**
 * Mask phone number
 * 1. Validate length; if invalid, do not mask
 * 2. Validate phone format; if invalid, do not mask
 * 3. Support country code
 * 4. Custom mask symbol
 * 5. Show only first 3 and last 4 digits
 *
 * @param phone Phone number
 * @param symbol Masking symbol
 * @returns Masked phone number
 */
export function encryptPhone(phone: string, symbol: string = "*"): string {
	if (!phone) {
		return phone
	}
	const phoneRegex = /^(\+\d{1,3})?(\d{11})$/
	const match = phone.match(phoneRegex)

	if (!match) {
		return phone // Invalid phone number; return as-is
	}

	const countryCode = match[1] || ""
	const localNumber = match[2]

	return `${countryCode}${localNumber.slice(0, 3)}${symbol.repeat(4)}${localNumber.slice(-4)}`
}

/**
 * Validate phone number
 *
 * @param phone Phone number
 * @returns Whether valid
 */
export function validatePhone(phone: string): boolean {
	const phoneRegex = /^(\+\d{1,3})?(\d{11})$/
	return phoneRegex.test(phone)
}

/**
 * Validate JSON string
 * @param data Data string
 * @returns Parsed object or false
 */
export function isValidJson(data: string): false | object {
	try {
		return JSON.parse(data)
	} catch (error) {
		return false
	}
}
/**
 * Format file size
 * @param size
 * @returns
 */

export function formatFileSize(size?: number) {
	if (!size) return t("common.unknown", { ns: "interface" })

	const kb = size / 1024
	const mb = kb / 1024
	const gb = mb / 1024

	if (gb >= 1) {
		return `${gb.toFixed(2)} GB`
	}
	if (mb >= 1) {
		return `${mb.toFixed(2)} MB`
	}
	if (kb >= 1) {
		return `${kb.toFixed(2)} KB`
	}

	return `${size} B`
}

/**
 * Determine whether the text is Markdown
 * @param text
 * @returns
 */
export function isMarkdown(text: string): boolean {
	// code-ish
	const fences = text.match(/^```/gm)
	if (fences && fences.length > 1) return true

	// link-ish
	if (text.match(/\[[^]+\]\(https?:\/\/\S+\)/gm)) return true
	if (text.match(/\[[^]+\]\(\/\S+\)/gm)) return true

	// heading-ish
	if (text.match(/^#{1,6}\s+\S+/gm)) return true

	// list-ish
	const listItems = text.match(/^[\d-*].?\s\S+/gm)
	if (listItems && listItems.length > 1) return true

	return false
}

/**
 * Try parsing JSON
 * @param data
 * @param fallbackData
 * @returns
 */
export const jsonParse = <V>(data: string, fallbackData: V): V => {
	try {
		return JSON.parse(data) as V
	} catch (error) {
		return fallbackData
	}
}
