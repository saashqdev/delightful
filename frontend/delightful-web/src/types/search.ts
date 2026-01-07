/** Global search namespace */
export namespace GlobalSearch {
	export interface SearchParams {
		type: number
		key_word?: string
		/** ES search engine pagination token */
		page_token?: string
		page_size?: number
		extra?: Record<string, any>
	}

	/** Contact type */
	export interface ContactItem {
		/** User ID */
		user_id: string
		delightful_id: string
		organization_code: string
		user_type: number
		description: string
		label: string
		nickname: string
		avatar_url: string
		country_code: string
		phone: string
		real_name: string
		ai_code: string
		user_manual: string
		like_num: number
		status: number
		account_type: number
		/** Job title */
		job_title: string
		path_nodes: Array<{
			department_id: string
			department_name: string
			parent_department_id: string
			path: string
			visible: boolean
		}>
	}

	/** AI assistant */
	export interface AssistantItem {
		id: string
		robot_name: string
		robot_avatar: string
		robot_description: string
		flow_code: string
		flow_version: string
		user_id: string
	}

	/** Group user */
	interface GroupUser {
		user_id: string
		delightful_id: string
		organization_code: string
		user_type: number
		description: string
		like_num: number
		label: string
		status: number
		nickname: string
		avatar_url: string
		country_code: string
		real_name: string
		account_type: number
		ai_code: string
		user_manual: string
	}

	/** Group */
	export interface GroupItem {
		id: string
		group_owner: string
		group_name: string
		group_avatar: string
		group_notice: string
		group_tag: string
		group_type: number
		group_status: number
		organization_code: string
		member_limit: number
		users: Array<GroupUser>
	}

	/** Chat */
	export interface ChatItem {
		name: string
	}

	/** Application */
	export interface ApplicationItem {
		id: string
		name: string
		/** Application code */
		code: string
		/** Application description */
		description?: string
		logo: string
	}

	/** Cloud document operator (including creator and modifier) */
	interface CloudDriveUser {
		id: string
		real_name: string
		avatar: string
		description: string
		position: string
		department: null | string
	}

	/** Document type */
	export const enum CloudDriveFileType {
		ALL = -1,

		/** Folder */
		FOLDER = 0,

		/** Multi-dimensional table */
		MULTI_TABLE = 1,
		WORD = 2,
		EXCEL = 3,

		/** Mind notes */
		MIND_NOTE = 4,

		/** PPT */
		PPT = 5,

		/** PDF  */
		PDF = 6,

		/** Cloud document (old version) */
		CLOUD_DOC = 7,

		/** Link */
		LINK = 8,

		/** Knowledge base */
		KNOWLEDGE_BASE = 9,

		/** Image */
		IMAGE = 10,

		/** Video */
		VIDEO = 11,

		/** Audio */
		AUDIO = 12,

		/** Compressed file */
		COMPRESS = 13,

		/** Other file type */
		UNKNOWN = 14,

		/** Markdown file */
		MARKDOWN = 15,

		/** Cloud document */
		CLOUD_DOCX = 16,

		/** html */
		HTML = 17,

		/** txt */
		TXT = 18,

		/** xmind */
		XMIND = 19,

		/** Keewood page */
		// PAGE = 20,

		/** Keewood application */
		// APPLICATION = 21,

		/** Whiteboard */
		WHITEBOARD = 22,

		/** CSV */
		// CSV = 23,

		/** Delightful application */
		// DELIGHTFUL_APPLICATION = 24
	}

	/** Cloud drive */
	export interface CloudDriveItem {
		is_template: number
		organization_code: string
		is_show_navigation_page: number
		properties: {
			url: string
			target_blank: number
		}
		id: string
		file_id: string
		name: string
		file_type: CloudDriveFileType
		highlights?: {
			title?: Array<string>
			content?: Array<string>
		}
		extension: string
		operation: string
		is_favorite: number
		is_quick_file: number
		is_subscribe: number
		path: Array<{
			id: string
			name: string
			type: string
			operation: string
			space_type: number
		}>
		creator: CloudDriveUser
		created_at: string
		modifier: CloudDriveUser
		modified_at: string
		recent_opened_at: string
		shared_at: string
		space_type: number
	}

	/** Schedule */
	export interface ScheduleItem {
		id: string
		series_id: string
		description: string
		title: string
		start_time: string
		location: Array<string>
		end_time: string
		organizer_id: string
		organization_code: string
		created_at: string
		updated_at: string
	}

	/** Approval */
	export interface ApprovalItem {
		id: string
		instance_no: string
		title: string
		status: number
		summary: []
		creator_info: {
			id: string
			avatar: string
			name: string
		}
		creator_name: string
		current_approval_users_info: Array<{
			id: string
			name: string
			avatar: string
		}>
		jump_link: {
			type: string
			url: string
		}
		result: number
		allow_approval: boolean
		approval_type: string
		finished_at: string
		created_at: string
		updated_at: string
		platform_tag: string
		business_id: string
	}

	/** Task */
	export interface TaskItem {
		id: string
		creator: {
			user_id: string
			delightful_id: string
			organization_code: string
			user_type: number
			description: string
			like_num: number
			label: string
			status: number
			nickname: string
			avatar_url: string
			user_manual: string
		}
		organization_code: string
		created_at: string
		updated_at: string
		published_at: string
		title: string
		parent_id: string
		group_id: string
		review_id: string
		review_status: number
		review_snapshot_id: string
		form_id: string
		form_status: number
		operation: number
		status: 1 | 2 | 3 | 4 | 5
		resource: number
		resource_id: string
		direct_url: string
		description: string
		priority: 1 | 2 | 3 | 4
		attachments: string
		deadline_time: string
		finished_at: string
		review_result: string
		anonymous: number
		assignees: Array<{
			id: string
			type: string
			name: string
		}>
	}

	/** Department */
	export interface DepartmentItem {
		id: string
		department_id: string
		parent_department_id: string
		name: string
		i18n_name: string
		order: string
		leader_user_id: string
		organization_code: string
		status: string
		path: string
		level: number
		created_at: string
		updated_at: string
		deleted_at: string
		document_id: string
		employee_sum: number
		has_child: boolean
	}

	/** Knowledge base */
	export interface KnowledgeItem {
		is_template: number
		cover_updated_at: string
		organization_code: string
		is_show_navigation_page: number
		properties: {
			url: string
			target_blank: number
		}
		id: string
		/** Knowledge base content for displaying highlighted content */
		highlights?: {
			title?: Array<string>
			content?: Array<string>
		}
		file_id: string
		name: string
		file_type: number
		extension: string
		operation: string
		is_favorite: number
		is_quick_file: number
		is_subscribe: number
		path: Array<{
			id: string
			name: string
			type: string
			operation: string
			space_type: number
		}>
		creator: {
			id: string
			real_name: string
			avatar: string
			description: string
			position: string
			department: string
		}
		created_at: string
		modifier: {
			id: string
			real_name: string
			avatar: string
			description: string
			position: string
			department: string
		}
		modified_at: string
		recent_opened_at: string
		shared_at: string
		attributes: {
			advanced_permission_status: number
			inherited_permission_status: number
		}
		space_type: number
	}
}
