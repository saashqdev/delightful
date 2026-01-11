import { useMemoizedFn } from "ahooks"
import { useState } from "react"
import { AuthApi } from "@/apis"
import { unionBy } from "lodash-es"
import { OperationTypes, TargetTypes, type AuthMember } from "../../types"
import type { AuthExtraData, DepartmentExtraData, ExtraData } from "../types"
import { ManagerModalType } from "../types"

type AuthListProps = {
	extraConfig: ExtraData
	type: ManagerModalType
}

export default function useAuthList({ extraConfig, type }: AuthListProps) {
	// Current authorized list
	const [authList, setAuthList] = useState([] as AuthMember[])
	// Original authorized list (used for comparison)
	const [originalAuthList, setOriginalAuthList] = useState<AuthMember[]>(authList || [])

	// Add authorized member
	const addAuthMembers = useMemoizedFn((members: AuthMember[]) => {
		// Take union to avoid duplicate additions
		setAuthList((prevAuthList) => unionBy(prevAuthList, members, "target_id"))
	})

	// Delete authorized member
	const deleteAuthMembers = useMemoizedFn((members: AuthMember[]) => {
		const delMemberIds = members
			.filter((member) => member.operation !== OperationTypes.Owner)
			.map((member) => member.target_id)
		setAuthList(authList.filter((auth) => !delMemberIds.includes(auth.target_id)))
	})

	// Update authorized member permission information
	const updateAuthMember = useMemoizedFn((member: AuthMember) => {
		setAuthList((prevAuthList) => {
			const newAuthList = prevAuthList.map((auth) => {
				if (auth.target_id !== member.target_id) return auth
				return member
			})
			return newAuthList
		})
	})

	const initAuthList = useMemoizedFn(async () => {
		if (type === ManagerModalType.Auth) {
			const { resourceId, resourceType } = extraConfig as AuthExtraData
			if (!resourceId || !resourceType) return
			const authResult = await AuthApi.getResourceAccess(resourceType, resourceId)
			if (authResult.targets) {
				setAuthList(authResult.targets)
				setOriginalAuthList(authResult.targets)
			}
			return
		}
		if (type === ManagerModalType.Department) {
			const { value } = extraConfig as DepartmentExtraData
			const newAuthList = value.map((item) => ({
				target_type: TargetTypes.Department,
				target_id: item.id,
				operation: OperationTypes.Read,
				target_info: {
					id: item.id,
					name: item.name,
					description: "",
					icon: "",
				},
			}))
			setAuthList(newAuthList)
			setOriginalAuthList(newAuthList)
		}
	})

	return {
		authList,
		addAuthMembers,
		deleteAuthMembers,
		updateAuthMember,
		initAuthList,
		setAuthList,
		originalAuthList,
		setOriginalAuthList,
	}
}

