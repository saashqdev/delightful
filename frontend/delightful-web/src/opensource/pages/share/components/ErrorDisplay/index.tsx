import ShareIcon from "@/opensource/pages/share/assets/icon/replay_icon.svg"
import { Button } from "antd"
import { useStyles } from "./style"

interface ErrorDisplayProps {
	errorMessage?: string
	onRetry?: () => void
}

export default function ErrorDisplay({
	errorMessage = "Loading failed, please try again later",
	onRetry,
}: ErrorDisplayProps) {
	const { styles } = useStyles()

	return (
		<div className={styles.container}>
			<div className={styles.content}>
				<div className={styles.icon}>
					<img src={ShareIcon} alt="" />
				</div>
				<div className={styles.message}>{errorMessage}</div>
				<span className={styles.description}>
					Sorry, you don't have permission to view this playback or it has been deleted
				</span>
				{onRetry && (
					<Button
						type="primary"
						onClick={() => {
							window.location.href = "https://www.bedelightful.ai"
						}}
						className={styles.button}
					>
						Return to Home
					</Button>
				)}
			</div>
		</div>
	)
}
