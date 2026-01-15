import { useRef } from "react"
import { message as antdMessage } from "antd"
import { useTranslation } from "react-i18next"
import { useMemoizedFn } from "ahooks"
import { DelightfulFlow } from "@bedelightful/delightful-flow/dist/DelightfulFlow/types/flow"
import { set } from "lodash-es"
import { FlowApi } from "@/apis"
import { getLatestNodeVersion } from "@bedelightful/delightful-flow/dist/DelightfulFlow/utils"

interface UseFlowOperationsProps {
	flowInteractionRef: React.MutableRefObject<any>
	saveDraft: () => Promise<void>
	flowService: any // Can be adjusted according to actual type
}

export default function useFlowOperations({
	flowInteractionRef,
	saveDraft,
	flowService,
}: UseFlowOperationsProps) {
	const { t } = useTranslation()
	const commandExecutionRef = useRef<boolean>(false)
	// Used to store executed commandId
	const executedCommandsRef = useRef<Set<string>>(new Set())

	// Locate to node
	const goToNode = useMemoizedFn((nodeId: string) => {
		setTimeout(() => {
			if (!flowInteractionRef.current) return

			const node = flowInteractionRef.current.nodes?.find?.(
				(n: DelightfulFlow.Node) => n.node_id === nodeId,
			)
			if (node && node.width && node.height) {
				flowInteractionRef.current.updateViewPortToTargetNode(node)
			}
		}, 200)
	})

	// Add node
	const addNode = useMemoizedFn(async (nodeType: string, nodeId: string, updateList: any[]) => {
		if (!flowInteractionRef.current) return false
		const nodeTemplate = await FlowApi.getNodeTemplate(nodeType)
		updateList.forEach((update: any) => {
			set(nodeTemplate, update.path, update.value)
		})
		nodeTemplate.id = nodeId
		nodeTemplate.node_id = nodeId
		nodeTemplate.node_version = getLatestNodeVersion(nodeType)
		if (Array.isArray(nodeTemplate.meta) && nodeTemplate.meta.length === 0) {
			nodeTemplate.meta = { position: { x: 200, y: 200 } }
		}
		try {
			flowInteractionRef.current.addNode(nodeTemplate)

			setTimeout(() => {
				// Has nodes, automatically locate to connected node
				goToNode(nodeTemplate.node_id)
			}, 200)
			console.log("Node added:", nodeTemplate)
			return true
		} catch (error) {
			console.error("Add node failed:", error)
			antdMessage.error(t("flowAssistant.addNodeError", { ns: "flow" }))
			return false
		}
	})

	// Update node
	const updateNode = useMemoizedFn((nodeId: string, updateList: any) => {
		if (!flowInteractionRef.current) return false

		try {
			// Update node config
			const { nodeConfig } = flowInteractionRef.current || {}
			const currentNode = nodeConfig[nodeId]

			goToNode(nodeId)

			if (currentNode) {
				updateList.forEach((update: any) => {
					set(currentNode, update.path, update.value)
				})
				flowInteractionRef.current.updateNodeConfig({ ...currentNode })
			}

			console.log("Node updated:", nodeId, currentNode)
			return true
		} catch (error) {
			console.error("Update node failed:", error)
			antdMessage.error(t("flowAssistant.updateNodeError", { ns: "flow" }))
			return false
		}
	})

	// Delete node
	const deleteNode = useMemoizedFn((nodeId: string) => {
		if (!flowInteractionRef.current) return false

		try {
			flowInteractionRef.current.deleteNodes([nodeId])
			console.log("Node deleted:", nodeId)
			return true
		} catch (error) {
			console.error("Delete node failed:", error)
			antdMessage.error(t("flowAssistant.deleteNodeError", { ns: "flow" }))
			return false
		}
	})

	// Connect nodes
	const connectNodes = useMemoizedFn(
		(sourceNodeId: string, targetNodeId: string, sourceHandleId: string) => {
			if (!flowInteractionRef.current) return false

			try {
				flowInteractionRef.current.onConnect({
					source: sourceNodeId,
					target: targetNodeId,
					sourceHandle: sourceHandleId,
				})
				goToNode(targetNodeId)
				console.log("Nodes connected:", sourceNodeId, "->", targetNodeId)
				return true
			} catch (error) {
				console.error("Connect nodes failed:", error)
				antdMessage.error(t("flowAssistant.connectNodesError", { ns: "flow" }))
				return false
			}
		},
	)

	// Disconnect nodes
	const disconnectNodes = useMemoizedFn((sourceNodeId: string, targetNodeId: string) => {
		if (!flowInteractionRef.current) return false

		try {
			// Get next node list of the current source node
			const currentFlow = flowInteractionRef.current.getFlow()
			const sourceNode = currentFlow.nodes?.find(
				(node: DelightfulFlow.Node) => node.id === sourceNodeId,
			)
			const nextNodeIds = (sourceNode?.next_nodes || []).filter(
				(id: string) => id !== targetNodeId,
			)

			// Update connection
			flowInteractionRef.current.updateNextNodeIdsByDeleteEdge(sourceNodeId, nextNodeIds)
			console.log("Node connection disconnected:", sourceNodeId, "->", targetNodeId)
			return true
		} catch (error) {
			console.error("Disconnect nodes failed:", error)
			antdMessage.error(t("flowAssistant.disconnectNodesError", { ns: "flow" }))
			return false
		}
	})

	// Publish flow
	const publishFlow = useMemoizedFn(async (publishData: any, flowId: string) => {
		if (!flowInteractionRef.current) return false

		const currentFlow = flowInteractionRef.current.getFlow()
		if (!currentFlow) return false

		try {
			await flowService.publishFlow(
				{
					name: publishData.name || currentFlow.name,
					description: publishData.description || currentFlow.description,
					delightful_flow: currentFlow,
				},
				flowId,
			)

			antdMessage.success(t("flowAssistant.publishSuccess", { ns: "flow" }))
			return true
		} catch (error) {
			console.error("Error publishing flow:", error)
			antdMessage.error(t("flowAssistant.publishError", { ns: "flow" }))
			return false
		}
	})

	// Execute flow operation commands
	const executeOperations = useMemoizedFn(async (operations: any[], flowId: string) => {
		if (!flowInteractionRef.current) return []

		const results = []
		// Avoid using await inside the loop; process operations sequentially
		for (let i = 0; i < operations.length; i += 1) {
			const operation = operations[i]

			// Check whether there is a commandId and whether it has already been executed
			if (operation.commandId && executedCommandsRef.current.has(operation.commandId)) {
				console.log(
					`Operation already executed, skipping: commandId=${operation.commandId}, type=${operation.type}`,
				)
				results.push({
					type: operation.type,
					success: true,
					skipped: true,
					commandId: operation.commandId,
				})
				continue
			}

			try {
				let result
				switch (operation.type) {
					case "addNode":
						result = addNode(operation.nodeType, operation.nodeId, operation.updateList)
						break
					case "updateNode":
						result = updateNode(operation.nodeId, operation.updateList)
						break
					case "deleteNode":
						result = deleteNode(operation.nodeId)
						break
					case "connectNodes":
						result = connectNodes(
							operation.sourceNodeId,
							operation.targetNodeId,
							operation.sourceHandleId,
						)
						break
					case "disconnectNodes":
						result = disconnectNodes(operation.sourceNodeId, operation.targetNodeId)
						break
					case "saveDraft":
						// Use await only in saveDraft and publishFlow; these are necessary async operations
						// eslint-disable-next-line no-await-in-loop
						result = await saveDraft()
						break
					case "publishFlow":
						// eslint-disable-next-line no-await-in-loop
						result = await publishFlow(operation.publishData, flowId)
						break
					default:
						console.warn("Unknown operation type:", operation.type)
						result = false
				}

				// If executed successfully and has commandId, record to executed set
				if (result && operation.commandId) {
					executedCommandsRef.current.add(operation.commandId)
				}

				results.push({
					type: operation.type,
					success: !!result,
					commandId: operation.commandId,
				})
			} catch (error) {
				console.error(`Error executing operation ${operation.type}:`, error)
				results.push({
					type: operation.type,
					success: false,
					error,
					commandId: operation.commandId,
				})
			}
		}
		return results
	})

	// Reset executed command records
	const resetExecutedCommands = useMemoizedFn(() => {
		executedCommandsRef.current.clear()
		console.log("Executed command records reset")
	})

	return {
		addNode,
		updateNode,
		deleteNode,
		connectNodes,
		disconnectNodes,
		publishFlow,
		executeOperations,
		resetExecutedCommands,
		commandExecutionRef,
	}
}
