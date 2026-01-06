import { genRequestUrl } from "@/utils/http"
import type { VectorKnowledge, WithPage } from "@/types/flow"
import type { Knowledge } from "@/types/knowledge"
import type { HttpClient } from "../core/HttpClient"
import { RequestUrl } from "../constant"
import { knowledgeType } from "@/opensource/pages/vectorKnowledge/constant"

export const generateKnowledgeApi = (fetch: HttpClient) => ({
	/**
	 * Create knowledge base
	 */
	createKnowledge(params: Knowledge.CreateKnowledgeParams) {
		return fetch.post<Knowledge.CreateKnowledgeResult>(
			genRequestUrl(RequestUrl.createKnowledge),
			params,
		)
	},

	/**
	 * Update knowledge base
	 */
	updateKnowledge(params: Knowledge.UpdateKnowledgeParams) {
		return fetch.put<Knowledge.Detail>(
			genRequestUrl(RequestUrl.updateKnowledge, { code: params.code }),
			params,
		)
	},

	/**
	 * Get knowledge base list
	 */
	getKnowledgeList({
		name,
		page,
		pageSize,
		searchType,
		type,
	}: {
		name: string
		page: number
		pageSize: number
		searchType: VectorKnowledge.SearchType
		type?: knowledgeType
	}) {
		return fetch.post<WithPage<Knowledge.KnowledgeItem[]>>(
			genRequestUrl(RequestUrl.getKnowledgeList),
			{
				name,
				page,
				page_size: pageSize,
				search_type: searchType,
				type,
			},
		)
	},

	/**
	 * Get knowledge base details
	 */
	getKnowledgeDetail(code: string) {
		return fetch.get<Knowledge.Detail>(genRequestUrl(RequestUrl.getKnowLedgeDetail, { code }))
	},

	/**
	 * Delete knowledge base
	 */
	deleteKnowledge(code: string) {
		return fetch.delete<Knowledge.Detail>(genRequestUrl(RequestUrl.deleteKnowledge, { code }))
	},

	/**
	 * Get knowledge base document list
	 */
	getKnowledgeDocumentList({
		code,
		name,
		page,
		pageSize,
	}: {
		code: string
		name?: string
		page?: number
		pageSize?: number
	}) {
		return fetch.post<WithPage<Knowledge.EmbedDocumentDetail[]>>(
			genRequestUrl(RequestUrl.getKnowledgeDocumentList, { code }),
			{
				name,
				page,
				page_size: pageSize,
			},
		)
	},

	/**
	 * Add knowledge base document
	 */
	addKnowledgeDocument(params: Knowledge.AddKnowledgeDocumentParams) {
		return fetch.post<Knowledge.Detail>(
			genRequestUrl(RequestUrl.addKnowledgeDocument, {
				code: params.knowledge_code,
			}),
			params,
		)
	},

	/**
	 * Get knowledge base document details
	 */
	getKnowledgeDocumentDetail(params: { knowledge_code: string; document_code: string }) {
		return fetch.get<Knowledge.EmbedDocumentDetail>(
			genRequestUrl(RequestUrl.getKnowledgeDocumentDetail, {
				knowledge_code: params.knowledge_code,
				document_code: params.document_code,
			}),
		)
	},

	/**
	 * Update knowledge base document
	 */
	updateKnowledgeDocument(params: Knowledge.UpdateKnowledgeDocumentParams) {
		return fetch.put<Knowledge.EmbedDocumentDetail>(
			genRequestUrl(RequestUrl.updateKnowledgeDocument, {
				knowledge_code: params.knowledge_code,
				document_code: params.document_code,
			}),
			{
				name: params.name,
				enabled: params.enabled,
				fragment_config: params.fragment_config,
			},
		)
	},

	/**
	 * Delete knowledge base document
	 */
	deleteKnowledgeDocument(params: Knowledge.DeleteKnowledgeDocumentParams) {
		return fetch.delete<Knowledge.Detail>(
			genRequestUrl(RequestUrl.deleteKnowledgeDocument, {
				knowledge_code: params.knowledge_code,
				document_code: params.document_code,
			}),
		)
	},

	/**
	 * Segment preview
	 */
	segmentPreview(params: Knowledge.SegmentPreviewParams) {
		return fetch.post<WithPage<Knowledge.FragmentItem[]>>(RequestUrl.segmentPreview, params)
	},

	/**
	 * Recall test
	 */
	recallTest(params: { knowledge_code: string; query: string }) {
		return fetch.post<WithPage<Knowledge.FragmentItem[]>>(
			genRequestUrl(RequestUrl.recallTest, {
				knowledge_code: params.knowledge_code,
			}),
			{ query: params.query },
		)
	},

	/**
	 * Get document fragment list
	 */
	getFragmentList(params: Knowledge.GetFragmentListParams) {
		return fetch.post<WithPage<Knowledge.FragmentItem[]>>(
			genRequestUrl(RequestUrl.getFragmentList, {
				knowledge_base_code: params.knowledgeBaseCode,
				document_code: params.documentCode,
			}),
			{
				page: params.page,
				page_size: params.pageSize,
			},
		)
	},

	/**
	 * Revectorize document
	 */
	revectorizeDocument(params: { knowledgeBaseCode: string; documentCode: string }) {
		return fetch.post(
			genRequestUrl(RequestUrl.revectorizeDocument, {
				knowledge_base_code: params.knowledgeBaseCode,
				document_code: params.documentCode,
			}),
		)
	},

	/**
	 * Get embedding model list
	 */
	getEmbeddingModelList() {
		return fetch.get<Knowledge.ServiceProvider[]>(RequestUrl.getEmbeddingModelList)
	},

	/**
	 * Rebuild knowledge base
	 */
	rebuildKnowledge(id: string) {
		return fetch.post<Knowledge.Detail>(genRequestUrl(RequestUrl.deleteKnowledge, { id }))
	},

	/**
	 * Get available teamshare database list
	 */
	getUseableTeamshareDatabaseList() {
		return fetch.get<WithPage<Knowledge.KnowledgeDatabaseItem[]>>(
			RequestUrl.getUseableTeamshareDatabaseList,
		)
	},

	/**
	 * Get progress of knowledge base with permissions
	 */
	getTeamshareKnowledgeProgress(params: Knowledge.GetTeamshareKnowledgeProgressParams) {
		return fetch.post<WithPage<Knowledge.KnowledgeDatabaseProgress[]>>(
			RequestUrl.getTeamshareKnowledgeProgress,
			params,
		)
	},

	/**
	 * Initiate knowledge base vector creation
	 */
	createTeamshareKnowledgeVector(params: Knowledge.CreateTeamshareKnowledgeVectorParams) {
		return fetch.post<null>(RequestUrl.createTeamshareKnowledgeVector, params)
	},

	/**
	 * Get all active models by category
	 */
	getActiveModelByCategory(params: Knowledge.GetActiveModelByCategoryParams) {
		return fetch.get<Knowledge.ServiceProvider[]>(
			genRequestUrl(RequestUrl.getActiveModelByCategory, {}, params),
		)
	},

	/**
	 * Get official rerank model list
	 */
	getRerankModels() {
		return fetch.get<Knowledge.ServiceProvider[]>(RequestUrl.getRerankModels)
	},
})
