import React from "react"
import { NodesContext, NodesStateContext, NodesActionContext, NodesCtx } from "./Context"

export function NodesProvider({ nodes, setNodes, onNodesChange, children }: NodesCtx) {
	// Create separate context values for state and actions
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

	// Full context value for backward compatibility
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
