/**
 * Used by business nodes to inject the current testing node, testing state, and testing results, displayed by base nodes
 */
import React, { useMemo } from "react"
import { NodeTestingContext, NodeTestingCtx } from "./Context"

export const NodeTestingProvider = ({
	// List of node IDs currently being debugged (for displaying loading)
	nowTestingNodeIds,
	// Current debugging node ID
	testingNodeIds,
	// Node ID -> Node debugging log
	testingResultMap,
	// Whether to perform automatic node positioning
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
