// @ts-nocheck
/**
 * Flow operation utility functions
 * Provides functionality for handling Flow operations
 */

import type { DelightfulFlowInstance } from "@bedelightful/delightful-flow/dist/DelightfulFlow"

/**
 * Add node
 * @param operation Operation object
 * @param flowInstance Flow instance
 */
const addNode = async (operation: any, flowInstance: DelightfulFlowInstance): Promise<void> => {
	const { nodeType, position, data = {}, params = {} } = operation

	if (!nodeType) {
		throw new Error("Add node operation is missing nodeType field")
	}

	const nodePosition = position || { x: 100, y: 100 }

	const node = {
		id: `node-${Date.now()}`,
		node_id: `node-${Date.now()}`,
		node_type: nodeType,
		node_version: "1.0.0",
		params,
		position: nodePosition,
		meta: {},
		next_nodes: [],
		step: 0,
		data,
		system_output: null,
	}

	// Use type assertion to resolve type errors
	await (flowInstance as any).addNode(node)
}

/**
 * Update node
 * @param operation Operation object
 * @param flowInstance Flow instance
 */
const updateNode = async (operation: any, flowInstance: DelightfulFlowInstance): Promise<void> => {
	const { nodeId, data, params } = operation

	if (!nodeId) {
		throw new Error("Update node operation is missing nodeId field")
	}

	try {
		if (data) {
			// Use type assertion to avoid TypeScript errors
			await (flowInstance as any).updateNodeData?.(nodeId, data)
		}

		if (params) {
			// Use type assertion to avoid TypeScript errors
			await (flowInstance as any).updateNodeParams?.(nodeId, params)
		}
	} catch (error) {
		console.error("Failed to update node:", error)
		throw error
	}
}

/**
 * Delete node
 * @param operation Operation object
 * @param flowInstance Flow instance
 */
const deleteNode = async (operation: any, flowInstance: DelightfulFlowInstance): Promise<void> => {
	const { nodeId } = operation

	if (!nodeId) {
		throw new Error("Delete node operation is missing nodeId field")
	}

	await flowInstance.deleteNodes([nodeId])
}

/**
 * Connect nodes
 * @param operation Operation object
 * @param flowInstance Flow instance
 */
const connectNodes = async (operation: any, flowInstance: DelightfulFlowInstance): Promise<void> => {
	const { sourceId, targetId, sourceHandle, targetHandle } = operation

	if (!sourceId || !targetId) {
		throw new Error("Connect nodes operation is missing sourceId or targetId field")
	}

	try {
		// Use type assertion to avoid TypeScript errors
		await (flowInstance as any).connectNodes?.(sourceId, targetId, sourceHandle, targetHandle)
	} catch (error) {
		console.error("Failed to connect nodes:", error)
		throw error
	}
}

/**
 * Disconnect nodes
 * @param operation Operation object
 * @param flowInstance Flow instance
 */
const disconnectNodes = async (operation: any, flowInstance: DelightfulFlowInstance): Promise<void> => {
	const { sourceId, targetId } = operation

	if (!sourceId || !targetId) {
		throw new Error("Disconnect nodes operation is missing sourceId or targetId field")
	}

	try {
		// Use type assertion to avoid TypeScript errors
		await (flowInstance as any).disconnectNodes?.(sourceId, targetId)
	} catch (error) {
		console.error("Failed to disconnect nodes:", error)
		throw error
	}
}

/**
 * Execute a single flow operation
 * @param operation Operation object
 * @param flowInstance Flow instance
 * @param flowId Flow ID
 * @returns Whether successful
 */
export const executeOperation = async (
	operation: any,
	flowInstance: DelightfulFlowInstance,
	flowId: string,
): Promise<boolean> => {
	if (!operation || !flowInstance) return false

	try {
		const { type } = operation

		switch (type) {
			case "addNode":
				await addNode(operation, flowInstance)
				break
			case "updateNode":
				await updateNode(operation, flowInstance)
				break
			case "deleteNode":
				await deleteNode(operation, flowInstance)
				break
			case "connectNodes":
				await connectNodes(operation, flowInstance)
				break
			case "disconnectNodes":
				await disconnectNodes(operation, flowInstance)
				break
			default:
				console.warn(`Unknown operation type: ${type}`)
				return false
		}

		return true
	} catch (error) {
		console.error(`Failed to execute operation:`, error, operation)
		return false
	}
}

/**
 * Execute flow operations
 * @param operations Operations array
 * @param flowInstance Flow instance
 * @param flowId Flow ID
 * @param onComplete Completion callback
 * @param onUpdate Update callback
 */
export const executeOperations = async (
	operations: any[],
	flowInstance: DelightfulFlowInstance,
	flowId: string,
	onComplete?: () => void,
	onUpdate?: (operation: any, index: number, success: boolean) => void,
): Promise<boolean> => {
	if (!operations || operations.length === 0 || !flowInstance) {
		if (onComplete) onComplete()
		return false
	}

	let success = true

	// Use Promise.all to handle all operations, avoiding direct await in loops
	const results = await Promise.all(
		operations.map(async (operation, i) => {
			const operationSuccess = await executeOperation(operation, flowInstance, flowId)

			if (onUpdate) {
				onUpdate(operation, i, operationSuccess)
			}

			return operationSuccess
		}),
	)

	// Check if any operations failed
	if (results.some((result) => !result)) {
		success = false
	}

	if (onComplete) {
		onComplete()
	}

	return success
}





