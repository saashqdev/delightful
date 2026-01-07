import { memo } from "react"
import delightful from "@/opensource/pages/beDelightful/assets/svg/delightful.svg"
import CommonFooter from "../CommonFooter"
import { DetailType } from "../../types"
import { useStyles } from "./styles"

export default memo(function Empty() {
	const { styles } = useStyles()

	return (
		<div className={styles.emptyContainer}>
			<span className={styles.emptyHeader}>
				<span className={styles.title}>Be Delightful Client</span>
			</span>
			<div className={styles.emptyBody}>
				<div className={styles.emptyContent}>
					<img src={delightful} alt="Delightful" />
					<p>Be Delightful is ready to execute tasks at any time</p>
				</div>
			</div>
			<CommonFooter type={DetailType.Empty} tips="Delightful is waiting for commands" />
		</div>
	)
})
