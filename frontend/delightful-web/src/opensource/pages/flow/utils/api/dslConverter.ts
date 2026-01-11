// @ts-nocheck
/**
 * DSL Converter API
 * Provides API for converting between DSL and JSON
 */

import { DSLConverter } from "../dsl"

/**
 * Convert DSL to JSON
 * @param dslString DSL string (YAML format)
 * @returns JSON object or null
 */
export const convertDSLToJSON = (dslString: string) => {
	try {
		return DSLConverter.dslToJson(dslString)
	} catch (error: any) {
		console.error("Failed to convert DSL to JSON:", error.message)
		return null
	}
}

/**
 * Convert JSON to DSL
 * @param jsonObj JSON object
 * @returns DSL string (YAML format) or null
 */
export const convertJSONToDSL = (jsonObj: any) => {
	try {
		return DSLConverter.jsonToDslString(jsonObj)
	} catch (error: any) {
		console.error("Failed to convert JSON to DSL:", error.message)
		return null
	}
}

/**
 * Convert JSON string to DSL
 * @param jsonString JSON string
 * @returns DSL string (YAML format) or null
 */
export const convertJSONStringToDSL = (jsonString: string) => {
	try {
		return DSLConverter.jsonStringToDslString(jsonString)
	} catch (error: any) {
		console.error("Failed to convert JSON string to DSL:", error.message)
		return null
	}
}

/**
 * Convert DSL to JSON string
 * @param dslString DSL string (YAML format)
 * @returns JSON string or null
 */
export const convertDSLToJSONString = (dslString: string) => {
	try {
		return DSLConverter.dslToJsonString(dslString)
	} catch (error: any) {
		console.error("Failed to convert DSL to JSON string:", error.message)
		return null
	}
}

export default {
	convertDSLToJSON,
	convertJSONToDSL,
	convertJSONStringToDSL,
	convertDSLToJSONString,
}





