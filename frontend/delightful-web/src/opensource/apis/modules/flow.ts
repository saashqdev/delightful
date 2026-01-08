import { genRequestUrl } from "@/utils/http"
import { RequestUrl } from "../constant"
import type { DelightfulFlow } from "@delightful/delightful-flow/dist/DelightfulFlow/types/flow"
import type {
	ApiKey,
	ApiKeyRequestParams,
	FlowDraft,
	FlowTool,
	GetFlowListParams,
	LLMModalOption,
	NodeTestingResult,
	PlatformItem,
	SubFlowArgument,
	TestNodeParams,
	TestResult,
	TriggerConfig,
	UseableToolSet,
	WithPage,
	Flow,
} from "@/types/flow"
import { FlowType } from "@/types/flow"
import type { File, Sheet } from "@/types/sheet"
import type { Knowledge } from "@/types/knowledge"
import type { MethodOption } from "@delightful/delightful-flow/dist/DelightfulExpressionWidget/types"
import type { HttpClient } from "../core/HttpClient"

export const generateFlowApi = (fetch: HttpClient) => ({
	/**
	 * Query flow list
	 */
	getFlowList({ type = FlowType.Main, page = 1, pageSize = 100, name }: GetFlowListParams) {
		return fetch.post<WithPage<DelightfulFlow.Flow[]>>(genRequestUrl(RequestUrl.getFlowList), {
			type,
			page,
			page_size: pageSize,
			name,
		})
	},

	/**
	 * Query flow details
	 */
	getFlow(flowId: string) {
		return fetch.get<DelightfulFlow.Flow>(genRequestUrl(RequestUrl.getFlow, { flowId }))
	},

	/**
	 * Test run flow
	 */
	testFlow(flow: DelightfulFlow.Flow & { trigger_config: TriggerConfig }) {
		return fetch.post<TestResult>(
			genRequestUrl(RequestUrl.testFlow, { flowId: flow.id! }),
			flow,
		)
	},

	/**
	 * Add or update flow basic information
	 */
	addOrUpdateFlowBaseInfo(flow: Partial<DelightfulFlow.Flow> & { type?: FlowType }) {
		return fetch.post<DelightfulFlow.Flow>(genRequestUrl(RequestUrl.addOrUpdateFlowBaseInfo), flow)
	},

	/**
	 * Delete flow
	 */
	deleteFlow(flowId: string) {
		return fetch.delete<null>(genRequestUrl(RequestUrl.deleteFlow, { flowId }))
	},

	/**
	 * Save flow details
	 */
	saveFlow(flow: DelightfulFlow.Flow) {
		return fetch.post<null>(genRequestUrl(RequestUrl.saveFlow, { flowId: flow.id! }), flow)
	},

	/**
	 * Save flow as draft
	 */
	saveFlowDraft(draftDetail: FlowDraft.RequestArgs, flowId: string) {
		return fetch.post<FlowDraft.Detail>(
			genRequestUrl(RequestUrl.saveFlowDraft, { flowId }),
			draftDetail,
		)
	},

	/**
	 * Query flow draft list
	 */
	getFlowDraftList(flowId: string) {
		return fetch.post<WithPage<FlowDraft.ListItem[]>>(
			genRequestUrl(RequestUrl.getFlowDraftList, { flowId }),
		)
	},

	/**
	 * Query flow draft details
	 */
	getFlowDraftDetail(flowId: string, draftId: string) {
		return fetch.get<FlowDraft.Detail>(
			genRequestUrl(RequestUrl.getFlowDratDetail, { flowId, draftId }),
		)
	},

	/**
	 * Delete flow draft
	 */
	deleteFlowDraft(flowId: string, draftId: string) {
		return fetch.delete<FlowDraft.Detail>(
			genRequestUrl(RequestUrl.deleteFlowDraft, { flowId, draftId }),
		)
	},

	/**
	 * Query flow version list
	 */
	getFlowPublishList(flowId: string, page = 1, pageSize = 200) {
		return fetch.post<WithPage<FlowDraft.ListItem[]>>(
			genRequestUrl(RequestUrl.getFlowPublishList, { flowId, page, pageSize }),
		)
	},

	/**
	 * Query flow version details
	 */
	getFlowPublishDetail(flowId: string, versionId: string) {
		return fetch.get<FlowDraft.Detail>(
			genRequestUrl(RequestUrl.getFlowPublishDetail, { flowId, versionId }),
		)
	},

	/**
	 * Publish flow version
	 */
	publishFlow(publishDetail: FlowDraft.RequestArgs, flowId: string) {
		return fetch.post<FlowDraft.Detail>(
			genRequestUrl(RequestUrl.publishFlow, { flowId }),
			publishDetail,
		)
	},

	/**
	 * Rollback flow version
	 */
	restoreFlow(flowId: string, versionId: string) {
		return fetch.post<null>(genRequestUrl(RequestUrl.restoreFlow, { flowId, versionId }))
	},

	/**
	 * Change flow enable status
	 */
	changeEnableStatus(id: string) {
		return fetch.post<null>(genRequestUrl(RequestUrl.changeEnableStatus, { flowId: id }))
	},

	/**
	 * Single node debugging
	 */
	testNode(params: TestNodeParams) {
		return fetch.post<NodeTestingResult>(genRequestUrl(RequestUrl.testNode), params)
	},

	/**
	 * Get available LLM models
	 */
	getLLMModal() {
		return fetch.get<{ models: LLMModalOption[] }>(genRequestUrl(RequestUrl.getLLMModal))
	},

	/**
	 * Add open platform application to specified workflow
	 */
	bindOpenApiAccount(flowId: string, openPlatformAppIds: string[]) {
		return fetch.post(genRequestUrl(RequestUrl.bindOpenApiAccount, { flowId }), {
			open_platform_app_ids: openPlatformAppIds,
		})
	},

	/**
	 * Remove open platform application from specified workflow
	 */
	removeOpenApiAccount(flowId: string, openPlatformAppIds: string[]) {
		return fetch.delete(genRequestUrl(RequestUrl.removeOpenApiAccount, { flowId }), {
			open_platform_app_ids: openPlatformAppIds,
		})
	},

	/**
	 * Get list of open platform applications bound to specified workflow
	 */
	getOpenApiAccountList(flowId: string, page = 1, pageSize = 100) {
		return fetch.post<WithPage<PlatformItem[]>>(
			genRequestUrl(RequestUrl.getOpenApiAccountList, { flowId }),
			{
				page,
				page_size: pageSize,
			},
		)
	},

	/**
	 * Get my open platform application list
	 */
	getOpenPlatformOfMine(page = 1, pageSize = 100) {
		return fetch.post<WithPage<PlatformItem[]>>(
			genRequestUrl(RequestUrl.getOpenPlatformOfMine),
			{
				page,
				page_size: pageSize,
			},
		)
	},

	/**
	 * Get sub-flow arguments
	 */
	getSubFlowArguments(flowId: string) {
		return fetch.get<SubFlowArgument>(genRequestUrl(RequestUrl.getSubFlowArgument, { flowId }))
	},

	/**
	 * Save API Key
	 */
	saveApiKey(params: ApiKeyRequestParams, flowId: string) {
		return fetch.post<ApiKey>(genRequestUrl(RequestUrl.saveApiKey, { flowId }), params)
	},

	/**
	 * Get API Key list
	 */
	getApiKeyList(flowId: string, page = 1, pageSize = 100) {
		return fetch.post<WithPage<ApiKey[]>>(genRequestUrl(RequestUrl.getApiKeyList, { flowId }), {
			page,
			page_size: pageSize,
		})
	},

	/**
	 * Get API Key details
	 */
	getApiKeyDetail(id: string, flowId: string) {
		return fetch.get<ApiKey>(genRequestUrl(RequestUrl.getApiKeyDetail, { id, flowId }))
	},

	/**
	 * Delete API Key
	 */
	deleteApiKey(id: string, flowId: string) {
		return fetch.delete<null>(genRequestUrl(RequestUrl.deleteApiKey, { id, flowId }))
	},

	/**
	 * Rebuild API Key
	 */
	rebuildApiKey(id: string, flowId: string) {
		return fetch.post<ApiKey>(genRequestUrl(RequestUrl.rebuildApiKey, { id, flowId }))
	},

	/**
	 * Save API Key v1
	 */
	saveApiKeyV1(params: Flow.ApiKeyRequestParamsV1) {
		return fetch.post<ApiKey>(genRequestUrl(RequestUrl.saveApiKeyV1), params)
	},

	/**
	 * Get API Key v1 list
	 */
	getApiKeyListV1(params: Pick<Flow.ApiKeyRequestParamsV1, "rel_type" | "rel_code">) {
		return fetch.post<WithPage<ApiKey[]>>(genRequestUrl(RequestUrl.getApiKeyListV1), params)
	},

	/**
	 * Get API Key v1 details
	 */
	getApiKeyDetailV1(code: string) {
		return fetch.get<ApiKey>(genRequestUrl(RequestUrl.getApiKeyDetailV1, { code }))
	},

	/**
	 * Delete API Key v1
	 */
	deleteApiKeyV1(code: string) {
		return fetch.delete<null>(genRequestUrl(RequestUrl.deleteApiKeyV1, { code }))
	},

	/**
	 * Rebuild API Key v1
	 */
	rebuildApiKeyV1(code: string) {
		return fetch.post<ApiKey>(genRequestUrl(RequestUrl.rebuildApiKeyV1, { code }))
	},

	/**
	 * Get sheet list
	 */
	getSheets(fileId: string) {
		return fetch.get<{ sheets: Record<string, Sheet.Detail> }>(
			genRequestUrl(RequestUrl.getSheets, { fileId }),
		)
	},

	/**
	 * Get file list
	 */
	getFiles(params: File.RequestParams) {
		return fetch.post<WithPage<File.Detail[]>>(genRequestUrl(RequestUrl.getFiles), params)
	},

	/**
	 * Get file details
	 */
	getFile(fileId: string) {
		return fetch.get<File.Detail>(genRequestUrl(RequestUrl.getFile, { fileId }))
	},

	/**
	 * Get tool list
	 */
	getToolList({ page = 1, pageSize = 10, name }: FlowTool.GetToolListParams) {
		return fetch.post<WithPage<DelightfulFlow.Flow[]>>(genRequestUrl(RequestUrl.getToolList), {
			page,
			page_size: pageSize,
			name,
		})
	},

	/**
	 * Get useable tool list
	 */
	getUseableToolList() {
		return fetch.post<WithPage<UseableToolSet.Item[]>>(
			genRequestUrl(RequestUrl.getUseableToolList),
		)
	},

	/**
	 * Get useable database list
	 */
	getUseableDatabaseList() {
		return fetch.post<WithPage<Knowledge.KnowledgeItem[]>>(
			genRequestUrl(RequestUrl.getUseableDatabaseList),
		)
	},

	/**
	 * Get tool details
	 */
	getToolDetail(id: string) {
		return fetch.get<FlowTool.Detail>(genRequestUrl(RequestUrl.getTool, { id }))
	},

	/**
	 * Delete tool
	 */
	deleteTool(id: string) {
		return fetch.delete<null>(genRequestUrl(RequestUrl.deleteTool, { id }))
	},

	/**
	 * Save tool
	 */
	saveTool(params: FlowTool.SaveToolParams) {
		return fetch.post<FlowTool.Detail>(genRequestUrl(RequestUrl.saveTool), params)
	},

	/**
	 * Get available tools
	 */
	getAvailableTools(toolIds: string[]) {
		return fetch.post<WithPage<DelightfulFlow.Flow[]>>(genRequestUrl(RequestUrl.getAvailableTools), {
			codes: toolIds,
		})
	},

	/**
	 * Get methods data source
	 */
	getMethodsDataSource() {
		return fetch.post<{
			expression_data_source: MethodOption[]
		}>(genRequestUrl(RequestUrl.getMethodsDataSource))
	},

	/**
	 * Get vision models
	 */
	getVisionModels(category: string = "vlm") {
		return fetch.post<Flow.VLMProvider[]>(genRequestUrl(RequestUrl.getVisionModels), {
			category,
		})
	},

	/** Call tool or flow with API Key */
	callToolOrFlow(apiKey: string, params: object) {
		return fetch.post<any>(genRequestUrl(RequestUrl.callToolOrFlow), {
			params,
			headers: {
				"api-key": apiKey,
			},
		})
	},

	/** Call agent for conversation */
	callAgent(apiKey: string, params: { message: string; conversation_id: string }) {
		return fetch.post<any>(genRequestUrl(RequestUrl.callAgent), {
			params,
			headers: {
				"api-key": apiKey,
			},
		})
	},

	/**
	 * Get node template
	 */
	getNodeTemplate(nodeType: string) {
		return fetch.post<DelightfulFlow.Node>(genRequestUrl(RequestUrl.getNodeTemplate), {
			params: {},
			node_type: nodeType,
            node_version: "latest"
		})
	},

	/** Create / update MCP */
	saveMcp(params: Flow.Mcp.SaveParams) {
		return fetch.post<Flow.Mcp.Detail>(genRequestUrl(RequestUrl.saveMcp), params)
	},

	/** Get MCP list */
	getMcpList(params: Flow.Mcp.GetListParams) {
		return fetch.post<WithPage<Flow.Mcp.ListItem[]>>(genRequestUrl(RequestUrl.getMcpList), {
			page: params.page,
			page_size: params.pageSize,
			name: params.name,
		})
	},

	/** Get MCP details */
	getMcp(id: string) {
		return fetch.get<Flow.Mcp.Detail>(genRequestUrl(RequestUrl.getMcp, { id }))
	},

	/** Delete MCP */
	deleteMcp(id: string) {
		return fetch.delete<null>(genRequestUrl(RequestUrl.deleteMcp, { id }))
	},

	/** Get MCP tool list */
	getMcpToolList(code: string) {
		return fetch.post<WithPage<Flow.Mcp.ListItem[]>>(
			genRequestUrl(RequestUrl.getMcpToolList, { code }),
		)
	},

	/** Save MCP tool (add, update, update version) */
	saveMcpTool(params: Flow.Mcp.SaveParams, code: string) {
		return fetch.post<Flow.Mcp.Detail>(genRequestUrl(RequestUrl.saveMcpTool, { code }), params)
	},

	/** Delete MCP tool */
	deleteMcpTool(id: string, code: string) {
		return fetch.delete<null>(genRequestUrl(RequestUrl.deleteMcpTool, { id, code }))
	},

	/** Get MCP tool details */
	getMcpToolDetail(id: string, code: string) {
		return fetch.get<Flow.Mcp.Detail>(genRequestUrl(RequestUrl.getMcpToolDetail, { id, code }))
	},
})
