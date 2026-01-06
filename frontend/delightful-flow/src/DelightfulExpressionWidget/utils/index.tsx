import { EXPRESSION_ITEM, EXPRESSION_VALUE, LabelTypeMap } from "../types"
import { COLOR_DATA, COLOR_DATA_TEXT_COLOR } from "./colors"

/**
 * Get the type of a single field
 */
export const getItemType = (typeString = "") => {
	const types = Object.values(LabelTypeMap)
	return types.find((type) => typeString.includes(type))
}

/**
 * Filter out empty values
 */
export const filterNullValue = (resultValue = [] as EXPRESSION_ITEM[]) => {
	return resultValue
		.map((item) => {
			// @ts-ignore
			if (item && item.type.includes(LabelTypeMap.LabelText))
				// @ts-ignore
				item.value = item.value.replaceAll(/\u200B/g, "")
			return item
		})
		.filter((item) => item)
		.filter(
			(item) =>
				// @ts-ignore
				![LabelTypeMap.LabelText].includes(getItemType(item.type) as LabelTypeMap) ||
				item.value,
		)
}

// Replace < and > with entities to avoid HTML parsing
export const transferSpecialSign = (str: string) => {
	return str.replace(/</g, "&lt;").replace(/>/g, "&gt;")
}

export default function hash(str = "", length = 0) {
	if (!str) str = ""
	const H = 37
	let total = 0
	for (let i = 0; i < str.length; i += 1) {
		total += H * total + str.charCodeAt(i)
	}
	total %= length
	if (total < 0) {
		total += length
	}

	return total
}

export const getColor = (name: string) => {
	let i = hash(name, COLOR_DATA.length)
	return {
		backgroundColor: COLOR_DATA[i],
		textColor: COLOR_DATA_TEXT_COLOR[COLOR_DATA[i]],
	}
}

/** Filter out empty values */
export const filterEmptyValues = (values: EXPRESSION_VALUE[]) => {
	return values?.filter?.((v) => {
		const normalType = [LabelTypeMap.LabelNode, LabelTypeMap.LabelText, LabelTypeMap.LabelFunc]
		const isNormalType = normalType.includes(v?.type)
		if (isNormalType) {
			return v?.value && v?.value !== "\n"
		}
		return v
	})
}

// Convert CSS size values to a plain number
export const parseSizeToNumber = (size: string | number): number => {
	if (typeof size === "number") return size

	// Strip px suffix and parse if it is a pixel value
	if (size.endsWith("px")) {
		return parseInt(size, 10)
	}

	// Convert viewport height (vh) to pixels
	if (size.endsWith("vh")) {
		const vh = parseInt(size, 10)
		// Use window.innerHeight to get viewport height
		const viewportHeight = typeof window !== "undefined" ? window.innerHeight : 600
		return Math.floor((vh / 100) * viewportHeight)
	}

	// If no unit or other units, try parsing directly
	const parsed = parseInt(size.toString(), 10)
	return isNaN(parsed) ? 600 : parsed // Default to 600px
}
