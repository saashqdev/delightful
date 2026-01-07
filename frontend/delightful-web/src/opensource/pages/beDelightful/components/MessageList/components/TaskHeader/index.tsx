import { Button } from "antd"
import { useState, useEffect } from "react"
import { IconChevronRight, IconChevronDown, IconChecks } from "@tabler/icons-react"
import { SyncOutlined, ClockCircleOutlined } from "@ant-design/icons"
import { useStyles } from "./style"

interface TaskHeaderProps {
	tasks: any[]
	isExpanded: boolean
	onToggleExpand: () => void
}

// Get status icon
const getStatusIcon = (status: string) => {
	if (status === "done") return <IconChecks style={{ color: "#43b581" }} />
	if (status === "doing") return <SyncOutlined spin style={{ color: "rgba(28, 29, 35, 0.8)" }} />
	return <ClockCircleOutlined style={{ color: "#747f8d" }} />
}

// Get color for task status
const getTaskColor = (status: string) => {
	if (status === "doing") return "rgba(28, 29, 35, 0.8)"
	if (status === "done") return "#43b581"
	return "#747f8d"
}

const TaskHeader = ({ tasks, isExpanded, onToggleExpand }: TaskHeaderProps) => {
	const { styles } = useStyles()
	const [currentTask, setCurrentTask] = useState<any | null>(null)

	// Find the last running task when component mounts or task list updates
	useEffect(() => {
		if (tasks && tasks.length > 0) {
			// First find the running task
			const runningTask = [...tasks].reverse().find((task) => task.status === "doing")
			// If no running task exists, use the first task
			setCurrentTask(runningTask || tasks[0])
		}
	}, [tasks])

	if (!tasks || tasks.length === 0) {
		return null
	}

	return (
		<div className={styles.expandIcon}>
			<span>Task Progress</span>
			{!isExpanded && currentTask && (
				<span
					style={{
						marginLeft: "4px",
						display: "flex",
						alignItems: "center",
					}}
				>
					{getStatusIcon(currentTask.status)}
					<span
						style={{
							marginLeft: "4px",
							fontSize: "12px",
							color: getTaskColor(currentTask.status),
						}}
					>
						{currentTask.title}
					</span>
				</span>
			)}
			<Button
				className={styles.iconButton}
				size="small"
				type="text"
				icon={isExpanded ? <IconChevronDown size={18} /> : <IconChevronRight size={18} />}
				onClick={onToggleExpand}
			/>
		</div>
	)
}

export default TaskHeader
