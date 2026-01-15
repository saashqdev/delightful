// Organization structure selection related state management
import { useEffect, useMemo, useState } from "react"
import OrganizationPanel from "@/opensource/components/business/OrganizationPanel"
import type {
	OrganizationSelectItem,
	UserSelectItem,
} from "@/opensource/components/business/MemberDepartmentSelectPanel/types"
import { StructureItemType } from "@/types/organization"
import { useUserInfo } from "@/opensource/models/user/hooks"
import useStyles from "../../../../style"
import { defaultOperation } from "../../../../constants"
import { TargetTypes } from "../../../../types"
import { useAuthControl } from "../../../../AuthManagerModal/context/AuthControlContext"
import { getDisabledMemberIds } from "../../../../utils/authUtils"

export default function OrganizationSelectPanel() {
	const { styles } = useStyles()
	const { setAuthList, authList, originalAuthList } = useAuthControl()
	const { userInfo } = useUserInfo()
	const uId = userInfo?.user_id

	const [organizationChecked, setOrganizationChecked] = useState<OrganizationSelectItem[]>([])

	// Get current user permission
	const currentUserAuth = useMemo(() => {
		return authList.find((auth) => auth.target_id === uId)?.operation
	}, [authList, uId])

	useEffect(() => {
		// Extract selected departments and users from authList, and convert to corresponding OrganizationSelectItem type
		const selectedItems = authList
			.filter(
				(auth) =>
					auth.target_type === TargetTypes.Department ||
					auth.target_type === TargetTypes.User,
			)
			.map((auth) => {
				if (auth.target_type === TargetTypes.Department) {
					// convert to DepartmentSelectItem
					return {
						id: auth.target_id,
						name: auth.target_info?.name || "",
						dataType: StructureItemType.Department,
						operation: auth.operation,
					}
				}
				// convert to UserSelectItem
				return {
					id: auth.target_id,
					user_id: auth.target_id,
					nickname: auth.target_info?.name || "",
					real_name: auth.target_info?.name || "",
					avatar_url: auth.target_info?.icon || "",
					description: auth.target_info?.description || "",
					operation: auth.operation,
					dataType: StructureItemType.User,
				}
			})

		setOrganizationChecked(selectedItems as OrganizationSelectItem[])
	}, [authList])

	/**
	 * organization structure panel selection state
	 */
	const organizationPanelCheckboxOptions = useMemo(
		() => ({
			checked: organizationChecked,
			onChange: (value: OrganizationSelectItem[]) => {
				setOrganizationChecked(value)
				// @ts-ignore
				setAuthList((prevAuthList) => {
					// filter out existing departments and users
					const filteredAuthList = prevAuthList.filter((auth) => {
						return (
							auth.target_type !== TargetTypes.Department &&
							auth.target_type !== TargetTypes.User
						)
					})

					// add newly selected departments and users
					const newItems = value
						.map((item) => {
							if (item.dataType === StructureItemType.Department) {
								// Process info
								return {
									target_id: item.id,
									// @ts-ignore
									operation: item.operation || defaultOperation,
									target_type: TargetTypes.Department,
									target_info: {
										id: item.id,
										name: item.name,
										description: "",
										icon: "",
									},
								}
							}
							if (item.dataType === StructureItemType.User) {
								// Process info
								const userItem = item as UserSelectItem
								return {
									target_id: item.user_id,
									// @ts-ignore
									operation: item.operation || defaultOperation,
									target_type: TargetTypes.User,
									target_info: {
										id: item.user_id,
										name: item.nickname || item.real_name,
										description: userItem.description || "",
										icon: userItem.avatar_url || "",
										// can add user-specific information
									},
								}
							}
							// if there are other types, can process here
							return null
						})
						.filter(Boolean) //  info  null  info

					return [...filteredAuthList, ...newItems]
				})
			},
			disabled: getDisabledMemberIds(originalAuthList, uId!, currentUserAuth),
		}),
		[organizationChecked, originalAuthList, setAuthList, currentUserAuth, uId],
	)

	return (
		<OrganizationPanel
			className={styles.organizationList}
			checkboxOptions={{
				...organizationPanelCheckboxOptions,
				disabled: organizationPanelCheckboxOptions.disabled.map((item) => ({
					...item,
					dataType: StructureItemType.Partner,
				})),
			}}
		/>
	)
}
