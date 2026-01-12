import { memo, useMemo } from "react"
import { Flex } from "antd"
import { cx } from "antd-style"
import type {
	ConversationMessage,
	ConversationMessageSend,
} from "@/types/chat/conversation_message"
import { ConversationMessageType } from "@/types/chat/conversation_message"
import { calculateRelativeSize } from "@/utils/styles"
import { getAvatarUrl } from "@/utils/avatar"
import { useFontSize } from "@/opensource/providers/AppearanceProvider/hooks"
import MessageContent from "./components/MessageContent"
// import MessageStatus from "./components/MessageStatus"
import MessageSeenStatus from "../MessageSeenStatus"
import MessageSendStatus from "../MessageSendStatus"
import useStyles from "./style"
import DelightfulAvatar from "@/opensource/components/base/DelightfulAvatar"
import MemberCardStore from "@/opensource/stores/display/MemberCardStore"
import useInfoStore from "@/opensource/stores/userInfo"
import { useMemoizedFn } from "ahooks"
import { getUserName } from "@/utils/modules/chat"
import { observer } from "mobx-react-lite"
import RevokeTip from "../RevokeTip"

interface MessageItemProps {
	message_id: string
	sender_id: string
	name: string
	avatar: string
	is_self: boolean
	message: ConversationMessage | ConversationMessageSend["message"]
	status?: string
	unread_count?: number
	conversation?: any
	className?: string
	refer_message_id?: string
	revoked?: boolean
}

// Extract avatar to avoid repeated renders
const Avatar = observer(function Avatar({
	name,
	avatar,
	size,
	uid,
}: {
	name: string
	avatar: string
	size: number
	uid: string
}) {
	const { styles } = useStyles({ fontSize: 16, isMultipleCheckedMode: false })
	const userInfo = useInfoStore.get(uid)

	// Use useMemo to cache info object, avoid creating new object on every render
	const info = useMemo(() => {
		if (avatar) {
			return { name, avatar_url: getAvatarUrl(avatar) }
		}

		return { name: getUserName(userInfo), avatar_url: userInfo?.avatar_url }
	}, [avatar, name, userInfo])

	const handleAvatarClick = useMemoizedFn((e) => {
		if (e) {
			MemberCardStore.openCard(uid, { x: e.clientX, y: e.clientY })
		}
		e.stopPropagation()
		e.preventDefault()
	})

	return (
		<DelightfulAvatar
			className={styles.avatar}
			src={info.avatar_url}
			size={size}
			onClick={handleAvatarClick}
		>
			{name}
		</DelightfulAvatar>
	)
})

const MessageItem = memo(function MessageItem({
	message_id,
	name,
	avatar,
	is_self,
	message,
	unread_count,
	className,
	sender_id,
	refer_message_id,
	revoked = false,
}: MessageItemProps) {
	const { fontSize } = useFontSize()
	const isBlockMessage = message.type === ConversationMessageType.RecordingSummary
	const { styles } = useStyles({ fontSize: 16, isMultipleCheckedMode: false })

	// Use useMemo to cache style calculation
	const containerStyle = useMemo(
		() => ({
			marginTop: `${calculateRelativeSize(12, fontSize)}px`,
		}),
		[fontSize],
	)

	// Use useMemo to cache avatar size
	const avatarSize = useMemo(() => calculateRelativeSize(40, fontSize), [fontSize])

	// If message is revoked, show revoked tip
	if (revoked) {
		return <RevokeTip key={message_id} senderUid={sender_id} />
	}

	// Use useMemo to cache avatar component
	const avatarComponent = <Avatar name={name} avatar={avatar} size={avatarSize} uid={sender_id} />

	return (
		<div
			// id={message_id}
			className={cx(
				styles.flexContainer,
				styles.container,
				isBlockMessage && styles.blockContainer,
				className,
			)}
			style={{ ...containerStyle, justifyContent: is_self ? "flex-end" : "flex-start" }}
			data-message-id={message_id}
		>
			{/* Avatar - non-self message displayed on left */}
			{!is_self && avatarComponent}

			{/* Message content and status */}
			<Flex
				vertical
				gap={4}
				className={styles.contentWrapper}
				align={is_self ? "flex-end" : "flex-start"}
			>
				<MessageContent
					message_id={message_id}
					message={message}
					is_self={is_self}
					refer_message_id={refer_message_id}
					name={name}
				/>
				{is_self && (
					<>
						<MessageSeenStatus unreadCount={unread_count ?? 0} messageId={message_id} />
						<MessageSendStatus messageId={message_id} />
					</>
				)}
			</Flex>

			{/* Avatar - self message displayed on right */}
			{is_self && avatarComponent}
		</div>
	)
})

export default MessageItem
