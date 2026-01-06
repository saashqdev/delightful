import type {
	DepartmentSelectItem,
	UserSelectItem,
} from "@/opensource/components/business/MemberDepartmentSelectPanel/types"
import type { Operator } from "./other"

// All permission types
export const enum UserOperationType {
	all = "all",
	edit = "edit",
	read = "read",
	download = "download",
}

/**
 * Space type
 */
export const enum DriveSpaceType {
	All = -1,
	Me = 1,
	Shared = 2,
}

/**
 * File type
 */
export const enum DriveItemFileType {
	ALL = -1,
	FOLDER = 0, // Folder
	MULTI_TABLE = 1, // Multi-dimensional table
	WORD = 2,
	EXCEL = 3,
	MIND_NOTE = 4, // Mind notes
	PPT = 5,
	PDF = 6,
	CLOUD_DOC = 7, // Cloud document (legacy)
	LINK = 8, // Link
	KNOWLEDGE_BASE = 9, // Knowledge base
	IMAGE = 10, // Image
	VIDEO = 11, // Video
	AUDIO = 12, // Audio
	COMPRESS = 13,
	UNKNOWN = 14, // Other file types
	MARKDOWN = 15, // Markdown file
	CLOUD_DOCX = 16, // Cloud document
	HTML = 17, // HTML
	TXT = 18, // TXT
	XMIND = 19, // XMind
	PAGE = 20, // Keewood page
	APPLICATION = 21, // Keewood application
	WHITEBOARD = 22, // Whiteboard
}

interface DriveItemPath {
	id: string
	name: string
	type: "space"
	operation: UserOperationType
	space_type: DriveSpaceType
}

export interface DriveItem {
	is_template: 0
	cover_updated_at: null
	knowledge_base_id: null
	id: string
	file_id: string
	name: string
	file_type: DriveItemFileType
	extension: string
	operation: UserOperationType
	is_favorite: 0 | 1
	is_quick_file: 0 | 1
	is_subscribe: 0 | 1
	path: DriveItemPath[]
	creator: Operator
	created_at: string
	modifier: Operator
	modified_at: string
	recent_opened_at: null
	shared_at: string
	attributes: {
		advanced_permission_status: 0
		inherited_permission_status: 1
		open_share: 0
		share_type: 0
		enable_share_password: 0
		share_password: ""
	}
	space_type: DriveSpaceType
	organization_code: string
}

export interface UploadFileInfo {
	key: string
	name: string
	uid: string
	url: string
	fsize: number
}

export interface DrivePermissionBaseItem {
	id: string
	operation?: UserOperationType
	modify: 0 | 1
}

export const enum DrivePermissionItemType {
	Department = "department",
	User = "user",
}

export interface DrivePermissionDepartmentItem extends DrivePermissionBaseItem {
	type: DrivePermissionItemType.Department
	information: DepartmentSelectItem
}

export interface DrivePermissionUserItem extends DrivePermissionBaseItem {
	type: DrivePermissionItemType.User
	information: UserSelectItem
}

export interface DriveTemplateLibraryCategory {
	id: string
	name: string
}

export interface DriveTemplateLibraryFile {
	id: string
	name: string
	cover: FileIdentify
	used_time: string
	file_type: 16
	shared_at: string
	created_at: string
	shared_user: {
		id: string
		real_name: string
		avatar: string
		description: string
		position: string
		relation_number: string
		departments: []
		email: string
	}
}

export interface FileIdentify {
	uid: string
	name: string
	key: string
}

export interface FileData extends FileIdentify {
	url: string
}

/**
 * Trash item
 */
export interface DriveTrashItem {
	id: string
	file_type: DriveItemFileType
	creator: Operator
	name: string
	remain_day: number
	operation: UserOperationType
	parent_status: 1
	parent_name: string
}
