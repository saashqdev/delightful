import type { DelightfulFlow } from "@delightful/delightful-flow/DelightfulFlow/types/flow"
import type {
	ApiKeyRequestParams,
	FlowDraft,
	FlowTool,
	GetFlowListParams,
	TestNodeParams,
	TriggerConfig,
} from "@/types/flow"
import { FlowType } from "@/types/flow"
import type { File } from "@/types/sheet"
import type * as apis from "@/apis"

class FlowService {
	protected flowApi: typeof apis.FlowApi

	constructor(dependencies: typeof apis) {
		this.flowApi = dependencies.FlowApi
	}

	/** Query flow list */
	getFlowList({ type = FlowType.Main, page = 1, pageSize = 100, name }: GetFlowListParams) {
		return this.flowApi.getFlowList({
			type,
			page,
			pageSize,
			name,
		})
	}

	/** Query flow details */
	getFlow(flowId: string) {
		return this.flowApi.getFlow(flowId)
	}

	/** Flow trial run */
	testFlow(flow: DelightfulFlow.Flow & { trigger_config: TriggerConfig }) {
		return this.flowApi.testFlow(flow)
	}

	/** Add or update flow basic information */
	addOrUpdateFlowBaseInfo(flow: Partial<DelightfulFlow.Flow> & { type?: FlowType }) {
		return this.flowApi.addOrUpdateFlowBaseInfo(flow)
	}

	/** Delete flow */
	deleteFlow(flowId: string) {
		return this.flowApi.deleteFlow(flowId)
	}

	/** Save flow details */
	saveFlow(flow: DelightfulFlow.Flow) {
		return this.flowApi.saveFlow(flow)
	}

	/** Save flow as draft */
	saveFlowDraft(draftDetail: FlowDraft.RequestArgs, flowId: string) {
		return this.flowApi.saveFlowDraft(draftDetail, flowId)
	}

	/** Query flow draft list */
	getFlowDraftList(flowId: string) {
		return this.flowApi.getFlowDraftList(flowId)
	}

	/** Query flow draft details */
	getFlowDraftDetail(flowId: string, draftId: string) {
		return this.flowApi.getFlowDraftDetail(flowId, draftId)
	}

	/** Delete flow draft */
	deleteFlowDraft(flowId: string, draftId: string) {
		return this.flowApi.deleteFlowDraft(flowId, draftId)
	}

	/** Query flow version list */
	getFlowPublishList(flowId: string, page = 1, pageSize = 200) {
		return this.flowApi.getFlowPublishList(flowId, page, pageSize)
	}

	/** Query flow version details */
	getFlowPublishDetail(flowId: string, versionId: string) {
		return this.flowApi.getFlowPublishDetail(flowId, versionId)
	}

	/** Publish flow version */
	publishFlow(publishDetail: FlowDraft.RequestArgs, flowId: string) {
		return this.flowApi.publishFlow(publishDetail, flowId)
	}

	/** Rollback flow version */
	restoreFlow(flowId: string, versionId: string) {
		return this.flowApi.restoreFlow(flowId, versionId)
	}

	/** Modify flow enable status */
	changeEnableStatus(id: string) {
		return this.flowApi.changeEnableStatus(id)
	}

	/** Single node debug */
	testNode(params: TestNodeParams) {
		return this.flowApi.testNode(params)
	}

	/** Get available LLM models */
	getLLMModal() {
		return this.flowApi.getLLMModal()
	}

	/** Add open platform application to specified workflow */
	bindOpenApiAccount(flowId: string, openPlatformAppIds: string[]) {
		return this.flowApi.bindOpenApiAccount(flowId, openPlatformAppIds)
	}

	/** Remove open platform application from specified workflow */
	removeOpenApiAccount(flowId: string, openPlatformAppIds: string[]) {
		return this.flowApi.removeOpenApiAccount(flowId, openPlatformAppIds)
	}

	/** Get bound open platform application list for specified workflow */
	getOpenApiAccountList(flowId: string, page = 1, pageSize = 100) {
		return this.flowApi.getOpenApiAccountList(flowId, page, pageSize)
	}

	/** Get current user's bindable open platform application list */
	getOpenPlatformOfMine(page = 1, pageSize = 100) {
		return this.flowApi.getOpenPlatformOfMine(page, pageSize)
	}

	/** Get subflow input and output parameters */
	getSubFlowArguments(flowId: string) {
		return this.flowApi.getSubFlowArguments(flowId)
	}

	/** Save api-key */
	saveApiKey(params: ApiKeyRequestParams, flowId: string) {
		return this.flowApi.saveApiKey(params, flowId)
	}

	/** Query api-key list */
	getApiKeyList(flowId: string, page = 1, pageSize = 100) {
		return this.flowApi.getApiKeyList(flowId, page, pageSize)
	}

	/** Query api-key details */
	getApiKeyDetail(id: string, flowId: string) {
		return this.flowApi.getApiKeyDetail(id, flowId)
	}

	/** Delete api-key  */
	deleteApiKey(id: string, flowId: string) {
		return this.flowApi.deleteApiKey(id, flowId)
	}

	/** Rebuild api-key  */
	rebuildApiKey(id: string, flowId: string) {
		return this.flowApi.rebuildApiKey(id, flowId)
	}

	/** Get data sheets  */
	getSheets(fileId: string) {
		return this.flowApi.getSheets(fileId)
	}

	/** Search file list */
	getFiles(params: File.RequestParams) {
		return this.flowApi.getFiles(params)
	}

	/** Search file list */
	getFile(fileId: string) {
		return this.flowApi.getFile(fileId)
	}

	/** Toolset list */
	getToolList({ page = 1, pageSize = 10, name }: FlowTool.GetToolListParams) {
		return this.flowApi.getToolList({ page, pageSize, name })
	}

	/** Available toolset list */
	getUseableToolList() {
		return this.flowApi.getUseableToolList()
	}

	/** Available knowledge base list */
	getUseableDatabaseList() {
		return this.flowApi.getUseableDatabaseList()
	}

	/** Query flow toolset details */
	getToolDetail(id: string) {
		return this.flowApi.getToolDetail(id)
	}

	/** Delete toolset */
	deleteTool(id: string) {
		return this.flowApi.deleteTool(id)
	}

	/** Save toolset */
	saveTool(params: FlowTool.SaveToolParams) {
		return this.flowApi.saveTool(params)
	}

	/** Save toolset */
	getAvailableTools(toolIds: string[]) {
		return this.flowApi.getAvailableTools(toolIds)
	}

	/** Get function expression data source */
	getMethodsDataSource() {
		return this.flowApi.getMethodsDataSource()
	}

	/** Get vision understanding model data source */
	getVisionModels(category: string = "vlm") {
		return this.flowApi.getVisionModels(category)
	}

	/** Call tool or flow with Api Key */
	callToolOrFlow(apiKey: string, params: object) {
		return this.flowApi.callToolOrFlow(apiKey, params)
	}

	/** Call Agent for conversation */
	callAgent(apiKey: string, params: { message: string; conversation_id: string }) {
		return this.flowApi.callAgent(apiKey, params)
	}
}

export { FlowService }
