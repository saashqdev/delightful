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
			console.log("Processing confirmation operation command:", command, "messageId:", messageId)

			// Extract data required for confirmation operation from message
			setMessages((prev) =>
				prev.map((msg) =>
					msg.id === messageId
						? {
								...msg,
								confirmOperation: {
									type: "confirmOperation",
									message: command.message || "Please confirm if you want to execute this operation?",
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

	// Process确认Operation - 发送确认消息
	const handleConfirmOperation = useMemoizedFn(async (operationType: string, data?: any) => {
		// 移除所有消息的确认Operation按钮
		setMessages((prev) =>
			prev.map((msg) => ({
				...msg,
				confirmOperation: undefined,
			})),
		)

		// 如果没有提供发送消息的函数，则使用直接执行Operation的模式
		if (!sendMessage) {
			try {
				// 创建一个新的助手消息，显示确认Process中
				const assistantMessageId = Date.now().toString()
				const confirmMessage: Message = {
					id: assistantMessageId,
					role: "assistant",
					content: `正在执行${operationType}Operation...`,
					status: "loading",
				}

				// 添加确认消息
				setMessages((prev) => [...prev, confirmMessage])
				setForceScroll(true)

				// 执行Operation逻辑
				if (operationType === "confirmOperation" && data?.commands) {
					await executeOperations(data.commands, flowId || "")
				}

				// 更新消息为完成状态
				setMessages((prev) =>
					prev.map((msg) =>
						msg.id === assistantMessageId
							? {
									...msg,
									content: "Operation已成功执行",
									status: "done",
							  }
							: msg,
					),
				)
			} catch (error) {
				console.error("确认Operation执行失败:", error)
				const errorMessage = error instanceof Error ? error.message : String(error)

				// 添加错误消息
				const errorMessageId = Date.now().toString()
				setMessages((prev) => [
					...prev,
					{
						id: errorMessageId,
						role: "assistant",
						content: `Operation执行失败: ${errorMessage}`,
						status: "error",
					},
				])
				setForceScroll(true)
			}
			return
		}

		// 构建确认Operation的消息格式
		let confirmContent = `确认执行`

		// 如果有命令信息，添加命令详情
		if (data && typeof data === "object") {
			const commandInfo = JSON.stringify(data, null, 2)
			confirmContent += `\n\n命令信息: ${commandInfo}`
		}

		// 直接发送确认消息到服务端，让sendMessage函数负责创建和添加用户消息
		try {
			await sendMessage(confirmContent)
		} catch (error) {
			console.error("发送确认消息失败:", error)
		}
	})

	// ProcessCancelOperation - 发送Cancel消息
	const handleCancelOperation = useMemoizedFn((operationType: string) => {
		// 移除所有消息的确认Operation按钮
		setMessages((prev) =>
			prev.map((msg) => ({
				...msg,
				confirmOperation: undefined,
			})),
		)

		// 如果没有提供发送消息的函数，则使用直接显示Cancel消息的模式
		if (!sendMessage) {
			// 创建一个新的助手消息，显示Operation已Cancel
			const assistantMessageId = Date.now().toString()
			const cancelMessage: Message = {
				id: assistantMessageId,
				role: "assistant",
				content: `已Cancel${operationType}Operation`,
				status: "done",
			}

			// 添加Cancel消息
			setMessages((prev) => [...prev, cancelMessage])
			setForceScroll(true)
			return
		}

		// 构建CancelOperation的消息
		const cancelContent = `Cancel${operationType}Operation`

		// 直接发送Cancel消息到服务端，让sendMessage函数负责创建和添加用户消息
		try {
			sendMessage(cancelContent).catch((error) => {
				console.error("发送Cancel消息失败:", error)
			})
		} catch (error) {
			console.error("发送Cancel消息失败:", error)
		}
	})

	return {
		handleConfirmOperationCommand,
		handleConfirmOperation,
		handleCancelOperation,
	}
}

export default useConfirmOperations

