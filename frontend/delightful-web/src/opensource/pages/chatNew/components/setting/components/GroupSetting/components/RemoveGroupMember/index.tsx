// React related
import { useState } from "react"
import { observer } from "mobx-react-lite"

// Type imports
import type { DrawerProps } from "antd"

// UI component library
import { Checkbox, Drawer, Flex } from "antd"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import DelightfulAvatar from "@/opensource/components/base/DelightfulAvatar"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import AutoTooltipText from "@/opensource/components/other/AutoTooltipText"

// Icons
import { IconChevronLeft } from "@tabler/icons-react"

// toolfunction/Hooks
import { useTranslation } from "react-i18next"
import { useMemoizedFn } from "ahooks"
import useGroupInfo from "@/opensource/hooks/chat/useGroupInfo"
import { getUserJobTitle, getUserName } from "@/utils/modules/chat"

// Styles
import { useStyles } from "./style"

// Services and stores
import groupInfoStore from "@/opensource/stores/groupInfo"
import userInfoStore from "@/opensource/stores/userInfo"
import { ChatApi } from "@/apis"
import groupInfoService from "@/opensource/services/groupInfo"

interface ChatSettingProps extends DrawerProps {
	onClose?: () => void
	groupId: string
}

const RemoveGroupMember = observer(
	({ open, groupId, onClose: onCloseInProps }: ChatSettingProps) => {
		const { styles } = useStyles()
		const { t } = useTranslation("interface")

		const onClose = useMemoizedFn(() => {
			onCloseInProps?.()
		})

		const { groupInfo } = useGroupInfo(groupId)
		console.log("groupInfo =======> ", groupInfo)

		const members = groupInfoStore.currentGroupMembers

		const [selectedMembers, setSelectedMembers] = useState<string[]>([])

		const onSelectMember = useMemoizedFn((userId: string) => {
			setSelectedMembers((prev) => {
				if (prev.includes(userId)) {
					return prev.filter((id) => id !== userId)
				}
				return [...prev, userId]
			})
		})

		const onRemoveMembers = useMemoizedFn((userIds: string[]) => {
			if (!userIds.length) return
			ChatApi.kickGroupUsers({
				group_id: groupId,
				user_ids: userIds,
			}).then(() => {
				setSelectedMembers([])
				groupInfoStore.removeGroupMembers(userIds)
				groupInfoService.fetchGroupMembers(groupId)
			})
		})

		if (!groupId) return null

		return (
			<Drawer
				open={open}
				closable
				onClose={onClose}
				title={
					<Flex align="center" gap={4}>
						<DelightfulIcon component={IconChevronLeft} size={24} onClick={onClose} />
						{t("chat.groupSetting.removeMember")}
					</Flex>
				}
				width={380}
				push={false}
				classNames={{
					header: styles.header,
					mask: styles.mask,
					body: styles.body,
				}}
			>
				<Flex vertical className={styles.memberList} flex={1}>
					{members.map((user) => {
						const userInfo = userInfoStore.get(user.user_id)

						return (
							<Flex
								key={user.user_id}
								align="center"
								justify="space-between"
								gap={8}
								className={styles.memberItem}
							>
								<Checkbox
									disabled={userInfo?.user_id === groupInfo?.group_owner}
									checked={selectedMembers.includes(user.user_id)}
									onChange={() => onSelectMember(user.user_id)}
								/>
								<DelightfulAvatar size={28} src={userInfo?.avatar_url}>
									{getUserName(userInfo)}
								</DelightfulAvatar>
								<Flex vertical flex={1}>
									<AutoTooltipText>{getUserName(userInfo)}</AutoTooltipText>
									<AutoTooltipText className={styles.jobTitle}>
										{getUserJobTitle(userInfo)}
									</AutoTooltipText>
								</Flex>
								{userInfo?.user_id !== groupInfo?.group_owner && (
									<DelightfulButton
										type="default"
										danger
										className={styles.removeButton}
										onClick={() => onRemoveMembers([user.user_id])}
									>
										{t("chat.groupSetting.remove")}
									</DelightfulButton>
								)}
							</Flex>
						)
					})}
				</Flex>
				{selectedMembers.length > 0 && (
					<DelightfulButton
						className={styles.removeCheckedButton}
						size="large"
						block
						type="primary"
						onClick={() => onRemoveMembers(selectedMembers)}
					>
						{t("chat.groupSetting.remove")} ({selectedMembers.length})
					</DelightfulButton>
				)}
			</Drawer>
		)
	},
)

export default RemoveGroupMember
