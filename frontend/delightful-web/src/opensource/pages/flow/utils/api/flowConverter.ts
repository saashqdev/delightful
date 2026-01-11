//@ts-nocheck
/**
 * Flow Converter API
 * Provides API for converting between YAML and JSON
 */

import { message } from "antd"
import { FlowConverter } from "../flowConverter"

/**
 * Convert YAML to JSON
 * @param yamlString YAML string
 * @returns JSON object or null
 */
export const convertYAMLToJSON = (yamlString: string) => {
	try {
		return FlowConverter.yamlToJson(yamlString)
	} catch (error) {
		message.error(`YAML to JSON conversion failed: ${error.message}`)
		console.error("YAML to JSON conversion failed:", error)
		return null
	}
}

/**
 * Convert JSON to YAML
 * @param jsonObj JSON object
 * @returns YAML string or null
 */
export const convertJSONToYAML = (jsonObj: any) => {
	try {
		return FlowConverter.jsonToYamlString(jsonObj)
	} catch (error) {
		message.error(`JSON to YAML conversion failed: ${error.message}`)
		console.error("JSON to YAML conversion failed:", error)
		return null
	}
}

/**
 * Convert JSON string to YAML
 * @param jsonString JSON string
 * @returns YAML string or null
 */
export const convertJSONStringToYAML = (jsonString: string) => {
	try {
		return FlowConverter.jsonStringToYamlString(jsonString)
	} catch (error) {
		message.error(`JSON string to YAML conversion failed: ${error.message}`)
		console.error("JSON string to YAML conversion failed:", error)
		return null
	}
}

/**
 * Convert YAML to JSON string
 * @param yamlString YAML string
 * @returns JSON string or null
 */
export const convertYAMLToJSONString = (yamlString: string) => {
	try {
		return FlowConverter.yamlToJsonString(yamlString)
	} catch (error) {
		message.error(`YAML to JSON string conversion failed: ${error.message}`)
		console.error("YAML to JSON string conversion failed:", error)
		return null
	}
}

export default {
	convertYAMLToJSON,
	convertJSONToYAML,
	convertJSONStringToYAML,
	convertYAMLToJSONString,
}





