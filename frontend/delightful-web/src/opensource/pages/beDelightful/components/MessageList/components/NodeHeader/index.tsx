import { userStore } from "@/opensource/models/user"
import DelightfulAva from "@/opensource/pages/beDelightful/assets/image/delightful_ava.jpg"
import UserDefault from "@/opensource/pages/beDelightful/assets/svg/user_default.svg"
// eslint-disable-next-line import/order
import { useStyles } from "./style"

interface NodeHeaderProps {
	isUser: boolean
	timestamp: string
	isShare?: boolean
}

const NodeHeader = ({ isUser, timestamp, isShare }: NodeHeaderProps) => {
	const { styles } = useStyles()

	// Format timestamp
	const formatTimestamp = (time: string) => {
		if (!time) return ""

		let date: Date

		// Check if it's an ISO format time string (contains '-', 'T', ':' etc.)
		const timeStr = String(time) // Ensure time is a string
		if (timeStr.includes("-") || timeStr.includes("T") || timeStr.includes(":")) {
			date = new Date(time)
		} else {
			// Convert timestamp to number
			let timeValue = Number(time)

			// Check if it's a seconds-level timestamp (10 digits), convert to milliseconds if so
			if (timeValue < 10000000000) {
				timeValue *= 1000
			}

			date = new Date(timeValue)
		}

		// Check if date is valid
		if (Number.isNaN(date.getTime())) {
			return ""
		}

		const month = date.getMonth() + 1
		const day = date.getDate()
		const hours = date.getHours().toString().padStart(2, "0")
		const minutes = date.getMinutes().toString().padStart(2, "0")
		return `${month}/${day} ${hours}:${minutes}`
	}
	return (
		<div className={isUser ? styles.userNodeHeader : styles.nodeHeader}>
			{isUser ? (
				<>
					<span className={styles.timestamp}>{formatTimestamp(timestamp)}</span>
					<img
						src={
							isShare
								? UserDefault || userStore?.user?.userInfo?.avatar || UserDefault
								: userStore?.user?.userInfo?.avatar || UserDefault
						}
						alt="avatar"
						className={styles.avatar}
					/>
				</>
			) : (
				<>
					<img src={DelightfulAva} alt="avatar" className={styles.avatar} />
					<span className={styles.timestamp}>{formatTimestamp(timestamp)}</span>
				</>
			)}
		</div>
	)
}

export default NodeHeader
