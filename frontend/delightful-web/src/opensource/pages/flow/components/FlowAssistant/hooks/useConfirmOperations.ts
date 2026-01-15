import { useMemoizedFn } from "ahooks"
import type { Message } from ".."

/**
 * Hook for confirming operations, managing confirmation operation related states and methods
 */
interface UseConfirmOperationsProps {
	flowId: string
	executeOperations: (operations: any[], flowId: string) => Promise<any[]>
	setMessages: React.Dispatch<React.SetStateAction<Message[]>>
	setForceScroll: React.Dispatch<React.SetStateAction<boolean>>
	sendMessage?: (content: string) => Promise<void>
}

interface UseConfirmOperationsResult {
	handleConfirmOperationCommand: (command: any, messageId: string) => void
	handleConfirmOperation: (operationType: string, data?: any) => Promise<void>
	handleCancelOperation: (operationType: string) => void
}

export function useConfirmOperations({
	flowId,
	executeOperations,
	setMessages,
	setForceScroll,
	sendMessage,
}: UseConfirmOperationsProps): UseConfirmOperationsResult {
	// Process extraction of confirmation operation command from streaming response
	const handleConfirmOperationCommand = useMemoizedFn((command: any, messageId: string) => {
		if (command && command.type === "confirmOperation" && messageId) {
			console.log(
				"Processing confirmation operation command:",
				command,
				"messageId:",
				messageId,
			)

			// Extract data required for confirmation operation from message
			setMessages((prev) =>
				prev.map((msg) =>
					msg.id === messageId
						? {
								...msg,
								confirmOperation: {
									type: "confirmOperation",
									message:
										command.message ||
										"Please confirm if you want to execute this operation?",
									data: command.data || {},
								},
						  }
						: msg,
				),
			)

			// Force scroll to ensure user can see confirmation button
			setForceScroll(true)
		}
	})

	// process confirmation operation - send confirmation message
	const handleConfirmOperation = useMemoizedFn(async (operationType: string, data?: any) => {
		// remove confirmation operation button from all messages
		setMessages((prev) =>
			prev.map((msg) => ({
				...msg,
				confirmOperation: undefined,
			})),
		)

		// if no send message function provided, use direct operation execution pattern
		if (!sendMessage) {
			try {
				// create new assistant message showing confirmation processing
				const assistantMessageId = Date.now().toString()
				const confirmMessage: Message = {
					id: assistantMessageId,
					role: "assistant",
					content: `executing${operationType}operation...`,
					status: "loading",
				}

				// add confirmation message
				setMessages((prev) => [...prev, confirmMessage])
				setForceScroll(true)

				// execute operation logic
				if (operationType === "confirmOperation" && data?.commands) {
					await executeOperations(data.commands, flowId || "")
				}

				// update message to completed status
				setMessages((prev) =>
					prev.map((msg) =>
						msg.id === assistantMessageId
							? {
									...msg,
									content: "operation executed successfully",
									status: "done",
							  }
							: msg,
					),
				)
			} catch (error) {
				console.error("confirmation operation execution failed:", error)
				const errorMessage = error instanceof Error ? error.message : String(error)

				// add error message
				const errorMessageId = Date.now().toString()
				setMessages((prev) => [
					...prev,
					{
						id: errorMessageId,
						role: "assistant",
						content: `operation execution failed: ${errorMessage}`,
						status: "error",
					},
				])
				setForceScroll(true)
			}
			return
		}

		// build message format for confirmation operation
		let confirmContent = `confirm execution`

		// if command info exists, add command details
		if (data && typeof data === "object") {
			const commandInfo = JSON.stringify(data, null, 2)
			confirmContent += `\n\ncommand info: ${commandInfo}`
		}

		// send confirmation message directly to server, let sendMessage function handle creating and adding user message
		try {
			await sendMessage(confirmContent)
		} catch (error) {
			console.error("failed to send confirmation message:", error)
		}
	})

	// process cancel operation - send cancel message
	const handleCancelOperation = useMemoizedFn((operationType: string) => {
		// remove confirmation operation button from all messages
		setMessages((prev) =>
			prev.map((msg) => ({
				...msg,
				confirmOperation: undefined,
			})),
		)

		// if no send message function provided, use direct cancel message display pattern
		if (!sendMessage) {
			// create new assistant message showing operation cancelled
			const assistantMessageId = Date.now().toString()
			const cancelMessage: Message = {
				id: assistantMessageId,
				role: "assistant",
				content: `cancelled${operationType}Operation`,
				status: "done",
			}

			// add cancel message
			setMessages((prev) => [...prev, cancelMessage])
			setForceScroll(true)
			return
		}

		// build cancel operation message
		const cancelContent = `Cancel${operationType}Operation`

		// send cancel message directly to server, let sendMessage function handle creating and adding user message
		try {
			sendMessage(cancelContent).catch((error) => {
				console.error("failed to send cancel message:", error)
			})
		} catch (error) {
			console.error("failed to send cancel message:", error)
		}
	})

	return {
		handleConfirmOperationCommand,
		handleConfirmOperation,
		handleCancelOperation,
	}
}

export default useConfirmOperations
