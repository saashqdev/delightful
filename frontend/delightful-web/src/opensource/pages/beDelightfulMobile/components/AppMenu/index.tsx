import { IconEdit, IconFolder, IconShare, IconTrash, IconInfoCircle } from "@tabler/icons-react"
import { useStyles } from "./style"

export default function AppMenu() {
	const { styles, cx } = useStyles()

	const handleOpenAbout = () => {
		window.location.href = "delightful://delightful.app/openwith?name=gotoAbout"
	}

	return (
		<div className={styles.container}>
			<div className={styles.title}>About</div>
			<div>
				<div className={cx(styles.item)} onClick={handleOpenAbout} role="button">
					<IconInfoCircle className={styles.icon} /> <span>Super Maggie</span>
				</div>
				{/* <div className={styles.item}>
					<IconShare /> <span>Share Topic</span>
				</div>
				<div className={styles.item}>
					<IconEdit className={styles.icon} /> <span>Rename</span>
				</div>
				<div className={styles.item}>
					<IconTrash className={styles.icon} /> <span>Delete Topic</span>
				</div> */}
			</div>
		</div>
	)
}
