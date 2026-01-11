import type React from "react"
import { useEffect, useState, useRef } from "react"
import { useTranslation } from "react-i18next"
import { useMemoizedFn } from "ahooks"

interface CommandProcessorProps {
	commands: any[]
	messageId: string
	executeOperations: (operations: any[], flowId: string) => Promise<any[]>
	flowId: string
	commandExecutionRef: React.MutableRefObject<boolean>
	onCommandUpdate: (messageId: string, commandStatus: any[]) => void
	onComplete: () => void
}

// Use function component declaration instead of React.FC generic
function CommandProcessor(props: CommandProcessorProps): React.ReactElement | null {
	const {
		commands,
		messageId,
		executeOperations,
		flowId,
		commandExecutionRef,
		onCommandUpdate,
		onComplete,
	} = props

	const { t } = useTranslation()
	const [pendingCommands, setPendingCommands] = useState<any[]>([])
	const isProcessingRef = useRef<boolean>(false)

	// Initialize command queue
	useEffect(() => {
		if (commands.length > 0) {
			setPendingCommands((prev) => [...prev, ...commands])
		}
	}, [commands])

	// Execute next command
	const executeNextCommand = useMemoizedFn(async () => {
		if (pendingCommands.length === 0) {
			commandExecutionRef.current = false
			isProcessingRef.current = false

			onComplete()
			return
		}

		if (isProcessingRef.current) return

		isProcessingRef.current = true
		commandExecutionRef.current = true
		const command = pendingCommands[0]

		// 更新命令执行状态
		onCommandUpdate(messageId, [
			{
				type: command.type,
				status: "executing",
				message: t("flowAssistant.executingCommand", {
					type: command.type,
					ns: "flow",
				}),
			},
		])

		try {
			// 执行命令
			await executeOperations([command], flowId)

			// 从队列中移除已执行的命令
			setPendingCommands((prev) => prev.slice(1))

			// 更新命令完成状态
			onCommandUpdate(messageId, [
				{
					type: command.type,
					status: "completed",
					message: t("flowAssistant.commandCompleted", {
						type: command.type,
						ns: "flow",
					}),
				},
			])

			// 命令处理完成
			isProcessingRef.current = false

			// Execute the next command (use a short delay to ensure UI updates)
			setTimeout(() => {
				executeNextCommand()
			}, 100)
		} catch (error) {
			console.error(`Error executing command: ${command.type}`, error)

			// 更新命令错误状态
			const errorMessage = error instanceof Error ? error.message : String(error)
			onCommandUpdate(messageId, [
				{
					type: command.type,
					status: "failed",
					message: `${t("flowAssistant.commandFailed", {
						type: command.type,
						ns: "flow",
					})}: ${errorMessage}`,
				},
			])

			// 从队列中移除失败的命令
			setPendingCommands((prev) => prev.slice(1))

			// 命令处理完成
			isProcessingRef.current = false

			// 继续执行下一个命令
			setTimeout(() => {
				executeNextCommand()
			}, 100)
		}
	})

	// 当有命令但没有正在执行的命令时，启动执行
	useEffect(() => {
		const shouldStartExecution =
			pendingCommands.length > 0 && !commandExecutionRef.current && !isProcessingRef.current

		if (shouldStartExecution) {
			executeNextCommand()
		}
	}, [pendingCommands, executeNextCommand, commandExecutionRef])

	return null // 这是一个逻辑组件，不渲染UI
}

export default CommandProcessor
