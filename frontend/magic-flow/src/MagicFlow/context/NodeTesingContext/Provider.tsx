/**
 * For business nodes to inject the current node under test, its status, and results for display in base nodes.
 */
import React, { useMemo } from "react"
import { NodeTestingContext, NodeTestingCtx } from "./Context"

export const NodeTestingProvider = ({
	// IDs of nodes currently being debugged (used to show loading)
	nowTestingNodeIds,
	// IDs of nodes under test
	testingNodeIds,
	// Map of nodeId -> debug log
	testingResultMap,
	// Whether to auto-locate the node
	position,
	children,
}: NodeTestingCtx) => {
	const value = useMemo(() => {
		return {
			nowTestingNodeIds,
			testingNodeIds,
			testingResultMap,
			position
		}
	}, [nowTestingNodeIds, testingNodeIds, testingResultMap, position])

	return <NodeTestingContext.Provider value={value}>{children}</NodeTestingContext.Provider>
}
