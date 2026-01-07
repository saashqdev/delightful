import type { WithIdAndDataType, StructureUserItem } from "@/types/organization"
import { StructureItemType } from "@/types/organization"
import type {
	DepartmentSelectItem,
	GroupSelectItem,
	OrganizationSelectItem,
} from "../MemberDepartmentSelectPanel/types"

/**
 * Check if the node is a department
 * @param node Node data
 * @returns Whether it is a department
 */
export function isDepartment(node: OrganizationSelectItem): node is DepartmentSelectItem {
	return node.dataType === StructureItemType.Department
}

/**
 * Check if the node is a member
 * @param node Node data
 * @returns Whether it is a member
 */
export function isMember(
	node: OrganizationSelectItem,
): node is WithIdAndDataType<StructureUserItem, StructureItemType.User> {
	return node.dataType === StructureItemType.User
}

export function isGroup(node: OrganizationSelectItem): node is GroupSelectItem {
	return node.dataType === StructureItemType.Group
}

export function isPartner(
	node: OrganizationSelectItem,
): node is WithIdAndDataType<{}, StructureItemType.Partner> {
	return node.dataType === StructureItemType.Partner
}

/**
 * Flatten organization structure data
 * @param data Organization structure data
 * @param map Map to store flattened data
 * @returns Flattened organization structure data
 */
// export function flattenTree(
// 	data:
// 		| WithIdAndType<StructureItem, StructureItemType.Department>
// 		| WithIdAndType<StructureUserItem, StructureItemType.User>
// 		| (
// 				| WithIdAndType<StructureItem, StructureItemType.Department>
// 				| WithIdAndType<StructureUserItem, StructureItemType.User>
// 		  )[]
// 		| undefined,
// 	map?: Map<string, StructureItem | StructureUserItem>,
// ) {
// 	const result: Map<string, StructureItem | StructureUserItem> = map ?? new Map()

// 	if (!data) return result

// 	const arraify = Array.isArray(data) ? data : [data]

// 	arraify.forEach((item) => {
// 		if (isMember(item)) {
// 			result.set((item as StructureUserItem).user_id, item)
// 		} else {
// 			const department = item as StructureItem
// 			result.set(department.department_id, item)
// 			const list = [...department.children]
// 			if (list.length > 0) {
// 				flattenTree(list, result)
// 			}
// 		}
// 	})
// 	return result
// }

/**
 * Get total number of department members
 * @param data Department member tree
 * @returns Total number of department members
 */
// export function getDepartmentMemberCount(data: (StructureItem | StructureUserItem)[]) {
// 	let count = 0
// 	data.forEach((item) => {
// 		if (isMember(item)) {
// 			count += 1
// 		} else {
// 			count += getDepartmentMemberCount((item as StructureItem).children || [])
// 		}
// 	})
// 	return count
// }

/**
 * Get current rendered list data
 * @param selected List of currently selected node IDs
 * @param data Organization structure data
 * @returns Current rendered list data
 */
// export function getCurrentList(
// 	selected: string[],
// 	data:
// 		| WithIdAndDataType<StructureItem, StructureItemType.Department>
// 		| WithIdAndDataType<StructureUserItem, StructureItemType.User>
// 		| (
// 				| WithIdAndDataType<StructureItem, StructureItemType.Department>
// 				| WithIdAndDataType<StructureUserItem, StructureItemType.User>
// 		  )[],
// 	showMember: boolean,
// ) {
// 	if (!data) return []

// 	let arrify = Array.isArray(data) ? data : [data]

// 	if (!showMember) {
// 		arrify = arrify.filter(isDepartment)
// 	}

// 	if (selected.length === 0 || arrify.length === 0) {
// 		return arrify
// 	}
// 	const current = arrify.find((item) => isDepartment(item) && item.id === selected[0]) as
// 		| StructureItem
// 		| undefined

// 	if (current) {
// 		return getCurrentList(selected.slice(1), [...(current ? current.children : [])], showMember)
// 	}
// 	return arrify
// }
