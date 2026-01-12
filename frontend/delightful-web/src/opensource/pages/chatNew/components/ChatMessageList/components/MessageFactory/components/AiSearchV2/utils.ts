import type { AssociateQuestion } from "@/types/chat/conversation_message"
import duration from "dayjs/plugin/duration"
import dayjs from "dayjs"
import { t } from "i18next"
import { TimeLineDotStatus } from "./const"

export function getTimelineItemStatus(
	item: AssociateQuestion,
	index: number,
	array: AssociateQuestion[],
) {
	let status = TimeLineDotStatus.WAITING

	if (item.llm_response) {
		status = TimeLineDotStatus.SUCCESS
	} else if (index === 0) {
		status = TimeLineDotStatus.PENDING
	} else {
		const prev = array[index - 1]
		if (prev.llm_response) {
			status = TimeLineDotStatus.PENDING
		} else {
			status = TimeLineDotStatus.WAITING
		}
	}
	return status
}

dayjs.extend(duration)

/**
 * Format minutes
 */
export function formatMinutes(minutes: number) {
	const dur = dayjs.duration(minutes, "minutes")

	if (dur.asDays() >= 1) {
		return `${dur.asDays().toFixed(1)}${t("common.day", { ns: "interface" })}`
	}

	if (dur.asHours() >= 1) {
		return `${dur.asHours().toFixed(1)}${t("common.hour", { ns: "interface" })}`
	}

	return `${Math.floor(dur.asMinutes())}${t("common.minute", { ns: "interface" })}`
}

/**
 * Extract placeholders from source text
 * @param source Source text
 * @param regex Regular expression
 * @returns Placeholder array
 */
export function extractSourcePlaceholders(source: string, regex: RegExp): string[] {
	if (!source) return []

	const matches = Array.from(source.matchAll(regex))
	if (matches.length === 0) return [source]

	const result: string[] = []
	let lastIndex = 0

	matches.forEach((match) => {
		// If text exists before match, add that text
		if (match.index! > lastIndex) {
			result.push(source.slice(lastIndex, match.index))
		}
		// Add match item
		result.push(match[0])
		lastIndex = match.index! + match[0].length
	})

	// If remaining text at end, add to result
	if (lastIndex < source.length) {
		result.push(source.slice(lastIndex))
	}

	return result
}
