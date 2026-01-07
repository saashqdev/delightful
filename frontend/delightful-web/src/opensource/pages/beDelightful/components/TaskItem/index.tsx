import { CloseCircleFilled } from "@ant-design/icons"
import { IconChecks } from "@tabler/icons-react"
import { useStyles } from "./styles"

// Custom icon component for doing status
const AnimatedDoingIcon = () => {
	const { styles, cx } = useStyles()

	return (
		<div className={cx(styles.doingIconContainer)}>
			<div className={cx(styles.doingIconOuter)} />
			<div className={cx(styles.doingIconPulse)} />
			<div className={cx(styles.doingIconInner)} />
		</div>
	)
}

// Custom default icon component
const DefaultTaskIcon = () => {
	const { styles, cx } = useStyles()

	return (
		<div className={cx(styles.defaultIconContainer)}>
			<div className={cx(styles.defaultIcon)} />
		</div>
	)
}

interface TaskItemProps {
	status?: "waiting" | "running" | "finished" | "error"
	title: string
	taskId?: string
	hasDoingTask?: boolean
	isFirstTodo?: boolean
}

const TaskItem = ({
	status = "waiting",
	title,
	taskId,
	hasDoingTask,
	isFirstTodo,
}: TaskItemProps) => {
	const { styles, cx } = useStyles()

	// Get task icon
	const getTaskIcon = (
		currentStatus: string,
		currentTaskId?: string,
		currentHasDoingTask?: boolean,
		currentIsFirstTodo?: boolean,
	) => {
		// If current task is the first pending task and there's no task in progress, show doing icon
		if (
			!currentHasDoingTask &&
			currentIsFirstTodo &&
			currentTaskId &&
			currentStatus === "waiting"
		) {
			return <AnimatedDoingIcon />
		}

		// Handle original logic
		switch (currentStatus) {
			case "done":
				return <IconChecks className={cx(styles.statusIcon, styles.statusDone)} />
			case "doing":
				return <AnimatedDoingIcon />
			case "error":
				return <CloseCircleFilled className={cx(styles.statusIcon, styles.statusError)} />
			default:
				return <DefaultTaskIcon />
		}
	}

	// Get task status class name
	const getTaskStatusClassName = (currentStatus: string) => {
		switch (currentStatus) {
			case "finished":
				return styles.taskStatusDone
			case "running":
				return styles.taskStatusDoing
			case "error":
				return styles.taskStatusError
			default:
				return styles.taskStatusTodo
		}
	}

	return (
		<div className={styles.progressWrapper}>
			{getTaskIcon(status, taskId, hasDoingTask, isFirstTodo)}
			<span className={cx(styles.currentTaskText, getTaskStatusClassName(status))}>
				{title}
			</span>
		</div>
	)
}

export default TaskItem
