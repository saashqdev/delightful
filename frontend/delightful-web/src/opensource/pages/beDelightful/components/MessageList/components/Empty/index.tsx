import { useStyles } from "./style"

export default function Empty() {
	const { styles } = useStyles()
	return (
		<div className={styles.emptyContainer}>
			<div className={styles.emptyIcon}>👋🏻</div>
			<div className={styles.emptyTitle}>Hello, I’m Be Delightful</div>
			<div className={styles.emptyText}>What can I do for you?</div>
		</div>
	)
}
