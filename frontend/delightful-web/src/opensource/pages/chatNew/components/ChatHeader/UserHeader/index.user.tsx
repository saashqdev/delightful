import { Flex } from "antd"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import type { HTMLAttributes } from "react"
import { useMemo, useState } from "react"
import { contactStore } from "@/opensource/stores/contact"
import type Conversation from "@/opensource/models/chat/conversation"
import { getUserDepartmentFirstPath, getUserJobTitle, getUserName } from "@/utils/modules/chat"
import { cx } from "antd-style"
import conversationStore from "@/opensource/stores/chatNew/conversation"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import useUserInfo from "@/opensource/hooks/chat/useUserInfo"
import { useTranslation } from "react-i18next"
import { useDeepCompareEffect, useMemoizedFn } from "ahooks"
import { IconDots } from "@tabler/icons-react"
import { ExtraSectionKey } from "@/opensource/pages/chatNew/types"
import useStyles from "../styles"
import CurrentTopic from "../CurrentTopic"
import DelightfulAvatar from "@/opensource/components/base/DelightfulAvatar"
import { observer } from "mobx-react-lite"

interface HeaderProps extends HTMLAttributes<HTMLDivElement> {
	conversation: Conversation
}

function HeaderRaw({ conversation, className }: HeaderProps) {
	const { styles } = useStyles()
	const { t } = useTranslation("interface")

	// const imStyle = useAppearanceStore((state) => state.imStyle)
	const { userInfo: conversationUser } = useUserInfo(conversation.receive_id)

	const { settingOpen } = conversationStore

	const departmentPath = useMemo(
		() => getUserDepartmentFirstPath(conversationUser)?.split("/") || [],
		[conversationUser],
	)

	// Use useState and useEffect to replace useContactStore
	const [departments, setDepartments] = useState<any[]>([])

	useDeepCompareEffect(() => {
		if (departmentPath.length > 0) {
			contactStore.getDepartmentInfos(departmentPath, 1, true).then((result) => {
				setDepartments(result)
			})
		}
	}, [departmentPath])

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
					<DelightfulAvatar src={conversationUser?.avatar_url} size={40}>
						{getUserName(conversationUser)}
					</DelightfulAvatar>
					<Flex vertical flex={1}>
						<span className={styles.headerTitle}>{getUserName(conversationUser)}</span>
						<span className={styles.headerTopic}>
							{departments?.map((d) => d?.name).join("/")}
							{getUserJobTitle(conversationUser) &&
								` | ${getUserJobTitle(conversationUser)}`}
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
}

const UserHeader = observer(HeaderRaw)

export default UserHeader
