import { IconLayoutGrid } from "@tabler/icons-react"
import { memo } from "react"
import { useStyles } from "./style"

export default memo(function SwitchRoute() {
	const { styles, cx } = useStyles()
	return (
		<div className={styles.container}>
			<div className={styles.title}>Navigation</div>
			<div>
				<div className={cx(styles.item, styles.itemActive)}>
					<IconLayoutGrid className={styles.icon} /> <span>Workspace</span>
				</div>
				{/* <div className={styles.item}>
                    <IconLayoutGrid /> <span>Archive</span>
                </div>
                <div className={styles.item}>
                    <IconLayoutGrid /> <span>Quick Access</span>
                </div> */}
			</div>
		</div>
	)
})
