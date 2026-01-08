// @ts-nocheck
/**
 * Flow Conversion Service
 * Provides conversion functionality between Flow YAML and JSON formats
 */

import { json2yaml, json2yamlString } from "./flow2yaml"
import { yamlString2json, yamlString2jsonString } from "./yaml2json"
import * as yaml from "js-yaml"

/**
 * Flow Converter Class
 */
export class FlowConverter {
	/**
	 * Convert YAML string to Flow JSON object
	 * @param yamlString YAML string
	 * @returns Flow JSON object
	 */
	static yamlToJson(yamlString: string) {
		return yamlString2json(yamlString)
	}

	/**
	 * Convert YAML string to Flow JSON string
	 * @param yamlString YAML string
	 * @returns Flow JSON string
	 */
	static yamlToJsonString(yamlString: string) {
		return yamlString2jsonString(yamlString)
	}

	/**
	 * Convert Flow JSON object to YAML object
	 * @param json Flow JSON object
	 * @returns YAML object
	 */
	static jsonToYaml(json: any) {
		return json2yaml(json)
	}

	/**
	 * Convert Flow JSON object to YAML string
	 * @param json Flow JSON object
	 * @returns YAML string
	 */
	static jsonToYamlString(json: any) {
		try {
			// Handle empty objects or objects missing required fields
			if (!json || Object.keys(json).length === 0) {
				// Return basic empty structure
				return yaml.dump({
					flow: {
						id: "",
						name: "",
						description: "",
						version: "1.0.0",
						type: "default",
					},
					variables: [],
					nodes: [],
					edges: [],
				})
			}

			// Handle objects missing required fields
			if (!json.nodes || !json.edges) {
				const defaultJson = {
					flow: {
						id: json.id || "",
						name: json.name || "",
						description: json.description || "",
						version: json.version_code || "1.0.0",
						type: json.type
							? typeof json.type === "number"
								? json.type === 1
									? "workflow"
									: json.type === 2
									? "knowledge"
									: "default"
								: json.type
							: "default",
						icon: json.icon || "",
						enabled: json.enabled !== undefined ? json.enabled : true,
					},
					variables: json.global_variable
						? Array.isArray(json.global_variable)
							? json.global_variable
							: []
						: [],
					nodes: json.nodes || [],
					edges: json.edges || [],
				}
				return yaml.dump(defaultJson, { lineWidth: -1, noRefs: true })
			}

			return json2yamlString(json)
		} catch (error) {
			console.error("Failed to convert JSON to YAML string:", error)
			// Return basic empty structure instead of throwing error
			return yaml.dump({
				flow: {
					id: "",
					name: "",
					description: "",
					version: "1.0.0",
					type: "default",
				},
				variables: [],
				nodes: [],
				edges: [],
			})
		}
	}

	/**
	 * Convert Flow JSON string to YAML string
	 * @param jsonString Flow JSON string
	 * @returns YAML string
	 */
	static jsonStringToYamlString(jsonString: string) {
		try {
			const json = JSON.parse(jsonString)
			return this.jsonToYamlString(json)
		} catch (error) {
			console.error("Error converting JSON string to YAML string:", error)
			// Return basic empty structure instead of throwing error
			return yaml.dump({
				flow: {
					id: "",
					name: "",
					description: "",
					version: "1.0.0",
					type: "default",
				},
				variables: [],
				nodes: [],
				edges: [],
			})
		}
	}

	/**
	 * Convert Flow JSON string to Flow JSON object
	 * @param jsonString Flow JSON string
	 * @returns Flow JSON object
	 */
	static jsonStringToJson(jsonString: string) {
		return JSON.parse(jsonString)
	}
}

export default FlowConverter
