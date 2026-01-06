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

	// 正式员工
	Formal = 1,

	// 实习生
	Intern = 2,

	// 外包
	Outsourcing = 3,

	// 劳务派遣
	LaborDispatch = 4,

	// 顾问
	Consultant = 5,
}

export interface StructureUserItem {
	/** delightful 生态下的用户 ID */
	user_id: string
	/** 钉钉 ID */
	delightful_id: string
	/** 组织编码 */
	organization_code: string
	/** 用户类型 */
	user_type: UserType
	/** 描述 */
	description: string
	/** 点赞数 */
	like_num: number
	/** 标签 */
	label: string
	/** 状态 */
	status: 1 | 0
	/** 昵称 */
	nickname: string
	/** 头像 */
	avatar_url: string
	/** 国家编码 */
	country_code: string
	/** 电话 */
	phone: string
	/** 邮箱 */
	email: string
	/** 真实姓名 */
	real_name: string
	/** 员工类型 */
	employee_type: StructureUserType
	/** 员工编号 */
	employee_no: string
	/** 职位 */
	job_title: string
	/** 是否是领导 */
	is_leader: boolean
	/** 路径节点 */
	path_nodes: PathNode[]
	/**
	 * @deprecated 机器人信息
	 * 请使用 agent_info 代替
	 */
	bot_info?: {
		bot_id: string
		flow_code: string
		user_operation: OperationTypes
	}
	/** 机器人信息 */
	agent_info?: {
		bot_id: string
		flow_code: string
		user_operation: OperationTypes
	}
	/** 个人说明书url */
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
