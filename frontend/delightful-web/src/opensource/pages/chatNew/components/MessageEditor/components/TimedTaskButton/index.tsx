import type { DelightfulButtonProps } from "@/opensource/components/base/DelightfulButton"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { IconClockPlay } from "@tabler/icons-react"
import { useTranslation } from "react-i18next"
import { Popover } from "antd"
import ConversationTaskService from "@/opensource/services/chat/conversation/ConversationTaskService"
import TaskContent from "./components/TaskContent"
import { useStyles } from "./styles"

interface TimedTaskButtonProps extends Omit<DelightfulButtonProps, "onClick"> {
	conversationId?: string
	iconSize?: number
}

function TimedTaskButton({
	conversationId,
	iconSize = 20,
	className,
	...props
}: TimedTaskButtonProps) {
	const { t } = useTranslation("interface")
	const { styles } = useStyles()

	return (
		<Popover
			overlayClassName={styles.popover}
			content={<TaskContent conversationId={conversationId} />}
			trigger="click"
		>
			<DelightfulButton
				type="text"
				icon={
					<DelightfulIcon
						color="currentColor"
						size={iconSize}
						component={IconClockPlay}
					/>
				}
				className={className}
				onClick={() => {
					ConversationTaskService.getTaskList()
				}}
				{...props}
			>
				{t("chat.timedTask.title")}
			</DelightfulButton>
		</Popover>
	)
}

export default TimedTaskButton
