import DelightfulInfiniteScrollList from "@/opensource/components/DelightfulInfiniteScrollList"
import type { DelightfulListItemData } from "@/opensource/components/DelightfulList/types"
import { contactStore } from "@/opensource/stores/contact"
import { MessageReceiveType } from "@/types/chat"
import type { Friend } from "@/types/contact"
import { useMemoizedFn } from "ahooks"
import { createStyles } from "antd-style"
import { useCallback, useEffect, useState } from "react"
import DelightfulScrollBar from "@/opensource/components/base/DelightfulScrollBar"
import { useChatWithMember } from "@/opensource/hooks/chat/useChatWithMember"
import userInfoStore from "@/opensource/stores/userInfo"
import userInfoService from "@/opensource/services/userInfo"
import AvatarStore from "@/opensource/stores/chatNew/avatar"
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

const AiAssistant = observer(function AiAssistant() {
	const { styles } = useStyles()

	// Use state to manage data
	const [data, setData] = useState<any>(undefined)

	// Method to fetch friends and user info
	const fetchFriends = useCallback(async (params = {}) => {
		const result = await contactStore.getFriends(params)
		setData(result)
		return result
	}, [])

	// Initial load
	useEffect(() => {
		fetchFriends()
	}, [fetchFriends])

	const { fetchUserInfos } = userInfoService
	const chatWith = useChatWithMember()

	useEffect(() => {
		if (data && data?.items?.length > 0) {
			const unUserInfos = data?.items?.filter(
				(item: Friend) => !userInfoStore.get(item.friend_id),
			)
			if (unUserInfos.length > 0)
				fetchUserInfos(
					unUserInfos.map((item: Friend) => item.friend_id),
					2,
				)
		}
	}, [data, fetchUserInfos])

	const itemsTransform = useCallback(
		(item: Friend) => {
			const user = userInfoStore.get(item.friend_id)
			if (!user)
				return {
					id: item.friend_id,
					title: item.friend_id,
					avatar: AvatarStore.getTextAvatar(item.friend_id),
				}
			return {
				id: user.user_id,
				title: user.real_name,
				avatar: {
					src: user.avatar_url,
					children: user.real_name,
				},
				user,
			}
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[],
	)

	const handleItemClick = useMemoizedFn((item: DelightfulListItemData) => {
		chatWith(item.id, MessageReceiveType.Ai, true)
	})

	return (
		<DelightfulScrollBar className={styles.empty}>
			<DelightfulInfiniteScrollList<Friend>
				data={data}
				trigger={fetchFriends}
				itemsTransform={itemsTransform}
				onItemClick={handleItemClick}
			/>
		</DelightfulScrollBar>
	)
})

export default AiAssistant
