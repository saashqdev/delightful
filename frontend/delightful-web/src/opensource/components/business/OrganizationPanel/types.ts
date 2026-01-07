import type { StructureItem, StructureUserItem } from "@/types/organization"
import type { FormHTMLAttributes, ReactNode } from "react"
import type {
	DepartmentSelectItem,
	OrganizationSelectItem,
	UserSelectItem,
} from "../MemberDepartmentSelectPanel/types"

export type OrganizationPanelSelectItem = DepartmentSelectItem | UserSelectItem

export type CheckboxOptions = {
	checked?: Array<OrganizationSelectItem>
	onChange?: (checked: Array<OrganizationSelectItem>) => void
	disabled?: Array<OrganizationSelectItem>
}

export type OrganizationPanelProps = FormHTMLAttributes<HTMLDivElement> & {
	defaultSelectedPath?: { id: string; name: string }[]
	selectedPath?: { id: string; name: string }[]
	onChangeSelectedPath?: (path: { id: string; name: string }[]) => void
	/** Handle click on child item */
	onItemClick?: (node: OrganizationSelectItem, toNextDepartmentLevel: () => void) => void
	/** Item arrow area - custom render */
	itemArrow?: boolean | ((item: StructureItem) => ReactNode)
	/** Footer area */
	footer?: ReactNode
	/** Search bar right side */
	topRight?: ReactNode
	/** Whether to show members */
	showMember?: boolean
	/** Member node extension area */
	memberExtra?: (node: StructureUserItem) => ReactNode
	/** Breadcrumb right side node */
	breadcrumbRightNode?: ReactNode
	checkboxOptions?: CheckboxOptions
	/** Member node wrapper */
	memberNodeWrapper?: (node: ReactNode, member: StructureUserItem) => ReactNode
}
