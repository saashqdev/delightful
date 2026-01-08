import DelightfulInfiniteScrollList from "@/opensource/components/DelightfulInfiniteScrollList"
import { contactStore } from "@/opensource/stores/contact"
import type { GroupConversationDetail } from "@/types/chat/conversation"
import { useMemoizedFn } from "ahooks"
import { createStyles } from "antd-style"
import { useCallback, useState, useEffect } from "react"
import DelightfulScrollBar from "@/opensource/components/base/DelightfulScrollBar"
import { useChatWithMember } from "@/opensource/hooks/chat/useChatWithMember"
import { MessageReceiveType } from "@/types/chat"
import { observer } from "mobx-react-lite"

const useStyles = createStyles(({ css, token }) => {
	return {
		empty: css`
			padding: 20px;
			width: 100%;
			height: calc(100vh - ${token.titleBarHeight}px);
			overflow-y: auto;
		`,
	}
})

const MyGroups = observer(function MyGroups() {
	const { styles } = useStyles()

	// Use state to manage data and loading status
	const [data, setData] = useState<any>(undefined)
	const [isLoading, setIsLoading] = useState(false)

	// Initial load and refresh function
	const fetchData = useCallback(async (params = {}) => {
		setIsLoading(true)
		try {
			const result = await contactStore.getUserGroups(params)
			setData(result)
			return result
		} finally {
			setIsLoading(false)
		}
	}, [])

	// Initial load
	useEffect(() => {
		fetchData()
	}, [fetchData])

	const chatWith = useChatWithMember()
	const itemsTransform = useCallback(
		(item: GroupConversationDetail & { conversation_id: string }) => ({
			id: item.id,
			title: item.group_name,
			avatar: {
				src: item.group_avatar,
				children: item.group_name,
			},
			group: item,
		}),
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[isLoading],
	)

	const handleItemClick = useMemoizedFn(({ group }: ReturnType<typeof itemsTransform>) => {
		chatWith(group.id, MessageReceiveType.Group, true)
	})

	return (
		<DelightfulScrollBar className={styles.empty}>
			<DelightfulInfiniteScrollList<
				GroupConversationDetail & { conversation_id: string },
				ReturnType<typeof itemsTransform>
			>
				data={data}
				trigger={fetchData}
				itemsTransform={itemsTransform}
				onItemClick={handleItemClick}
			/>
		</DelightfulScrollBar>
	)
})

export default MyGroups
