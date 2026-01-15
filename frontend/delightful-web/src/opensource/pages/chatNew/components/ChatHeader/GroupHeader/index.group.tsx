import DelightfulAvatar from "@/opensource/components/base/DelightfulAvatar"
import { Flex } from "antd"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import type { HTMLAttributes } from "react"
import type Conversation from "@/opensource/models/chat/conversation"
import { cx } from "antd-style"
import conversationStore from "@/opensource/stores/chatNew/conversation"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import useGroupInfo from "@/opensource/hooks/chat/useGroupInfo"
import { observer } from "mobx-react-lite"
import { useTranslation } from "react-i18next"
import { IconDots } from "@tabler/icons-react"
import { useMemoizedFn } from "ahooks"
import { ExtraSectionKey } from "@/opensource/pages/chatNew/types"
import useStyles from "../styles"
import CurrentTopic from "../CurrentTopic"
import { userStore } from "@/opensource/models/user"

interface HeaderProps extends HTMLAttributes<HTMLDivElement> {
	conversation: Conversation
}

const GroupHeader = observer(({ conversation, className }: HeaderProps) => {
	const { styles } = useStyles()
	const { t } = useTranslation("interface")

	// const imStyle = useAppearanceStore((state) => state.imStyle)

	const { groupInfo } = useGroupInfo(conversation.receive_id)

	const organization = userStore.user.getOrganizationByDelightful(
		groupInfo?.organization_code ?? "",
	)

	const { settingOpen } = conversationStore

	const onSettingClick = useMemoizedFn(() => {
		conversationStore.toggleSettingOpen()
	})

	return (
		<Flex vertical>
			<Flex
				gap={8}
				align="center"
				justify="space-between"
				className={cx(styles.header, className)}
			>
				<Flex gap={8} align="center" flex={1}>
					<DelightfulAvatar src={groupInfo?.group_avatar}>
						{groupInfo?.group_name}
					</DelightfulAvatar>
					<Flex vertical flex={1}>
						<span className={styles.headerTitle}>{groupInfo?.group_name}</span>
						<span className={styles.headerTopic}>
							{organization?.organization_name}
						</span>
					</Flex>
				</Flex>
				<Flex gap={2}>
					{/* <DelightfulButton
						key={ExtraSectionKey.Topic}
						className={cx({
							[styles.extraSectionButtonActive]: topicOpen,
						})}
						tip={t("chat.topic.topic")}
						type="text"
						icon={
							<DelightfulIcon
								size={20}
								color="currentColor"
								component={IconMessageTopic}
							/>
						}
						onClick={onTopicClick}
					/> */}
					<DelightfulButton
						key={ExtraSectionKey.Setting}
						className={cx({
							[styles.extraSectionButtonActive]: settingOpen,
						})}
						tip={t("chat.setting")}
						type="text"
						icon={
							<DelightfulIcon size={20} color="currentColor" component={IconDots} />
						}
						onClick={onSettingClick}
					/>
				</Flex>
			</Flex>
			<CurrentTopic />
		</Flex>
	)
})

export default GroupHeader
