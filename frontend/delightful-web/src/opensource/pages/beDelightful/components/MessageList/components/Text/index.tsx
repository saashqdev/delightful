import DelightfulAva from "@/opensource/pages/beDelightful/assets/svg/delightfulAva.svg"
import { userStore } from "@/opensource/models/user"
import { IconChecks } from "@tabler/icons-react"
import MarkDown from "react-markdown"
import { useStyles } from "./style"
import { observer } from "mobx-react-lite"

interface TextProps {
	data: any
	isUser: boolean
	hideHeader?: boolean
	isFinished?: boolean
}

function Text(props: TextProps) {
	const { styles } = useStyles()
	const { userInfo } = userStore.user
	const { data, isUser, hideHeader = false, isFinished = false } = props
	if (!data?.content && !data?.text?.content) return null
	const formatTimestamp = (timestamp: string) => {
		const date = new Date(timestamp)
		const month = date.getMonth() + 1
		const day = date.getDate()
		const hours = date.getHours().toString().padStart(2, "0")
		const minutes = date.getMinutes().toString().padStart(2, "0")
		return `${month}/${day} ${hours}:${minutes}`
	}

	// Check if it's "finished" type
	// let isFinished = data?.status === "finished"
	// Determine text content style class
	let textContentClass = styles.assistantText
	if (isFinished) {
		textContentClass = styles.finishedText
	} else if (isUser) {
		textContentClass = styles.userText
	}

	return (
		<div
			className={`${styles.textContainer} ${
				isUser ? styles.userContainer : styles.assistantContainer
			}`}
		>
			{!hideHeader && (
				<div
					className={styles.textHeader}
					style={isUser ? { justifyContent: "flex-end" } : {}}
				>
					{isUser ? (
						<>
							<span className={styles.timestamp}>
								{formatTimestamp(data?.send_timestamp)}
							</span>
							<img src={userInfo?.avatar} alt="avatar" className={styles.avatar} />
						</>
					) : (
						<>
							<img src={DelightfulAva} alt="avatar" className={styles.avatar} />
							<span className={styles.timestamp}>
								{formatTimestamp(data?.send_timestamp)}
							</span>
						</>
					)}
				</div>
			)}
			<div className={`${styles.textContent} ${textContentClass}`}>
				{isFinished ? <IconChecks stroke={1.5} /> : null}
				{isUser ? (
					<div className={styles.githubMarkdown}>
						{data?.content || data?.text?.content}
					</div>
				) : (
					<MarkDown
						className={styles.githubMarkdown}
						components={{
							a: ({ node, children, ...linkProps }) => (
								<a {...linkProps} target="_blank" rel="noopener noreferrer">
									{children}
								</a>
							),
						}}
					>
						{data?.content || data?.text?.content}
					</MarkDown>
				)}
			</div>
		</div>
	)
}

export default observer(Text)
