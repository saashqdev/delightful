/* eslint-disable no-template-curly-in-string */

export const enum RequestUrl {
	/** User */
	/** Get user account list */
	getUserAccounts = "/v4/users/accounts",
	/** Get managed user details */
	getUserInfo = "/v4/users/info",
	/** Get user devices */
	getUserDevices = "/v4/users/login/devices",
	/** Sign out device */
	logoutDevices = "/v4/users/logout/device",
	/** Get verification code */
	getUsersVerificationCode = "/v4/users/send_sms",
	/** Get verification code for changing phone */
	getUserVerificationCode = "/v4/user/send_sms",
	/** Change password */
	changePassword = "/v4/users/pwd",
	/** Change phone number */
	changePhone = "/v4/users/phone",
	/** Login */
	Login = "/api/v1/sessions",
	/** Logout */
	Logout = "/v4/users/logout",
	/** Third-party login */
	thirdPartyLogins = "/v4/user/fast_login",

	/** Get Teamshare user info */
	getTeamshareUserInfo = "/v4/users/info",
	/** Get Teamshare user departments */
	getTeamshareUserDepartments = "/api/v2/users/department",
	/** User manual */
	getUserManual = "/api/v2/delightful/contact/user/user-manual",

	/** Get global shared configuration */
	/** Get application i18n language and country code */
	getInternationalizedSettings = "/v4/locales/settings",
	/** Get private deployment configuration */
	getPrivateConfigure = "/v4/private-deployments/configuration",

	/** Application */
	/** Get application list */
	getAppList = "/v4/workbench/applications",
	getAppCategoriesList = "/v4/workbench/applications/categories",
	/** Get frequently used applications */
	getCommonApplications = "/v4/workbench/common/applications",
	/** Categorized applications */
	getCommonCategories = "/v4/workbench/applications/categories",
	/** Application details */
	getApplicationDetails = "/v4/workbench/applications/${code}/info",

	/** Organization */

	/** Contact */
	/** Get all prompt friends */
	getSquarePrompts = "/api/v2/delightful/friend/square",
	/** Search prompt friends square */
	getSquareFriendsByKeyword = "/api/v2/delightful/friend/search",
	/** Get friend list */
	getFriends = "/api/v1/contact/friends",
	/** Add prompt friend */
	addPropmtFriend = "/api/v1/contact/friends/${friendId}",
	/** Get organization structure */
	getOrganization = "/api/v1/contact/departments/${id}/children",
	/** Get all types of user info by IDs */
	getUserInfoByIds = "/api/v1/contact/users/queries",
	/** [New] Get department user list */
	getDepartmentUsers = "/api/v1/contact/departments/${id}/users",
	/** Search users */
	searchUser = "/api/v1/contact/users/search",
	/** Get department info */
	getDepartmentInfo = "/api/v1/contact/departments/${id}",
	/** Fuzzy search departments */
	searchOrganizations = "/api/v1/contact/departments/search",
	/** Get Teamshare userId */
	getTeamshareUserId = "/api/v1/users/me/teamshare-id",
	/** Get department documentation */
	getDepartmentDocument = "/api/v1/departments/${id}/document",

	/** Chat */
	/** Fetch offline messages */
	messagePull = "/api/v1/im/messages/page",
	/** Fetch recent messages */
	messagePullCurrent = "/api/v1/im/messages",
	/** Get conversation list */
	getConversationList = "/api/v1/im/conversations/queries",
	/** Conversation window operations (remove, mute, pin, badge, etc.) */
	handleConversation = "/delightful_friend/add",
	/** Create group conversation */
	createGroupConversation = "/api/v1/contact/groups",
	/** Get group conversation detail */
	getGroupConversationDetail = "/api/v1/contact/groups/queries",
	/** Get group conversation members */
	getGroupConversationMembers = "/api/v1/contact/groups/${id}/members",
	/** Get user groups */
	getUserGroups = "/api/v1/contact/users/self/groups",
	/** Get message recipient list */
	getMessageReceiveList = "/api/v1/im/messages/${messageId}/recipients",
	/** Add group members */
	addGroupUsers = "/api/v1/contact/groups/${id}/members",
	/** Leave group conversation */
	leaveGroup = "/api/v1/contact/groups/${id}/members/self",
	/** Kick group members */
	kickGroupUsers = "/api/v1/contact/groups/${id}/members",
	/** Disband group */
	removeGroup = "/api/v1/contact/groups/${id}",
	/** Update group info */
	updateGroupInfo = "/api/v1/contact/groups/${id}",
	/** Get all conversation info by IDs */
	getConversationInfoByIds = "/api/v1/im/conversations/queries",
	/** Get chat file URLs */
	getChatFileUrls = "/api/v1/im/files/download-urls/queries",
	/** Get AI summary message */
	getConversationAiAutoCompletion = "/api/v1/im/conversations/${conversationId}/completions",
	/** Get AI assistant bot info */
	getAiAssistantBotInfo = "/api/v1/agents/versions/${userId}/user",
	/** Update AI assistant quick instruction config */
	updateAiConversationQuickInstructionConfig = "/api/v1/im/conversations/${conversationId}/instructs",
	/** Get messages for a conversation */
	getConversationMessages = "/api/v1/im/conversations/${conversationId}/messages/queries",
	/** Get messages by app message ID */
	getMessagesByAppMessageId = "/api/v1/messages/app-message-ids/${appMessageId}/queries",

	/**
	 * Batch fetch messages under a conversation
	 * @deprecated Behavior not as expected; planned for deprecation
	 */
	batchGetConversationMessages = "/api/v1/im/conversations/${conversationId}/messages",
	/** Batch fetch multiple messages under a conversation */
	batchGetConversationMessagesV2 = "/api/v1/im/conversations/messages/queries",
	/** [New] Query user task list */
	getTaskList = "/api/v1/user/task",
	/** [New] Create user task */
	createTask = "/api/v1/user/task",
	/** [New] Get user task */
	getTask = "/api/v1/user/task/${id}",

	/** File */
	/** Check file upload status */
	checkFileUploadStatus = "/api/v1/file-utils/upload-verifications",
	/** Get upload credentials */
	getUploadCredentials = "/api/v1/file/temporary-credential",
	/** Report file upload */
	reportFileUpload = "/api/v1/im/files",
	/** Get file download link */
	getFileDownloadLink = "/api/v1/file/publicFileDownload",

	/** Drive */
	/** Get file list */
	getFileList = "/api/v2/files/cascade/queries",
	/** Get file detail */
	getFileDetail = "/api/anonymous/v2/files/${fileId}",
	/** Get favorites list */
	getFavoriteList = "/api/v1/favorites/queries",
	/** Get recent files list */
	getRecentFileList = "/api/v1/recent_files/queries",
	/** Add or remove favorite */
	addOrCancelFavorite = "/api/v1/favorites",
	/** Get upload file info */
	getUploadFileInfo = "/api/wps/url/queries",
	/** Edit file */
	editFile = "/api/v1/files/${id}",
	/** Delete file */
	trashFile = "/api/v1/trashes",
	/* Get trash files */
	getTrashFiles = "/api/v2/trashes/queries",
	/** Create file duplicate */
	duplicateFile = "/api/v1/files/${fileId}/duplication",
	/** Create file */
	newFile = "/api/v1/files",
	/** File permissions list */
	getFilePermissions = "/api/v1/files/${fileId}/permissions",
	/** Move file */
	moveFileTo = "/api/v1/file_movements",
	/** Get template library categories */
	getTemplateLibraryCategories = "/api/v1/template_library/categories",
	/* Get template library file list */
	getTemplateLibraryFiles = "/api/v1/template_library",
	/** Get template library file cover */
	getFileCdnUrl = "/api/v1/file-utils/temporary-urls/queries",
	/** Cloud drive file search */
	searchFiles = "/open/oauth/home/search",

	/** Flow */
	/** Get workflow list */
	getFlowList = "/api/v1/flows/queries",
	/** Flow trial run */
	testFlow = "/api/v1/flows/${flowId}/flow-debug",
	/** Create or update flow */
	addOrUpdateFlowBaseInfo = "/api/v1/flows",
	/** Save flow detail */
	saveFlow = "/api/v1/flows/${flowId}/save-node",
	/** Get flow detail */
	getFlow = "/api/v1/flows/${flowId}",
	/** Delete flow */
	deleteFlow = "/api/v1/flows/${flowId}",
	/** Change flow enable status */
	changeEnableStatus = "/api/v1/flows/${flowId}/change-enable",
	/** Single node debug */
	testNode = "/api/v1/flows/node-debug",
	/** Get available LLM models */
	getLLMModal = "/api/v1/flows/models",
	/** Get open platform apps bindable by current user */
	getOpenPlatformOfMine = "/api/v1/flows/open-platform/applications",
	/** Bind open platform app to flow */
	bindOpenApiAccount = "/api/v1/flows/${flowId}/open-platform/apps",
	/** Remove open platform app from flow */
	removeOpenApiAccount = "/api/v1/flows/${flowId}/open-platform/apps",
	/** Get open platform apps bound to flow */
	getOpenApiAccountList = "/api/v1/flows/${flowId}/open-platform/apps/queries",
	/** Get subflow input and output */
	getSubFlowArgument = "/api/v1/flows/${flowId}/params",
	/** Save flow draft */
	saveFlowDraft = "/api/v1/flows/${flowId}/draft",
	/** Get flow draft list */
	getFlowDraftList = "/api/v1/flows/${flowId}/draft/queries",
	/** Get flow draft detail */
	getFlowDratDetail = "/api/v1/flows/${flowId}/draft/${draftId}",
	/** Delete flow draft */
	deleteFlowDraft = "/api/v1/flows/${flowId}/draft/${draftId}",

	/** Get toolset list */
	getToolList = "/api/v1/flows/tool-set/queries",
	/** Get available toolsets */
	getUseableToolList = "/api/v1/flows/queries/tool-sets",
	/** Get toolset detail */
	getTool = "/api/v1/flows/tool-set/${id}",
	/** Save toolset */
	saveTool = "/api/v1/flows/tool-set",
	/** Delete toolset */
	deleteTool = "/api/v1/flows/tool-set/${id}",
	/** Get available tools */
	getAvailableTools = "/api/v1/flows/queries/tools",
	/** Create or update MCP */
	saveMcp = "/api/v1/mcp/server",
	/** Get MCP list */
	getMcpList = "/api/v1/mcp/server/queries",
	/** Get MCP detail */
	getMcp = "/api/v1/mcp/server/${id}",
	/** Delete MCP */
	deleteMcp = "/api/v1/mcp/server/${id}",
	/** Get MCP tool list */
	getMcpToolList = "/api/v1/mcp/server/${code}/tools",
	/** Save MCP tool */
	saveMcpTool = "/api/v1/mcp/server/${code}/tool",
	/** Delete MCP tool */
	deleteMcpTool = "/api/v1/mcp/server/${code}/tool/${id}",
	/** Get MCP tool detail */
	getMcpToolDetail = "/api/v1/mcp/server/${code}/tool/${id}",
	/** Get function expression data sources */
	getMethodsDataSource = "/api/v1/flows/expression-data-source",
	/** Get available knowledge base list */
	getUseableDatabaseList = "/api/v1/flows/queries/knowledge",
	/** Get available Teamshare knowledge base list */
	getUseableTeamshareDatabaseList = "/api/v1/teamshare/knowledge/manageable",
	/** Get vision model data sources */
	getVisionModels = "/org/admin/service-providers/category",
	/** Get active models by category */
	getActiveModelByCategory = "/api/v1/admin/service-providers/by-category",
	/** Get official rerank model list */
	getRerankModels = "/api/v1/knowledge-base/providers/rerank/list",
	/** Get knowledge base vectorization progress */
	getTeamshareKnowledgeProgress = "/api/v1/teamshare/knowledge/manageable-progress",
	/** Start knowledge base vector creation */
	createTeamshareKnowledgeVector = "/api/v1/teamshare/knowledge/start-vector",
	/** Call Agent via API key */
	callAgent = "/api/chat",
	/** Call tool or flow via API key */
	callToolOrFlow = "/api/param-call",
	/** Get node template */
	getNodeTemplate = "/api/v1/flows/node-template",

	/** Publish flow version */
	publishFlow = "/api/v1/flows/${flowId}/version/publish",
	/** Roll back flow version */
	restoreFlow = "/api/v1/flows/${flowId}/version/${versionId}/rollback",
	/** Query version list */
	getFlowPublishList = "/api/v1/flows/${flowId}/version/queries?page=${page}&page_size=${pageSize}",
	/** Query version detail */
	getFlowPublishDetail = "/api/v1/flows/${flowId}/version/${versionId}",

	/** Knowledge */
	/** Create knowledge base */
	createKnowledge = "/api/v1/knowledge-bases",
	/** Update knowledge base */
	updateKnowledge = "/api/v1/knowledge-bases/${code}",
	/** Knowledge base list */
	getKnowledgeList = "/api/v1/knowledge-bases/queries",
	/** Save knowledge base */
	saveKnowledge = "/api/v2/delightful/knowledge",
	/** Knowledge base detail */
	getKnowLedgeDetail = "/api/v1/knowledge-bases/${code}",
	/** Delete knowledge base */
	deleteKnowledge = "/api/v1/knowledge-bases/${code}",
	/** Knowledge base matching */
	matchKnowledge = "/api/v1/knowledge-bases/similarity",
	/** Get knowledge base document list */
	getKnowledgeDocumentList = "/api/v1/knowledge-bases/${code}/documents/queries",
	/** Add knowledge base document */
	addKnowledgeDocument = "/api/v1/knowledge-bases/${code}/documents",
	/** Get knowledge base document detail */
	getKnowledgeDocumentDetail = "/api/v1/knowledge-bases/${knowledge_code}/documents/${document_code}",
	/** Update knowledge base document */
	updateKnowledgeDocument = "/api/v1/knowledge-bases/${knowledge_code}/documents/${document_code}",
	/** Delete knowledge base document */
	deleteKnowledgeDocument = "/api/v1/knowledge-bases/${knowledge_code}/documents/${document_code}",
	/** Fragment preview */
	segmentPreview = "/api/v1/knowledge-bases/fragments/preview",
	/** Recall test */
	recallTest = "/api/v1/knowledge-bases/${knowledge_code}/fragments/similarity",
	/** Get embedding model list */
	getEmbeddingModelList = "/api/v1/knowledge-bases/providers/embedding/list",
	/** Fragment list */
	getFragmentList = "/api/v1/knowledge-bases/${knowledge_base_code}/documents/${document_code}/fragments/queries",
	/** Re-vectorize document */
	revectorizeDocument = "/api/v1/knowledge-bases/${knowledge_base_code}/documents/${document_code}/re-vectorized",
	/** Create fragment */
	createFragment = "/api/v1/knowledge-bases/${knowledge_base_code}/documents/${document_code}/fragments",
	/** Update fragment */
	updateFragment = "/api/v1/knowledge-bases/${knowledge_base_code}/documents/${document_code}/fragments/${id}",
	/** Fragment detail */
	getFragmentDetail = "/api/v1/knowledge-bases/${knowledge_base_code}/documents/${document_code}/fragments/${id}",
	/** Delete fragment */
	deleteFragment = "/api/v1/knowledge-bases/${knowledge_base_code}/documents/${document_code}/fragments/${id}",
	/** Knowledge base rebuild - to be deprecated */
	rebuildKnowledge = "/api/v2/delightful/knowledge/${id}/rebuild",

	/** Save API key */
	saveApiKey = "/api/v1/flows/${flowId}/api-key",
	/** Query API key list */
	getApiKeyList = "/api/v1/flows/${flowId}/api-key/queries",
	/** Query API key detail */
	getApiKeyDetail = "/api/v1/flows/${flowId}/api-key/${id}",
	/** Delete API key */
	deleteApiKey = "/api/v1/flows/${flowId}/api-key/${id}",
	/** Rebuild API key */
	rebuildApiKey = "/api/v1/flows/${flowId}/api-key/${id}/rebuild",

	/** Save API key v1 */
	saveApiKeyV1 = "/api/v1/authentication/api-key",
	/** Query API key v1 */
	getApiKeyListV1 = "/api/v1/authentication/api-key/queries",
	/** Query API key v1 detail */
	getApiKeyDetailV1 = "/api/v1/authentication/api-key/${code}",
	/** Delete API key v1 */
	deleteApiKeyV1 = "/api/v1/authentication/api-key/${code}",
	/** Rebuild API key v1 */
	rebuildApiKeyV1 = "/api/v1/authentication/api-key/${code}/rebuild",

	/** Get topic list */
	getTopicList = "/api/v1/im/conversations/${conversationId}/topics/queries",
	/** Get topic messages */
	getTopicMessages = "/api/v2/delightful/topic/messages",
	/** Smart rename topic */
	getDelightfulTopicName = "/api/v1/im/conversations/${conversationId}/topics/${topicId}/name",

	/** Get files */
	getFiles = "/api/v1/teamshare/multi-table/file/queries",
	/** Get data sheets */
	getSheets = "/api/v1/teamshare/multi-table/${fileId}/sheets",
	/** Get file detail */
	getFile = "/api/v1/teamshare/multi-table/${fileId}",

	/** Auth */
	/** Update resource authorization */
	updateResourceAccess = "/api/v1/operation-permissions/resource-access",
	/** Query resource authorization */
	getResourceAccess = "/api/v1/operation-permissions/resource-access",
	/** Transfer creator */
	transferOwner = "/api/v1/operation-permissions/transfer-owner",
	/** Bind Authorization + authorization code in Delightful */
	bindDelightfulAuthorization = "/api/v1/auth/status",
	/** [New] Exchange user token for one-time temp token */
	userTokenToTempToken = "/api/v1/auth/temp-token",
	/** [New] Exchange temp token for user token */
	tempTokenToUserToken = "/api/v1/auth/teamshare-token?temp_token=${tempToken}",
	/** Get environment code for current account */
	getDeploymentCode = "/api/v1/auth/environment",
	/** Get admin console permissions */
	getAdminPermission = "/api/v1/delightful/operation-permissions/organization-admin",

	/** bots */
	/** Get marketplace bots */
	getMarketBotList = "/api/v1/agents/versions/marketplace",
	/** Get enterprise bot list */
	getOrgBotList = "/api/v1/agents/versions/organization",
	/** Get personal bot list */
	getUserBotList = "/api/v1/agents/queries",
	/** Save bot */
	saveBot = "/api/v1/agents",
	/** Bot detail */
	getBotDetail = "/api/v1/agents/${agentId}",
	/** Delete bot */
	deleteBot = "/api/v1/agents/${agentId}",
	/** Get bot version list */
	getBotVersionList = "/api/v1/agents/${agentId}/versions",
	/** Get published bot version detail */
	getBotVersionDetail = "/api/v1/agents/versions/${agentVersionId}",
	/** Update bot status */
	updateBotStatus = "/api/v1/agents/${agentId}/status",
	/** Publish bot */
	publishBot = "/api/v1/agents/versions",
	/** Get bot max version */
	getBotMaxVersion = "/api/v1/agents/${agentId}/max",
	/** Publish bot to organization */
	publishBotToOrg = "/api/v1/agents/${agentId}/enterprise-status",
	/** Register bot and add friend */
	registerAndAddFriend = "/api/v1/agents/${agentVersionId}/register-friend",
	/** Whether bot was modified */
	isBotUpdate = "/api/v1/agents/${agentId}/is-updated",
	/** Get default icons */
	getDefaultIcon = "/api/v1/file/default-icons",
	/** Save interactive instructions */
	saveInstruct = "/api/v1/agents/${agentId}/instructs",
	/** Get interactive instruction types */
	getInstructTypeOption = "/api/v1/agent-options/instruct-types",
	/** Get interactive instruction group types */
	getInstructGroupTypeOption = "/api/v1/agent-options/instruct-group-types",
	/** Get instruction status type icons */
	getInstructStatusIcons = "/api/v1/agent-options/instruct-state-icons",
	/** Get instruction status type color sets */
	getInstructStatusColors = "/api/v1/agent-options/instruct-state-colors",
	/** Get system instructions */
	getSystemInstruct = "/api/v1/agent-options/instruct-system",
	/** Get configured third-party platforms */
	getThirdPartyPlatforms = "/api/v1/agents/third-platform/${botId}/list",

	/** calendar */
	/** Create schedule */
	createCalendar = "/api/v1/schedules",
	/** Create AI schedule - no v1 */
	createAiCalendar = "/api/v2/delightful/meetingrooms/schedules/ai-create-template",
	/** Get schedule detail */
	getCalendarDetail = "/api/v1/schedules/${id}",
	/** Get schedule for a specific day */
	getCalendarByDay = "/api/v2/delightful/meetingrooms/schedules/queries",
	/** Update schedule */
	updateCalendar = "/api/v1/schedules",
	/** Update invite status of schedule */
	updateCalendarStatus = "/api/v1/schedules/${id}/participants",
	/** Delete schedule */
	deleteCalendar = "/api/v1/schedules/${id}",
	/** Get schedule summary */
	getCalendarSummary = "/api/v1/schedules/${id}/summary",
	/** Comment on schedule */
	commentCalendar = "/api/v1/schedules/${id}/comments",
	/** Add participants */
	addParticipant = "/api/v1/schedules/${id}/participants",
	/** Get user availability */
	getUserStatus = "/api/v1/schedules/user/availability",
	/** Get status of schedule participants */
	getParticipantAvaliablity = "/api/v1/schedules/${id}/participants",
	/** Transfer schedule organizer */
	transferOrganizer = "/api/v1/schedules/${id}/owner",
	/** Get meeting room list */
	getMeetingRooms = "/api/v1/meeting-rooms",
	/** Get available meeting rooms in time range */
	getAvaliableMeetingRooms = "/api/v1/meeting-rooms/available",
	/** Get meeting room filter criteria */
	getFilteringCriteria = "/api/v1/meeting-rooms/locations",
	/** Create group chat from schedule */
	createCalendarGroup = "/api/v1/schedules/${id}/chat-groups",

	/** favorites */
	/** Favorites */
	/** Add favorite */
	addFavorites = "/api/v1/favorites",
	/** Delete favorite */
	deleteFavorites = "/api/v1/favorites",
	/** Search favorites */
	searchFavorites = "/api/v1/favorites/search",
	/** Get favorites list */
	getFavorites = "/api/v1/favorites/list/queries",

	/** Link tag */
	relationLabel = "/api/v1/tags/relations",
	/** Add tag */
	addLabel = "/api/v1/tags",
	/** Delete tag */
	deleteLabel = "/api/v1/tags",
	/** Edit tag */
	editLabel = "/api/v1/tags/${id}",
	/** Get tag list */
	getLabelList = "/api/v1/tags",

	/** Approval */
	/** Start approval */
	StartApproval = "/api/oa-approval/instances/start",

	/** Global search */
	globalSearch = "/api/v1/global-search/queries",
}

export const enum RequestMethod {
	GET = "GET",
	POST = "POST",
	DELETE = "DELETE",
	PUT = "PUT",
}
