import type { OperationTypes } from "@/opensource/pages/flow/components/AuthControlButton/types"
import type { UserType } from "./user"
import type { GroupConversationType } from "./chat/conversation"

export const enum StructureItemType {
	Department = "department",
	User = "user",
	Group = "group",
	Partner = "partner",
}

export const enum PlatformType {
	DingTalk = "DingTalk",
}

// Department member sum type
export const enum SumType {
	// Calculate direct department member count
	CountDirectDepartment = 1,
	// Calculate total user count of all sub-departments
	CountAll = 2,
}

export type WithIdAndDataType<D, T extends StructureItemType> = D & {
	id: string
	dataType: T
}

export interface StructureItem {
	/** Department ID */
	department_id: string
	/** Parent department ID */
	parent_department_id: string
	/** Name */
	name: string
	/** Internationalized name */
	i18n_name: string
	/** Sort order */
	order: string
	/** Leader user ID */
	leader_user_id: string
	/** Organization code */
	organization_code: string
	/** Status */
	status: string
	/** Path */
	path: string
	/** Level */
	level: number
	/** Creation time */
	created_at: string
	/** Document ID */
	document_id: string
	/** Total employee count */
	employee_sum: number
	/** Whether has child departments */
	has_child: boolean
}

/**
 * Department information in cache
 *
 */
export interface StructureItemOnCache extends StructureItem {
	/** Value when sum_type is 2 */
	employee_sum_deep: number
}

export interface PathNode {
	department_name: string
	department_id: string
	parent_department_id: string
	path: string
	visible: boolean
}

export const enum StructureUserType {
	// Unknown (e.g., personal edition user)
	Unknown = 0,

	// Full-time employee
	Formal = 1,

	// Intern
	Intern = 2,

	// Outsourcing
	Outsourcing = 3,

	// Labor dispatch
	LaborDispatch = 4,

	// Consultant
	Consultant = 5,
}

export interface StructureUserItem {
	/** User ID in delightful ecosystem */
	user_id: string
	/** DingTalk ID */
	delightful_id: string
	/** Organization code */
	organization_code: string
	/** User type */
	user_type: UserType
	/** Description */
	description: string
	/** Like count */
	like_num: number
	/** Label */
	label: string
	/** Status */
	status: 1 | 0
	/** Nickname */
	nickname: string
	/** Avatar */
	avatar_url: string
	/** Country code */
	country_code: string
	/** Phone */
	phone: string
	/** Email */
	email: string
	/** Real name */
	real_name: string
	/** Employee type */
	employee_type: StructureUserType
	/** Employee number */
	employee_no: string
	/** Job title */
	job_title: string
	/** Is leader */
	is_leader: boolean
	/** Path nodes */
	path_nodes: PathNode[]
	/**
	 * @deprecated Bot information
	 * Please use agent_info instead
	 */
	bot_info?: {
		bot_id: string
		flow_code: string
		user_operation: OperationTypes
	}
	/** Agent information */
	agent_info?: {
		bot_id: string
		flow_code: string
		user_operation: OperationTypes
	}
	/** Personal manual URL */
	user_manual: string
}

export interface GroupInfo {
	id: string
	group_name: string
	group_avatar: string
	group_notice: string
	group_owner: string
	organization_code: string
	group_tag: string
	group_type: GroupConversationType
	group_status: number
}

export type OrganizationData = (StructureItem | StructureUserItem | GroupInfo)[]
