// import request from "@/services/base/fetch/beDelightful"
import delightfulClient from "@/opensource/apis/clients/delightful"
// import { REMOTE_HTTP_URL } from "../constans"

export const getWorkspaces = async () => {
	return delightfulClient.post(
		`/api/v1/be-agent/workspaces/queries`,
		{
			page: 1,
			page_size: 999,
		},
		{
			headers: {
				"Content-Type": "application/json",
			},
		},
	)
}

export const deleteWorkspace = async ({ id }: { id: string }) => {
	return delightfulClient.post(
		`/api/v1/be-agent/workspaces/delete`,
		{ id },
		{
			headers: {
				"Content-Type": "application/json",
			},
		},
	)
}

// When no workspaceId, create workspace; when workspaceId exists, edit workspace
export const editWorkspace = async ({
	id,
	workspace_name,
}: {
	id?: string
	workspace_name: string
}) => {
	return delightfulClient.post(
		`/api/v1/be-agent/workspaces/save`,
		{ id, workspace_name },
		{
			headers: {
				"Content-Type": "application/json",
			},
		},
	)
}

export const editThread = async ({
	id,
	topic_name,
	workspace_id,
}: {
	id?: string
	topic_name: string
	workspace_id: string
}) => {
	return delightfulClient.post(
		`/api/v1/be-agent/topics/save`,
		{ id, topic_name, workspace_id },
		{
			headers: {
				"Content-Type": "application/json",
			},
		},
	)
}

export const deleteThread = async ({ id, workspace_id }: { id: string; workspace_id: string }) => {
	return delightfulClient.post(
		`/api/v1/be-agent/topics/delete`,
		{ id, workspace_id },
		{
			headers: {
				"Content-Type": "application/json",
			},
		},
	)
}

// Get attachments list by topic id
export const getAttachmentsByThreadId = async ({ id }: { id: string }) => {
	const isDelightfulShare = window?.location?.pathname?.includes("delightful-share")
	const url = isDelightfulShare
		? "/api/v1/be-agent/admin/topic/user-attachments"
		: `/api/v1/be-agent/topics/${id}/attachments`
	return delightfulClient.post(url, {
		page: 1,
		page_size: 999,
		file_type: ["user_upload", "process", "system_auto_upload"],
		// @ts-ignore Use window to add temporary token
		token: window.temporary_token || "",
		topic_id: id,
	})
}

// Get topics list by workspace id
export const getTopicsByWorkspaceId = async ({
	id,
	page,
	page_size,
}: {
	id: string
	page: number
	page_size: number
}) => {
	return delightfulClient.post(
		`/api/v1/be-agent/workspaces/${id}/topics`,
		{ page, page_size },
		{
			headers: {
				"Content-Type": "application/json",
			},
		},
	)
}

// Get historical messages by conversation ID
export const getMessagesByConversationId = async ({
	conversation_id,
	chat_topic_id,
	page_token = "",
	limit = 50,
	order = "asc",
}: {
	conversation_id: string
	chat_topic_id: string
	limit?: number
	order?: "asc" | "desc"
	page_token?: string
}): Promise<any> => {
	return delightfulClient.post(
		`/api/v1/im/conversations/${conversation_id}/messages/queries`,
		{
			limit,
			order,
			page_token,
			topic_id: chat_topic_id,
		},
		{
			headers: {
				"Content-Type": "application/json",
			},
		},
	)
}

// Get temporary download url by file id
export const getTemporaryDownloadUrl = async ({ file_ids }: { file_ids: string[] }) => {
	const isDelightfulShare = window?.location?.pathname?.includes("delightful-share")
	const apiPath = isDelightfulShare
		? "/api/v1/be-agent/admin/topic/user-attachment-url"
		: "/api/v1/be-agent/tasks/get-file-url"

	return delightfulClient.post(
		apiPath,
		// @ts-ignore Use window to add temporary token
		{ file_ids, token: window.temporary_token || "" },
	)
}

// Download file content from URL and return text
export const downloadFileContent = async (url: string): Promise<string> => {
	try {
		const response = await fetch(url, {
			method: "GET",
			headers: {
				"Content-Type": "application/json",
			},
		})

		if (!response.ok) {
			throw new Error(`Download failed: ${response.status} ${response.statusText}`)
		}

		return response.text()
	} catch (error) {
		console.error("Error downloading file content:", error)
		throw error
	}
}

// Create share topic
export const createShareTopic = async ({
	resource_id,
	resource_type,
	share_type,
	pwd,
}: {
	resource_id: string
	resource_type: number
	share_type: number
	pwd: string
}) => {
	const response = await delightfulClient.post("/api/v1/share/resources/create", {
		resource_id,
		resource_type,
		share_type,
		pwd,
	})
	return response
}

// Get share information by code
export const getShareInfoByCode = async ({ code }: { code: string }) => {
	const response = await delightfulClient.get(`/api/v1/share/resources/${code}/setting`)
	return response
}

// Smart topic rename
export const smartTopicRename = async ({
	id,
	user_question,
}: {
	id: string
	user_question: string
}) => {
	return delightfulClient.post("/api/v1/be-agent/topics/rename", {
		id,
		user_question,
	})
}

// Get topic detail by topic id
export const getTopicDetail = async ({ id }: { id: string }) => {
	return delightfulClient.get(`/api/v1/be-agent/topics/${id}`)
}

// Error log reporting
export const reportErrorLog = async ({ log }: { log: any }) => {
	return delightfulClient.post("/api/v1/be-agent/log/report", {
		log,
	})
}
// Get usage statistics
export const getUsage = async ({
	page = 1,
	page_size = 999,
	organization_code = "",
	user_name = "",
	topic_name = "",
	topic_id = "",
	sandbox_id = "",
	topic_status = "",
}: {
	page?: number
	page_size?: number
	organization_code?: string
	user_name?: string
	topic_name?: string
	topic_id?: string
	sandbox_id?: string
	topic_status?: string
}) => {
	try {
		console.log("getUsage with filters", {
			page,
			page_size,
			organization_code,
			user_name,
			topic_name,
			topic_id,
			sandbox_id,
			topic_status,
		})
		const response = await delightfulClient.post("/api/v1/be-agent/admin/statistics/user-usage", {
			page,
			page_size,
			organization_code,
			user_name,
			topic_name,
			topic_id,
			sandbox_id,
			topic_status,
		})
		// Ensure the returned data includes pagination info
		return response
	} catch (error) {
		console.error("Error in getUsage:", error)
		throw error
	}
}

// Get user usage metrics
export const getUsageMetrics = async () => {
	return delightfulClient.get("/api/v1/be-agent/admin/statistics/topic-metrics")
}

// Get organization list for all workspaces
export const getUsageList = async () => {
	return delightfulClient.get("/api/v1/be-agent/admin/workspace/organization-codes")
}

// Get topic sandbox status
export const getTopicSandboxStatus = async ({ id }: { id: string }) => {
	return delightfulClient.get(`/api/v1/be-agent/admin/topic/${id}/sandbox`)
}

// Get topic detail
export const getTopicDetailByTopicId = async ({ id }: { id: string }) => {
	return delightfulClient.get(`/api/v1/be-agent/topics/${id}`)
}
