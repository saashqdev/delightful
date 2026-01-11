export const enum RoutePath {
	/* /api and /v1 are reserved for backend services. Avoid using them to prevent reverse-proxy conflicts. */
	Home = "/home",
	Chat = "/chat",
	Login = "/login",
	Invite = "/login/invite",
	NotFound = "*",
	/**
	 * @description Cloud Drive
	 */
	/** Frequently Used Cloud Drive */
	DriveRecent = "/drive/recent",
	/** Personal Cloud Drive */
	DriveMe = "/drive/me",
	/** Enterprise Cloud Drive */
	DriveShared = "/drive/shared",
	/** Created by Me */
	DriveCreated = "/drive/mine",
	/** Recycle Bin */
	DriveTrash = "/drive/trash",
	/** Folder */
	DriveFolder = "/drive/folder/:folderId/:spaceType",
	/** Document */
	BiTable = "/base",
	/** Knowledge Base */
	Knowledge = "/knowledge",
	/** Knowledge Base - Home Display */
	KnowledgeWiki = "/wiki",
	/** Approval */
	Approval = "/approval",
	/** Tasks */
	Tasks = "/tasks",
	/** Favorites */
	Favorites = "/favorites",
	/** Settings */
	Settings = "/settings",
	/** Contacts */
	Contacts = "/contacts",
	ContactsOrganization = "/contacts/organization",
	ContactsAiAssistant = "/contacts/ai-assistant",
	ContactsMyFriends = "/contacts/my-friends",
	ContactsMyGroups = "/contacts/my-groups",
	Application = "/app",
	Workspace = "/workspace",
	AssistantList = "/flow/assistant",
	Flow = "/flow",
	Flows = "/flow/:type/list",
	VectorKnowledge = "/vector-knowledge",
	VectorKnowledgeCreate = "/vector-knowledge/create",
	VectorKnowledgeDetail = "/vector-knowledge/detail",
	AgentList = "/flow/agent/list",
	Explore = "/explore",
	FlowKnowledgeDetail = "/flow/knowledge/detail/:id",
	FlowDetail = "/flow/:type/detail/:id",
	Calendar = "/calendar",
	/** Super Delightful */
	BeDelightful = "/be-delightful/workspace",
	BeDelightfulWorkspace = "/be-delightful/workspace",
	BeDelightfulShare = "/share/:shareId",
}
