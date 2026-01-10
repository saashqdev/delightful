import React from "react"
import { NodesContext, NodesStateContext, NodesActionContext, NodesCtx } from "./Context"

export function NodesProvider({ nodes, setNodes, onNodesChange, children }: NodesCtx) {
	// Create state and action Context values separately
	const stateValue = React.useMemo(
		() => ({
			nodes,
		}),
		[nodes],
	)

	const actionValue = React.useMemo(
		() => ({
			setNodes,
			onNodesChange,
		}),
		[setNodes, onNodesChange],
	)

	// Complete Context value created for backward compatibility
	const contextValue = React.useMemo(
		() => ({
			...stateValue,
			...actionValue,
		}),
		[stateValue, actionValue],
	)

	return (
		<NodesStateContext.Provider value={stateValue}>
			<NodesActionContext.Provider value={actionValue}>
				<NodesContext.Provider value={contextValue}>{children}</NodesContext.Provider>
			</NodesActionContext.Provider>
		</NodesStateContext.Provider>
	)
}
