import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { CloseCircleFilled } from "@ant-design/icons"
import { IconChecks, IconChevronDown, IconChevronUp, IconCircleCheck } from "@tabler/icons-react"
import { Tooltip } from "antd"
import { useState } from "react"
import type { TaskData } from "../../pages/Workspace/types"
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

interface TaskListProps {
	taskData: TaskData | null
	isInChat?: boolean
	className?: string
	style?: React.CSSProperties
	mode?: "view" | "default"
}

function TaskList({ taskData, className, style, mode }: TaskListProps) {
	const [expanded, setExpanded] = useState(mode === "view")

	const { styles, cx } = useStyles()
	// Calculate task completion progress
	const completedTasks = taskData?.process?.filter((task) => task.status === "finished").length

	const totalTasks = taskData?.process?.length

	// Get the current task in progress or the last completed task
	const getCurrentTask = () => {
		// 1. First, look for the task in progress
		const runningTask = taskData?.process?.find((task) => task.status === "running")
		if (runningTask) {
			return runningTask
		}

		// 2. If no task is in progress, find the first waiting task
		const todoTask = taskData?.process?.find((task) => task.status === "waiting")
		if (todoTask) {
			return todoTask
		}

		// 3. If there are no running or waiting tasks, return the last completed task
		const doneTasks = taskData?.process?.filter((task) => task.status === "finished") || []
		if (doneTasks.length > 0) {
			return doneTasks[doneTasks.length - 1]
		}

		// If there are no tasks, return the first task (if it exists)
		return taskData?.process?.[0]
	}

	// Get task icon
	const getTaskIcon = (status: string, taskId?: string) => {
		// Check if there is a task in progress
		const hasDoingTask = taskData?.process?.some((task) => {
			return task.status === "running"
		})
		// If there is no task in progress, find the first waiting task
		const firstTodoTask = !hasDoingTask
			? taskData?.process?.find((task) => task.status === "waiting")
			: null

		// If the current task is the first waiting task and there's no task in progress, show waiting icon
		if (
			!hasDoingTask &&
			firstTodoTask &&
			taskId &&
			taskId === firstTodoTask.id &&
			status === "waiting"
		) {
			return <AnimatedDoingIcon />
		}

		// Handle original logic
		switch (status) {
			case "finished":
				return expanded ? (
					<IconCircleCheck
						className={cx(styles.statusIcon, styles.statusDone)}
						stroke={1.5}
					/>
				) : (
					<IconChecks className={cx(styles.statusDone)} stroke={1.5} />
				)
			case "running":
				return <AnimatedDoingIcon />
			case "error":
				return <CloseCircleFilled className={cx(styles.statusIcon, styles.statusError)} />
			default:
				return <DefaultTaskIcon />
		}
	}

	// Get task status class name
	const getTaskStatusClassName = (status: string) => {
		switch (status) {
			case "finished":
				return styles.taskStatusDone
			case "waiting":
				return styles.taskStatusTodo
			case "error":
				return styles.taskStatusError
			default:
				return styles.taskStatusTodo
		}
	}

	const toggleExpanded = () => {
		if (mode === "view") return
		setExpanded(!expanded)
	}

	// Current running or last completed task
	const currentTask = getCurrentTask()

	return (
		<div
			className={cx(styles.container, !expanded && styles.containerCollapsed, className)}
			style={style}
		>
			{!expanded && (
				<>
					{getTaskIcon(currentTask?.status || "waiting", currentTask?.id)}
					<Tooltip title={currentTask?.title}>
						<div
							className={cx(
								styles.currentTaskText,
								!expanded
									? styles.taskStatusDefault
									: getTaskStatusClassName(currentTask?.status || "waiting"),
							)}
						>
							{currentTask?.title}
						</div>
					</Tooltip>
				</>
			)}
			<div
				className={cx(
					styles.containerInner,
					!expanded && styles.containerInnerCollapsed,
					mode === "view" && styles.containerInnerView,
				)}
			>
				<div
					className={cx(styles.header, expanded && styles.headerExpanded)}
					onClick={toggleExpanded}
				>
					<div className={cx(styles.headerLeft, !expanded && styles.headerLeftCollapsed)}>
					{expanded && <div className={styles.title}>Task List</div>}
					</div>
					{mode === "view" ? null : (
						<div className={styles.headerRight}>
							<div className={styles.progressWrapper}>
								<span className={styles.progressText}>
									{completedTasks} / {totalTasks}
								</span>
							</div>
							<DelightfulIcon
								size={18}
								component={expanded ? IconChevronDown : IconChevronUp}
							/>
						</div>
					)}
				</div>
				{expanded && (
					<div
						className={styles.taskList}
						style={{ maxHeight: mode === "view" ? "335px" : "240px" }}
					>
						{taskData?.process?.map((task) => (
							<div key={task.id} className={styles.taskItem}>
								<div className={styles.taskIcon}>
									{getTaskIcon(task.status, task.id)}
								</div>
								<Tooltip title={task.title}>
									<div className={styles.taskTitle}>{task.title}</div>
								</Tooltip>
							</div>
						))}
					</div>
				)}
			</div>
		</div>
	)
}

export default TaskList
