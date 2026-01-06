import { cx } from "antd-style"
import { useTranslation } from "react-i18next"
import { memo } from "react"
import { Flex, Tag } from "antd"
import {
	IconMessage,
	IconMessagePlus,
	IconThumbUp,
	IconUserHeart,
	IconUserPlus,
	IconX,
} from "@tabler/icons-react"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import DelightfulEmpty from "@/opensource/components/base/DelightfulEmpty"
import DelightfulAvatar from "@/opensource/components/base/DelightfulAvatar"
import defaultAgentAvatar from "@/assets/logos/agent-avatar.jpg"
import type { PromptCard as PromptCardType } from "../PromptCard/types"
import useStyles from "./style"

interface PropmtDescriptionProps {
	open?: boolean
	data?: PromptCardType
	onClose: () => void
	onAddFriend?: (
		data: PromptCardType,
		addAgent: boolean,
		navigateConversation: boolean,
	) => Promise<void>
}

const PropmtDescription = memo(
	({ open = false, data, onClose, onAddFriend }: PropmtDescriptionProps) => {
		const { t } = useTranslation("interface")
		const { styles } = useStyles({ open })

		if (!data)
			return (
				<Flex vertical className={styles.container} align="center" justify="space-between">
					<DelightfulEmpty />
				</Flex>
			)

		return (
			<Flex vertical className={styles.container} align="center" justify="space-between">
				<DelightfulButton
					icon={<DelightfulIcon component={IconX} />}
					type="text"
					className={styles.close}
					onClick={onClose}
				/>
				<Flex align="center" vertical gap={12} className={styles.top}>
					{data.robot_avatar ? (
						<DelightfulAvatar src={data.robot_avatar} size={50} style={{ borderRadius: 8 }}>
							{data.robot_name}
						</DelightfulAvatar>
					) : (
						<img src={defaultAgentAvatar} alt="" className={styles.defaultAvatar} />
					)}
					<span className={styles.title}>{data.robot_name}</span>
					<Flex wrap="wrap" align="center" justify="center" gap="10px 0">
						{data.created_info?.label &&
							data?.created_info.label?.map((flag) => (
								<Tag key={flag} className={styles.flag}>
									{flag}
								</Tag>
							))}
					</Flex>
					<span className={styles.description}>{data.robot_description}</span>
					<Flex align="center" justify="space-around" gap={4} className={styles.nums}>
						<Flex flex={1} align="center" vertical justify="center" gap={4}>
							<Flex align="center" className={styles.numLabel}>
								<DelightfulIcon component={IconThumbUp} size={14} color="currentColor" />
								{t("explore.descriptionPanel.good")}
							</Flex>
							<span className={styles.num}>{data.created_info?.like_num}</span>
						</Flex>
						<Flex flex={1} align="center" vertical justify="center" gap={4}>
							<Flex align="center" className={styles.numLabel}>
								<DelightfulIcon
									component={IconUserHeart}
									size={14}
									color="currentColor"
								/>
								{t("explore.descriptionPanel.friendNum")}
							</Flex>
							<span className={styles.num}>{data.created_info?.like_num}</span>
						</Flex>
					</Flex>
				</Flex>
				<Flex vertical className={styles.buttons} gap={8}>
					{!data.is_add ? (
						<>
							<DelightfulButton
								type="primary"
								className={cx(styles.button)}
								icon={
									<DelightfulIcon component={IconMessagePlus} size={18} color="#fff" />
								}
								onClick={() => onAddFriend?.(data, true, true)}
							>
								{t("explore.descriptionPanel.addFriendAndChat")}
							</DelightfulButton>
							<DelightfulButton
								type="text"
								className={cx(styles.button, styles.plainButton)}
								icon={
									<DelightfulIcon
										component={IconUserPlus}
										color="currentColor"
										size={18}
									/>
								}
								onClick={() => onAddFriend?.(data, true, false)}
							>
								{t("explore.descriptionPanel.onlyAddAgent")}
							</DelightfulButton>
						</>
					) : (
						<>
							<DelightfulButton
								type="text"
								disabled
								className={cx(styles.button, styles.disabledButton)}
								icon={
									<DelightfulIcon
										component={IconUserPlus}
										size={18}
										color="currentColor"
									/>
								}
							>
								{t("explore.descriptionPanel.alreadyAddAgent")}
							</DelightfulButton>
							<DelightfulButton
								type="text"
								className={cx(styles.button, styles.plainButton)}
								icon={<DelightfulIcon component={IconMessage} size={18} />}
								onClick={() => onAddFriend?.(data, false, true)}
							>
								{t("explore.descriptionPanel.initiateAsession")}
							</DelightfulButton>
						</>
					)}
				</Flex>
			</Flex>
		)
	},
)

export default PropmtDescription
