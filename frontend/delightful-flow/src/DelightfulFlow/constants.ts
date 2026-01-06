import _ from "lodash"
import { NodeSchema, NodeWidget, nodeManager } from "./register/node"
import { getLatestNodeVersion } from "./utils"

export const prefix = "delightful-flow-"

/**
	 * Flow status
 */
export enum FlowStatus {
	// Unsaved
	UnSave = -1,

	// Draft
	Draft = 0,

	// Enabled
	Enable = 1
}

/**
	 * Node types
 */
export enum NodeType {
	// Selector
	If = "4",
}

/**
	 * Default node configuration field names
 */
export const defaultParamsName = {
	params: "params",
	nodeType: "node_type",
    nextNodes: "next_nodes"
}


// Execution node list
export const getExecuteNodeList = () => {
	/** Filter out nodes that should not appear on the material panel */
    return Object.keys(nodeManager.nodesMap)
            .filter((nodeType) => !nodeManager.notMaterialNodeTypes.includes(nodeType))
            .map(nodeType => {
                const latestVersion = getLatestNodeVersion(nodeType)
                return _.get(nodeManager.nodesMap, [nodeType, latestVersion], {} as NodeWidget)
            })
}
// Execution node group list
export const getExecuteNodeGroupList = () => {
    const executeNodeList = getExecuteNodeList()
    return executeNodeList.reduce((acc, cur) => {
		const groupName = cur.schema.groupName || "Default Group"
        if (!acc[groupName]) acc[groupName] = []
        acc[groupName].push(cur.schema)
        return acc
    }, {} as Record<string, NodeSchema[]>)
}

export enum FlowDesignerEvents {
	// Save/publish start event
	SubmitStart = 1,

	// Save/publish finished event
	SubmitFinished = 2,

	// Validation failure event
	ValidateError = 3,

	// Material panel visibility change event
	MaterialShowStatusChanged = 4
}


// Resolution percentage for rendering skeleton
export const renderSkeletonRatio = 15

// Threshold of node count to render skeleton for fit view
export const fitViewRatio = 30


// Front-end cache keys
export const localStorageKeyMap = {
	/** Front-end interaction mode */
	InteractionMode: prefix + 'interaction-mode'
}

// Default padding for group nodes
export const GROUP_MIN_DISTANCE = 30

// Top padding for group node headers
export const GROUP_TOP_GAP = 38

export const DefaultNodeVersion = 'v0'
