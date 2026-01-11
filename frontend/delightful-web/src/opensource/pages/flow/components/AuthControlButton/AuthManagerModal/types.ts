import type { DepartmentSelectItem } from "@/opensource/components/business/MemberDepartmentSelectPanel/types"
import type { ResourceTypes } from "../types"

export enum ManagerModalType {
	// Permission management
	Auth = "auth",
	// Department selection
	Department = "department",
}

export type AuthExtraData = {
	resourceId: string
	resourceType: ResourceTypes
}

export type DepartmentExtraData = {
	onOk: (value: Pick<DepartmentSelectItem, "id" | "name">[]) => void
	value: Pick<DepartmentSelectItem, "id" | "name">[]
}

export type ExtraData = AuthExtraData | DepartmentExtraData

export interface WithExtraConfigProps<T extends ExtraData> {
	title: string
	open: boolean
	type: ManagerModalType
	extraConfig: T
	closeModal: () => void
}





