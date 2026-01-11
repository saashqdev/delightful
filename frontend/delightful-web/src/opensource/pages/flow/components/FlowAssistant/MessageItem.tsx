import type React from "react"
import { Avatar, Spin, Button, Space } from "antd"
import { ReloadOutlined } from "@ant-design/icons"
import { useStyles } from "./styles"
import AnimatedLoading, { loadingContainerStyle } from "./components/AnimatedLoading"
import type { CommandStatus } from "./components/CommandStatusDisplay"
import CommandStatusDisplay from "./components/CommandStatusDisplay"
import DelightfulMarkdown from "@/opensource/pages/chatNew/components/ChatMessageList/components/MessageFactory/components/Markdown/EnhanceMarkdown"
import { useCommandDetection } from "./hooks/useCommandDetection"
import USER_AVATAR_URL from "./static/user.png"
import ASSISTANT_AVATAR_URL from "./static/assistant.png"

export interface MessageProps {
	id: string
	role: "user" | "assistant"
	content: string
	status?: "loading" | "done" | "error"
	commandStatus?: CommandStatus[]
	isCollectingCommand?: boolean
	confirmOperation?: {
		type: string
		message: string
		data?: any
	}
	onConfirm?: (operationType: string, data?: any) => void
	onCancel?: (operationType: string) => void
	onRetry?: (messageId: string) => void
}

function MessageItem(props: MessageProps): React.ReactElement {
	const {
		id,
		role,
		content,
		status,
		commandStatus,
		isCollectingCommand,
		confirmOperation,
		onConfirm,
		onCancel,
		onRetry,
	} = props
	const { styles, cx } = useStyles()
	const { detectCommands, processCommandMarkers } = useCommandDetection()

	// render confirm action button
	const renderConfirmButtons = () => {
		if (!confirmOperation) return null

		return (
			<div className={styles.confirmButtonsContainer}>
				<Space>
					<Button
						type="primary"
						onClick={() => onConfirm?.(confirmOperation.type, confirmOperation.data)}
					>
						Confirm
					</Button>
					<Button onClick={() => onCancel?.(confirmOperation.type)}>Cancel</Button>
				</Space>
			</div>
		)
	}

	// render message content
	const renderContent = (): React.ReactNode => {
		// process command flag in content, ensure JSON is not displayed
		let displayContent = content || ""

		// detect command flag
		const { hasCommands, commandCount } = detectCommands(displayContent)
		if (hasCommands) {
			console.log("MessageItemdiscover command flag:", id, "number of commands:", commandCount)
		}

		// display custom loading animation at instruction collection stage
		if (isCollectingCommand) {
			// if no content, display normal Spin component
			if (!displayContent) {
				return (
					<div className={loadingContainerStyle}>
						<Spin size="small" />
					</div>
				)
			}

			// process command flag
			displayContent = processCommandMarkers(displayContent, true)

			// display custom animation when there is content
			return (
				<>
					<DelightfulMarkdown content={displayContent} />
					<AnimatedLoading />
				</>
			)
		}

		// 当不在收集命令状态时，process command flag
		if (hasCommands) {
			displayContent = processCommandMarkers(displayContent, false)
		}

		// only display normal loading state if message is empty
		if (!displayContent && status === "loading") {
			console.log(`Message ${id} rendering empty loading state`)
			return (
				<div className={loadingContainerStyle}>
					<Spin size="small" />
				</div>
			)
		}

		// error state processing - display error message and retry button
		if (status === "error") {
			return (
				<div className={styles.messageContentWrapper}>
					<DelightfulMarkdown content={displayContent} />
				</div>
			)
		}

		// 如果消息有内容但仍在加载中，不显示loading状态，只显示内容
		if (displayContent && status === "loading") {
			return (
				<div className={styles.messageContentWrapper}>
					<DelightfulMarkdown content={displayContent} />
					{renderConfirmButtons()}
				</div>
			)
		}

		// 如果有命令状态，使用CommandStatusDisplay组件显示
		if (commandStatus && commandStatus.length > 0) {
			return (
				<>
					<CommandStatusDisplay commandStatus={commandStatus} content={displayContent} />
					{renderConfirmButtons()}
				</>
			)
		}

		// 正常消息内容或空内容
		return (
			<div className={styles.messageContentWrapper}>
				{displayContent ? <DelightfulMarkdown content={displayContent} /> : null}
				{renderConfirmButtons()}
			</div>
		)
	}

	// 根据消息角色使用不同的布局和样式
	if (role === "user") {
		return (
			<div className={styles.userMessageItem}>
				<div className={styles.userMessageRow}>
					<div className={cx(styles.messageContent, styles.user)}>{renderContent()}</div>
					<Avatar src={USER_AVATAR_URL} className={styles.userAvatar} />
				</div>
			</div>
		)
	}

	// 助理消息
	return (
		<div className={styles.assistantMessageItem}>
			<div className={styles.assistantMessageRow}>
				<Avatar src={ASSISTANT_AVATAR_URL} className={styles.avatar} />
				<div
					className={cx(styles.messageContent, styles.assistant, {
						[styles.errorText]: status === "error",
					})}
				>
					{renderContent()}
				</div>
				{status === "error" && onRetry && (
					<Button
						type="text"
						danger
						icon={<ReloadOutlined />}
						className={styles.retryButton}
						onClick={() => onRetry?.(id)}
						title="重试"
					/>
				)}
			</div>
		</div>
	)
}

export default MessageItem






