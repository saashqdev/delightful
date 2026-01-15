// src/utils/curlToApiStructure.ts
import { generateSnowFlake } from "../helpers"
import { parseCurlCommand } from "./curlParser"

// Generate unique ID
export function generateComponentId() {
	return `component-${generateSnowFlake().replace(/-/g, "").substring(0, 12)}`
}

// Convert regular value to InputExpressionValue format
function convertToExpressionValue(value: any) {
	const valueType = typeof value
	let stringValue = String(value)

	// If it's an object, convert to JSON string
	if (valueType === "object" && value !== null) {
		try {
			stringValue = JSON.stringify(value)
		} catch (e) {
			stringValue = String(value)
		}
	}

	return {
		type: "const",
		const_value: [
			{
				type: "input",
				uniqueId: generateComponentId(),
				value: stringValue,
			},
		],
		expression_value: [],
	}
}

// Convert object to form structure in API structure
export function objectToFormStructure(obj: Record<string, any> = {}) {
	const properties: Record<string, any> = {}

	Object.entries(obj).forEach(([key, value]) => {
		const isObject = typeof value === "object" && value !== null && !Array.isArray(value)

		properties[key] = {
			type: isObject ? "object" : typeof value === "number" ? "number" : "string",
			key,
			sort: 0,
			title: null,
			description: null,
			required: [],
			// Convert value to InputExpressionValue format
			value: isObject ? null : convertToExpressionValue(value),
			encryption: false,
			encryption_value: null,
			items: null,
			properties: isObject ? objectToFormStructure(value).properties : null,
		}
	})

	return {
		type: "object",
		key: "root",
		sort: 0,
		title: null,
		description: null,
		required: [],
		value: null,
		encryption: false,
		encryption_value: null,
		items: null,
		properties: Object.keys(properties).length > 0 ? properties : null,
	}
}

export function curlToApiStructure(curlCommand: string) {
	const parsedCurl = parseCurlCommand(curlCommand)

	// Create API structure
	const apiStructure = {
		id: generateComponentId(),
		version: "1",
		type: "api",
		structure: {
			method: parsedCurl.method,
			domain: parsedCurl.domain,
			path: parsedCurl.path,
			uri: null,
			url: parsedCurl.url,
			proxy: "",
			auth: "",
			request: {
				params_query: {
					id: generateComponentId(),
					version: "1",
					type: "form",
					structure:
						Object.keys(parsedCurl.queryParams).length > 0
							? objectToFormStructure(parsedCurl.queryParams)
							: {
									type: "object",
									key: "root",
									sort: 0,
									title: null,
									description: null,
									required: [],
									value: null,
									encryption: false,
									encryption_value: null,
									items: null,
									properties: null,
							  },
				},
				params_path: {
					id: generateComponentId(),
					version: "1",
					type: "form",
					structure: {
						type: "object",
						key: "root",
						sort: 0,
						title: null,
						description: null,
						required: [],
						value: null,
						encryption: false,
						encryption_value: null,
						items: null,
						properties:
							parsedCurl.pathParams.length > 0
								? parsedCurl.pathParams.reduce((acc, param) => {
										acc[param] = {
											type: "string",
											key: param,
											sort: 0,
											title: null,
											description: null,
											required: [],
											value: "",
											encryption: false,
											encryption_value: null,
											items: null,
											properties: null,
										}
										return acc
								  }, {} as Record<string, any>)
								: null,
					},
				},
				body_type: parsedCurl.bodyType,
				body: {
					id: generateComponentId(),
					version: "1",
					type: "form",
					structure:
						parsedCurl.bodyType !== "none"
							? objectToFormStructure(parsedCurl.body)
							: {
									type: "object",
									key: "root",
									sort: 0,
									title: null,
									description: null,
									required: [],
									value: null,
									encryption: false,
									encryption_value: null,
									items: null,
									properties: null,
							  },
				},
				headers: {
					id: generateComponentId(),
					version: "1",
					type: "form",
					structure:
						Object.keys(parsedCurl.headers).length > 0
							? objectToFormStructure(parsedCurl.headers)
							: {
									type: "object",
									key: "root",
									sort: 0,
									title: null,
									description: null,
									required: [],
									value: null,
									encryption: false,
									encryption_value: null,
									items: null,
									properties: null,
							  },
				},
			},
		},
	}

	return apiStructure
}
