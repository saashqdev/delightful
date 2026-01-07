import { memo } from "react"
import delightfulBetaSVG from "@/opensource/pages/beDelightful/assets/svg/be_delightful_logo.svg"
import arrowDownSVG from "@/opensource/pages/beDelightfulMobile/assets/svg/arrow-down.svg"
import { cx } from "antd-style"
import { useStyles } from "./styles"
import type { MessagePanelProps } from "../MessagePanel"
// import MessagePanel from "../MessagePanel"
import MessagePanel from "@/opensource/pages/beDelightful/components/MessagePanel/MessagePanel"
import WorkspaceCase from "../WorkspaceCase"

interface WorkspaceWelcomeProps extends MessagePanelProps {
	setFileList?: (files: any) => void
	showLoading?: boolean
	isEmptyStatus?: boolean
}

export default memo(function WorkspaceWelcome(props: WorkspaceWelcomeProps) {
	const { setFileList, ...messagePanelProps } = props

	const { styles } = useStyles()

	return (
		<div className={styles.container}>
			<div className={styles.containerTop}>
				<div className={styles.hello}>
					<img src={delightfulBetaSVG} alt="delightful" className={styles.image} />
				<div className={styles.title}>👋 Hi, my friend</div>
				<div className={styles.subTitle}>How can Delightful help you?</div>
				</div>
				<img src={arrowDownSVG} alt="arrow-down" className={styles.arrowDown} />
				<div className={styles.caseWrapper}>
				<div className={styles.caseTitle}>「✨ 100x Productivity Cases」</div>
					<WorkspaceCase className={styles.case} />
				</div>
			</div>
			<MessagePanel
				// {...messagePanelProps}
				className={cx(styles.messagePanel, messagePanelProps.className)}
				setFileList={setFileList}
				showLoading={messagePanelProps.showLoading}
				selectedThreadInfo={messagePanelProps.selectedThreadInfo}
				onSendMessage={messagePanelProps.onSubmit}
				fileList={messagePanelProps.fileList}
				taskData={messagePanelProps.taskData}
				isEmptyStatus={messagePanelProps.isEmptyStatus}
				topicModeInfo={messagePanelProps.topicModeInfo}
			/>
		</div>
	)
})
