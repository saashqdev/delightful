import DelightfulInfiniteScrollList from "@/opensource/components/DelightfulInfiniteScrollList"
import type { DelightfulListItemData } from "@/opensource/components/DelightfulList/types"
import { contactStore } from "@/opensource/stores/contact"
import type { GroupConversationDetailWithConversationId } from "@/types/chat/conversation"
import { StructureItemType } from "@/types/organization"
import { useControllableValue } from "ahooks"
import { useMemo, useState, useEffect, useCallback } from "react"
import type { OrganizationSelectItem } from "../MemberDepartmentSelectPanel/types"
import { observer } from "mobx-react-lite"

type GroupSelectItem = OrganizationSelectItem & DelightfulListItemData

const itemsTransform = (item: GroupConversationDetailWithConversationId): GroupSelectItem => ({
	...item,
	dataType: StructureItemType.Group,
	title: item.group_name,
	avatar: item.group_avatar || {
		children: item.group_name,
	},
})

interface GroupSelectPanelProps {
	value: OrganizationSelectItem[]
	onChange: (value: OrganizationSelectItem[]) => void
	className?: string
	style?: React.CSSProperties
}

/**
 * Group selection panel
 */
const GroupSelectPanel = observer((props: GroupSelectPanelProps) => {
	// Use state to manage data and loading status
	const [data, setData] = useState<any>(undefined)

	// Use useCallback to define data fetching method
	const fetchData = useCallback(async (params = {}) => {
		const result = await contactStore.getUserGroups(params)
		setData(result)
		return result
	}, [])

	// Initial load
	useEffect(() => {
		fetchData()
	}, [fetchData])

	const [value, setValue] = useControllableValue<GroupSelectItem[]>(props, {
		defaultValue: [],
	})

	const checkboxOptions = useMemo(
		() => ({
			checked: value,
			onChange: setValue,
			dataType: StructureItemType.Group,
		}),
		[setValue, value],
	)

	return (
		<DelightfulInfiniteScrollList<GroupConversationDetailWithConversationId, GroupSelectItem>
			data={data}
			trigger={fetchData}
			itemsTransform={itemsTransform}
			checkboxOptions={checkboxOptions}
			className={props.className}
			style={props.style}
			noDataFallback={<div />}
		/>
	)
})

export default GroupSelectPanel
