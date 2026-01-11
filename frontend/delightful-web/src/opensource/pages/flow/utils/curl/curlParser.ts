/* eslint-disable no-console */
/* eslint-disable no-lonely-if */
/* eslint-disable no-restricted-syntax */
/* eslint-disable prefer-destructuring */
/**
 * curl parsing related functions
 */

interface ParsedCurl {
	method: string
	url: string
	domain: string
	path: string
	headers: Record<string, string>
	queryParams: Record<string, string>
	pathParams: string[]
	body: any
	bodyType: string
}

export function parseCurlCommand(curlCommand: string): ParsedCurl {
	// Preprocess curl command: remove trailing backslashes and merge lines
	curlCommand = curlCommand.replace(/\\\s*\n/g, " ")

	// Default values
	const result: ParsedCurl = {
		method: "GET",
		url: "",
		domain: "",
		path: "",
		headers: {},
		queryParams: {},
		pathParams: [],
		body: {},
		bodyType: "none",
	}

	// Extract URL - improved regex to handle URLs with and without quotes
	const urlMatch = curlCommand.match(
		/curl\s+(?:--location|-L)?\s*(?:--request|-X)?\s*[A-Z]*\s*['"]([^'"]+)['"]|curl\s+(?:--location|-L)?\s*(?:--request|-X)?\s*[A-Z]*\s*([^\s'"]+)/,
	)
	if (urlMatch) {
		result.url = urlMatch[1] || urlMatch[2] || ""
		try {
			// Process URL
			const urlObj = new URL(result.url)
			result.domain = urlObj.origin
			result.path = urlObj.pathname

			// Extract query parameters
			const searchParams = urlObj.searchParams
			searchParams.forEach((value, key) => {
				result.queryParams[key] = value
			})
		} catch (e) {
			console.error("Invalid URL in curl command", e)
			// Try to manually parse URL
			const urlParts = result.url.split("/")
			if (urlParts.length >= 3) {
				// Extract domain part
				const protocolAndDomain = urlParts.slice(0, 3).join("/")
				result.domain = protocolAndDomain
				// Extract path part
				result.path = `/${urlParts.slice(3).join("/")}`
			}
		}
	}

	// Extract request method - supports --request/-X format, handles quoted method names and various spacing
	const methodMatch = curlCommand.match(/(?:--request|-X)\s+['"]?\s*([A-Z]+)\s*['"]?/i)
	if (methodMatch && methodMatch[1]) {
		result.method = methodMatch[1].toUpperCase()
	}

	// Check if request body data exists (--data-raw, --data or -d), if exists and no explicit method specified, default to POST
	const hasDataParam = /(?:--data-raw|-d|--data)\s+['"]/.test(curlCommand)
	if (hasDataParam && result.method === "GET") {
		result.method = "POST"
	}

	// Extract request headers - compatible with both -H and --header formats
	const headerRegex = /(?:--header|-H)\s+['"]([^:;]+)(?::|\s*;\s*)([^'"]*)['"]/g
	const headerMatches = Array.from(curlCommand.matchAll(headerRegex))
	for (const match of headerMatches) {
		if (match[1]) {
			const headerName = match[1].trim()
			const headerValue = match[2] ? match[2].trim() : ""
			// Only add valid headers (with names)
			if (headerName) {
				result.headers[headerName] = headerValue
			}
		}
	}

	// Extract request body - supports multiple formats, compatible with short and long formats
	const dataRawMatch = curlCommand.match(/--data-raw\s+['"]((.|[\r\n])*?)['"](?:\s|$)/s)
	const dataMatch = curlCommand.match(/(?:--data|-d)\s+['"]((.|[\r\n])*?)['"](?:\s|$)/s)
	const formMatch = curlCommand.match(/(?:--data-urlencode|-d)\s+['"]((.|[\r\n])*?)['"](?:\s|$)/s)

	let bodyContent = ""
	if (dataRawMatch) bodyContent = dataRawMatch[1]
	else if (dataMatch) bodyContent = dataMatch[1]
	else if (formMatch) bodyContent = formMatch[1]

	console.log("Extracted body content:", bodyContent)

	if (bodyContent) {
		// Check content type
		const contentTypeHeader = Object.entries(result.headers).find(
			([key]) => key.toLowerCase() === "content-type",
		)

		if (contentTypeHeader) {
			const contentType = contentTypeHeader[1].toLowerCase()
			if (contentType.includes("application/json")) {
				result.bodyType = "json"
				try {
					// Try to parse JSON directly
					result.body = JSON.parse(bodyContent)
				} catch (e) {
					console.error("Failed to parse JSON body, trying to clean it first", e)
					try {
						// Clean JSON string: remove escape characters and extra newlines
						const cleanedBody = bodyContent
							.replace(/\\n/g, " ")
							.replace(/\\"/g, '"')
							.replace(/\\\\/g, "\\")
							.replace(/\n/g, " ")
							.trim()

						console.log("Cleaned body:", cleanedBody)
						result.body = JSON.parse(cleanedBody)
					} catch (e2) {
						console.error("Failed to parse cleaned JSON body", e2)

						// Final attempt: extract JSON part
						try {
							const jsonRegex = /{[\s\S]*}/s
							const jsonMatch = bodyContent.match(jsonRegex)
							if (jsonMatch) {
								console.log("Extracted JSON part:", jsonMatch[0])
								result.body = JSON.parse(jsonMatch[0])
							} else {
								result.body = bodyContent
							}
						} catch (e3) {
							console.error("All JSON parsing attempts failed", e3)
							result.body = bodyContent
						}
					}
				}
			} else if (contentType.includes("application/x-www-form-urlencoded")) {
				result.bodyType = "x-www-form-urlencoded"
				// Parse form data
				const formData: Record<string, string> = {}
				bodyContent.split("&").forEach((pair) => {
					const [key, value] = pair.split("=")
					if (key) formData[key] = value || ""
				})
				result.body = formData
			} else if (contentType.includes("multipart/form-data")) {
				result.bodyType = "form-data"
				// Parse multipart form data
				const formData: Record<string, string> = {}
				bodyContent.split("&").forEach((pair) => {
					const [key, value] = pair.split("=")
					if (key) formData[key] = value || ""
				})
				result.body = formData
			}
		} else {
			// No Content-Type header, try to infer type
			if (bodyContent.trim().startsWith("{") && bodyContent.trim().endsWith("}")) {
				result.bodyType = "json"
				try {
					// Try to parse JSON directly
					result.body = JSON.parse(bodyContent)
				} catch (e) {
					console.error(
						"Failed to parse JSON body without content-type, trying to clean it",
						e,
					)
					try {
						// Clean JSON string
						const cleanedBody = bodyContent
							.replace(/\\n/g, " ")
							.replace(/\\"/g, '"')
							.replace(/\\\\/g, "\\")
							.replace(/\n/g, " ")
							.trim()

						console.log("Cleaned body without content-type:", cleanedBody)
						result.body = JSON.parse(cleanedBody)
					} catch (e2) {
						console.error("Failed to parse cleaned JSON body without content-type", e2)

						// Final attempt: extract JSON part
						try {
							const jsonRegex = /{[\s\S]*}/s
							const jsonMatch = bodyContent.match(jsonRegex)
							if (jsonMatch) {
								console.log(
									"Extracted JSON part without content-type:",
									jsonMatch[0],
								)
								result.body = JSON.parse(jsonMatch[0])
							} else {
								// If it's form format, parse as form
								if (bodyContent.includes("&") && bodyContent.includes("=")) {
									result.bodyType = "x-www-form-urlencoded"
									const formData: Record<string, string> = {}
									bodyContent.split("&").forEach((pair) => {
										const [key, value] = pair.split("=")
										if (key) formData[key] = value || ""
									})
									result.body = formData
								} else {
									result.body = bodyContent
								}
							}
						} catch (e3) {
							console.error(
								"All JSON parsing attempts failed without content-type",
								e3,
							)
							result.body = bodyContent
						}
					}
				}
			} else if (bodyContent.includes("&") && bodyContent.includes("=")) {
				result.bodyType = "x-www-form-urlencoded"
				const formData: Record<string, string> = {}
				bodyContent.split("&").forEach((pair) => {
					const [key, value] = pair.split("=")
					if (key) formData[key] = value || ""
				})
				result.body = formData
			} else {
				// Default to plain text
				result.bodyType = "json"
				result.body = bodyContent
			}
		}
	}

	// If has request body but no type set, default to JSON
	if (Object.keys(result.body).length > 0 && result.bodyType === "none") {
		result.bodyType = "json"
	}

	console.log("Parsed curl result:", JSON.stringify(result, null, 2))
	return result
}





