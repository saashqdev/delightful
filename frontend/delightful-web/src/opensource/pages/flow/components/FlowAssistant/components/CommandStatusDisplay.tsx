import React from "react"
import { Spin } from "antd"
import { IconCheck, IconX } from "@tabler/icons-react"
import { useStyles } from "../styles"
import DelightfulMarkdown from "@/opensource/pages/chatNew/components/ChatMessageList/components/MessageFactory/components/Markdown/EnhanceMarkdown"

export interface CommandStatus {
	type: string
	status: "pending" | "executing" | "completed" | "failed"
	message?: string
}

interface CommandStatusDisplayProps {
	commandStatus: CommandStatus[]
	content?: string
}

/**
 * command status display component
 * display command execution status and related information
 */
const CommandStatusDisplay: React.FC<CommandStatusDisplayProps> = ({ commandStatus, content }) => {
	const { styles, cx } = useStyles()

	return (
		<div className={styles.messageContentWrapper}>
			{content ? <DelightfulMarkdown content={content} /> : null}
			<div className={styles.commandStatusContainer}>
				{commandStatus.map((cmd) => {
					let statusClassName = ""
					if (cmd.status === "executing") {
						statusClassName = styles.executing
					} else if (cmd.status === "completed") {
						statusClassName = styles.completed
					} else if (cmd.status === "failed") {
						statusClassName = styles.failed
					}

					return (
						<div
							key={`${cmd.type}-${cmd.status}-${cmd.message?.substring(0, 10) || ""}`}
							className={cx(styles.commandStatusItem, statusClassName)}
						>
							{cmd.status === "executing" && (
								<Spin size="small" style={{ marginRight: 8 }} />
							)}
							{cmd.status === "completed" && (
								<IconCheck size={16} style={{ marginRight: 8 }} />
							)}
							{cmd.status === "failed" && (
								<IconX size={16} style={{ marginRight: 8 }} />
							)}
							<span>{cmd.message}</span>
						</div>
					)
				})}
			</div>
		</div>
	)
}

export default CommandStatusDisplay
