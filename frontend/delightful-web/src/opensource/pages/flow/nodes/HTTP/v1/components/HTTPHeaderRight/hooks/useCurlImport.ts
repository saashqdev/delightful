/* eslint-disable no-console */
import { useState, useCallback } from "react"
import { useFlow } from "@delightful/delightful-flow/dist/DelightfulFlow/context/FlowContext/useFlow"
import { message } from "antd"
import { useCurrentNode } from "@delightful/delightful-flow/dist/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { parseCurlCommand } from "@/opensource/pages/flow/utils/curl/curlParser"
import {
	generateComponentId,
	objectToFormStructure,
} from "@/opensource/pages/flow/utils/curl/curlToApiStructure"
import { useTranslation } from "react-i18next"

// Create default empty structure
function createEmptyStructure() {
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
		properties: null,
	}
}

export default function useCurlImport() {
	const { t } = useTranslation()
	const [visible, setVisible] = useState(false)
	const { updateNodeConfig } = useFlow()
	const { currentNode } = useCurrentNode()

	const onCancel = useCallback(() => {
		setVisible(false)
	}, [])

	const showImporter = useCallback(() => {
		setVisible(true)
	}, [])

	const onImport = useCallback(
		(curlCommand: string) => {
			if (!curlCommand.trim()) {
				message.error(t("http.curlImport.emptyError", { ns: "flow" }))
				return
			}

			try {
				const parsedCurl = parseCurlCommand(curlCommand)
				console.log("Parsed curl result:", parsedCurl)

// Get current node API configuration
				const currentApi = currentNode?.params?.api || {}

			// Create updated API structure - complete overwrite update
				const updatedApi = {
					...currentApi,
					structure: {
						...currentApi.structure,
						method: parsedCurl.method,
						domain: parsedCurl.domain,
						path: parsedCurl.path,
						url: parsedCurl.url,
						request: {
							...currentApi.structure?.request,
							// Query parameters - always use new parsing results
							params_query: {
								id:
									currentApi.structure?.request?.params_query?.id ||
									generateComponentId(),
								version: "1",
								type: "form",
								structure:
									Object.keys(parsedCurl.queryParams).length > 0
										? objectToFormStructure(parsedCurl.queryParams)
										: createEmptyStructure(),
							},
							// Path parameters - always use new parsing results
							params_path: {
								id:
									currentApi.structure?.request?.params_path?.id ||
									generateComponentId(),
								version: "1",
								type: "form",
								structure:
									parsedCurl.pathParams.length > 0
										? {
												...createEmptyStructure(),
												properties: parsedCurl.pathParams.reduce(
													(acc, param) => {
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
													},
													{} as Record<string, any>,
												),
										  }
										: createEmptyStructure(),
							},
							// Request body type - always use new parsing results
							body_type: parsedCurl.bodyType || "none",
							// Request body - always use new parsing results
							body: {
								id:
									currentApi.structure?.request?.body?.id ||
									generateComponentId(),
								version: "1",
								type: "form",
								structure:
									parsedCurl.bodyType !== "none" &&
									Object.keys(parsedCurl.body || {}).length > 0
										? objectToFormStructure(parsedCurl.body)
										: createEmptyStructure(),
							},
							// Request headers - always use new parsing results
							headers: {
								id:
									currentApi.structure?.request?.headers?.id ||
									generateComponentId(),
								version: "1",
								type: "form",
								structure:
									Object.keys(parsedCurl.headers).length > 0
										? objectToFormStructure(parsedCurl.headers)
										: createEmptyStructure(),
							},
						},
					},
				}

				// Update node configuration
				if (currentNode?.id) {
					updateNodeConfig({
						...currentNode,
						params: {
							...currentNode.params,
							api: updatedApi,
						},
					})

					message.success(t("http.curlImport.importSuccess", { ns: "flow" }))
					setVisible(false)
				} else {
					message.error(t("http.curlImport.nodeNotExist", { ns: "flow" }))
				}
			} catch (error) {
				console.error("Failed to parse curl command", error)
				message.error(t("http.curlImport.parseError", { ns: "flow" }))
			}
		},
		[currentNode, updateNodeConfig, t],
	)

	return {
		visible,
		showImporter,
		onCancel,
		onImport,
	}
}





