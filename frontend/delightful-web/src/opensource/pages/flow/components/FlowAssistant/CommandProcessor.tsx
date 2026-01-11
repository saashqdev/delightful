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

		// Update command execution status
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
			// Execute command
			await executeOperations([command], flowId)

			// Remove executed command from queue
			setPendingCommands((prev) => prev.slice(1))

			// Update command completion status
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

			// Command process completed
			isProcessingRef.current = false

			// Execute the next command (use a short delay to ensure UI updates)
			setTimeout(() => {
				executeNextCommand()
			}, 100)
		} catch (error) {
			console.error(`Error executing command: ${command.type}`, error)

			// Update command error status
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

			// Remove failed command from queue
			setPendingCommands((prev) => prev.slice(1))

			// Command process completed
			isProcessingRef.current = false

			// Continue executing next command
			setTimeout(() => {
				executeNextCommand()
			}, 100)
		}
	})

	// When there is a command but no executing command, start execution
	useEffect(() => {
		const shouldStartExecution =
			pendingCommands.length > 0 && !commandExecutionRef.current && !isProcessingRef.current

		if (shouldStartExecution) {
			executeNextCommand()
		}
	}, [pendingCommands, executeNextCommand, commandExecutionRef])

	return null // This is a logical component, does not render UI
}

export default CommandProcessor






