import { getIncomers, internalsSymbol, getOutgoers, Node, Edge, Position, Connection, ViewportHelperFunctions, getNodesBounds } from "reactflow"
import dagre from "dagre"
import { UpdateStepType } from "../hooks/useBaseFlow"
import _ from "lodash"
import { MagicFlow } from "../types/flow"
import { nodeManager } from "../register/node"
import { GROUP_MIN_DISTANCE, GROUP_TOP_GAP } from "../constants"
import { generatePasteEdges, generatePasteNode, judgeIsLoopBody } from "."

export const Ranksep = 100
export const Nodesep = 100

// Get the center coordinates of a node
export function getNodeCenter (node: Node) {
	return {
		x: node.positionAbsolute!.x + node.width! / 2,
		y: node.positionAbsolute!.y + node.height! / 2
	}
}

// Get the start and end coordinates of an edge
export function getEdgeHandlePosition (sourceNode: Node, targetNode: Node) {
	const sourceNodeHandle = sourceNode[internalsSymbol]!.handleBounds!.source![0]
	const targetNodeHandle = targetNode[internalsSymbol]!.handleBounds!.target![0]

	return {
		sx: sourceNodeHandle.x,
		sy: sourceNodeHandle.y,
		tx: targetNodeHandle.x,
		ty: targetNodeHandle.y
	}
}

// Recursively get all predecessor nodes
export const getAllPredecessors = (curNode: MagicFlow.Node, nodes: Node[], edges: Edge[], predecessors = [] as MagicFlow.Node[]): MagicFlow.Node[] => {
	// Use React Flow's getIncomers to gather all inbound nodes
    // @ts-ignore
	const incomers = [...(getIncomers(curNode, nodes, edges) || [])] as MagicFlow.Node[]

    // When incomers are branch nodes, do a second pass to track branch ids
    const branchNodes = incomers.filter(incomeNode => nodeManager.branchNodeIds.includes(`${incomeNode.node_type}`))
    branchNodes.forEach(branchNode => {
        const edgesBranchNode2CurNode = edges.filter(edge => edge.source === branchNode.node_id && edge.target === curNode.node_id)
        const branchIds = edgesBranchNode2CurNode.map(edge => edge.sourceHandle)
		if(!branchNode.params) return
        branchNode.params.outputBranchIds = branchIds
    })

	// If there are no incomers or all predecessors are visited, return the result
	if (!incomers || incomers.length === 0) {
		return predecessors
	}

	// Merge new predecessors into the list
	const updatedPredecessors = [ ...incomers, ...predecessors ]

	// For each new predecessor, recurse to find its predecessors
	// eslint-disable-next-line no-unused-vars
	return incomers.reduce(
        // @ts-ignore
		(acc, pred) => getAllPredecessors(pred, nodes, edges, acc),
		updatedPredecessors
	) as MagicFlow.Node[]
}

// Recursively get all successor nodes
export const getAllPostNodes = (curNode: MagicFlow.Node, nodes: MagicFlow.Node[], edges: Edge[], postNodes = [] as MagicFlow.Node[]): MagicFlow.Node[] => {
	// Use React Flow's getOutgoers to gather all outbound nodes
    // @ts-ignore
	const outNodes = getOutgoers(curNode, nodes, edges)

	// If there are no outgoers or all successors are visited, return the result
	if (!outNodes || outNodes.length === 0) {
		return postNodes
	}

	// Merge new successors into the list
	const updatedPredecessors = [ ...outNodes, ...postNodes ]

	// For each new successor, recurse to find its successors
	// eslint-disable-next-line no-unused-vars
	return outNodes.reduce(
        // @ts-ignore
		(acc, pred) => getAllPostNodes(pred, nodes, edges, acc),
		updatedPredecessors
	) as MagicFlow.Node[]
}

// Recursively get all successor nodes
// export const setNodeSteps = (curNode, nodes, edges, postNodes = [], prevStep = 0) => {

// 	// Use React Flow's getOutgoers to gather all outbound nodes
// 	const outNodes = getOutgoers(curNode, nodes, edges)

// 	// For each new successor, recurse to find its successors
// 	// eslint-disable-next-line no-unused-vars
// 	return outNodes.reduce(
// 		(acc, pred) => getAllPostNodes(pred, nodes, edges, acc),
// 		updatedPredecessors
// 	)
// }

export function sortByEdges (nodes: MagicFlow.Node[], edges: Edge[]) {
	// Dictionary to store indegree for each node
	const indegree = {} as Record<string, number>

	// Dictionary to store outgoing edges for each node
	const outEdges = {} as Record<string, string[]>

	// Initialize indegree and outgoing edge maps
	for (const node of nodes) {
		indegree[node.id] = 0
		outEdges[node.id] = []
	}

	// Calculate indegree for each node
	for (const edge of edges) {
		indegree[edge.target]++
		outEdges[edge.source].push(edge.target)
	}

	// Prepare result arrays
	const result = [] as MagicFlow.Node[]
	const nextNodes = [] as MagicFlow.Node[]

	// Seed with nodes whose indegree is zero
	for (const node of nodes) {
		if (indegree[node.id] === 0) {
			nextNodes.push(node)
			result.push(node)
		}
	}

	// Walk the queue and update indegree based on outgoing edges until empty
	while (nextNodes.length > 0) {
		const currentNode = nextNodes.shift()

        if(!currentNode) continue

		// Get all outgoing edges for the current node
		const currentOutEdges = outEdges[currentNode.id]

		// Traverse outgoing edges
		for (const targetNodeId of currentOutEdges) {
			// Decrease indegree of the target node
			indegree[targetNodeId]--

			// If target node indegree is zero, enqueue it
			if (indegree[targetNodeId] === 0) {
				const targetNode = nodes.find(node => node.id === targetNodeId)
				if(targetNode){
                    nextNodes.push(targetNode)
				    result.push(targetNode)
                }
			}
		}
	}

	return result
}

/** Run dagre layout */
export const dagreLayout = (direction="TB", nodes:MagicFlow.Node[], edges: Edge[]) => {
	const triggerNode = nodes[0]
	const dagreGraph = new dagre.graphlib.Graph()
	dagreGraph.setDefaultEdgeLabel(() => ({}))

	// Keep the trigger node fixed and auto-layout the others
	dagreGraph.setGraph({
		rankdir: direction,
		ranksep: Ranksep,
		nodesep: Nodesep,

		marginx: 0,
		marginy: 0
	})

	nodes.forEach((node: MagicFlow.Node) => {
		dagreGraph.setNode(node.id, { width: node.width, height: node.height })
	})

	edges.forEach((edge) => {
		dagreGraph.setEdge(edge.source, edge.target)
	})

	dagre.layout(dagreGraph)

	return dagreGraph
}

export const getLayoutElements = (_nodes: MagicFlow.Node[], _edges: Edge[], direction = "TB", paramsName: MagicFlow.ParamsName) => {

	const result = {
		nodes: _nodes,
		edges: _edges
	}

	const hasNodeWithoutSize = _nodes.some(n => !n.width || !n.height)
	if (hasNodeWithoutSize) return result

	if (_nodes.length === 0) return result

	const withoutGroupNodes = _nodes.filter(_n => !_n.parentId)

	const resultEdges = [..._edges]
	const resultNodes = [...withoutGroupNodes]

	const isHorizontal = direction === "LR"
	const dagreGraph = dagreLayout(direction, resultNodes, resultEdges)

	// Map of group layout instances, group id -> layout instance, to obtain child node positions
	const subFlowDagreMap = {} as Record<string, dagre.graphlib.Graph<{}>>

	_nodes.forEach((_n) => {
		const node = resultNodes.find(n => n.id === _n.id)!

		// If this is a group node, lay out all nodes inside the group
		// Prerequisite: group node order must come before child nodes
		if(judgeIsLoopBody(node?.[paramsName.nodeType])) {
			const groupNodes = _nodes.filter(n => n.parentId === node.id)
			const groupNodeIds = groupNodes.map(n => n.id)
			const groupEdges = _edges.filter(n => groupNodeIds.includes(n.source) || groupNodeIds.includes(n.target))
			const subFlowDagreInstance = dagreLayout(direction, groupNodes, groupEdges)
			subFlowDagreMap[node.id] = subFlowDagreInstance
		}

		// If the node belongs to a group
		if(!node) {
			// const parentNodeWithPosition = dagreGraph.node(node.parentId)
			const subFlowDagreInstance = Reflect.get(subFlowDagreMap,_n.parentId!)
			const childNodeWithPosition = subFlowDagreInstance?.node?.(_n.id)
			// Manually push grouped child nodes into the result
			resultNodes.push({
				..._n,
				position: {
					x: childNodeWithPosition?.x - childNodeWithPosition.width / 2 + GROUP_MIN_DISTANCE || 0,
					y: childNodeWithPosition?.y - childNodeWithPosition.height / 2 + GROUP_MIN_DISTANCE + GROUP_TOP_GAP || 0,
				},
				// @ts-ignore
				positionAbsolute: null
			})
		}else {
			const nodeWithPosition = dagreGraph.node(node?.id)
			node.targetPosition = isHorizontal ? Position.Left : Position.Top
			node.sourcePosition = isHorizontal ? Position.Right : Position.Bottom
			node.position = {
				x: nodeWithPosition.x - node.width! / 2,
				y: nodeWithPosition.y - node.height! / 2
			}
		}

		return node
	})


	// Further adjust group wrapper nodes, mainly to constrain size
	resultNodes.filter(n => judgeIsLoopBody(n[paramsName.nodeType])).forEach((groupNode) => {
		const subNodes = resultNodes.filter(n => n.parentId === groupNode.id)
		const isEmptyGroup = subNodes.length === 0
		// console.log("subNodes",subNodes)
		const { width, height } = getNodesBounds(subNodes)
		groupNode.width = isEmptyGroup ? 480 : width + GROUP_MIN_DISTANCE * 2
		groupNode.height = isEmptyGroup ? 600 : height + GROUP_TOP_GAP + GROUP_MIN_DISTANCE * 2
		groupNode.style = {
			width: groupNode.width,
			height: groupNode.height
		}

		// console.log("groupNode",groupNode, width, height)
	})


	return { nodes: resultNodes, edges: resultEdges }
}

/**
 * Update step data
 * @type {*} operation type: connect or delete edge
 * @connection {*} current connection
 * @nodeConfig {*} node configuration
 * @returns
 */

type UpdateTargetNodesStepProps = {
    type: UpdateStepType
    connection: Connection | Edge
    nodeConfig: Record<string, MagicFlow.Node>
    nodes: MagicFlow.Node[]
    edges: Edge[]
    beforeStep?: number
}

export const updateTargetNodesStep = ({
	type,
	connection,
	nodeConfig,
	nodes,
	edges,
	beforeStep = 0
}: UpdateTargetNodesStepProps) => {
	let sourceNode = nodes.find((n) => n.node_id === connection.source)
	if (type === UpdateStepType.Connect) {
		beforeStep = sourceNode!.step
	}
	if (!sourceNode) return
    // @ts-ignore
	const outNodes = getOutgoers(sourceNode, nodes, edges) as MagicFlow.Node[]

	for (const outNode of outNodes) {
		const outNodeConfig = nodeConfig[outNode.node_id]

		if (!outNodeConfig) {
			continue
		}

		const outNodeStep = beforeStep + 1
		outNodeConfig.step = outNodeStep

		nodeConfig[outNode.node_id] = outNodeConfig

		const _nodeIndex = nodes.findIndex((n) => n.node_id === outNode.node_id)
		if (_nodeIndex === -1) return
		nodes.splice(_nodeIndex, 1, { ...nodes[_nodeIndex], step: outNodeStep })

		const outEdges = edges.filter((edge) => edge.source === outNode.node_id)
		outEdges.forEach((outEdge) => {
			updateTargetNodesStep({
				type,
				connection: outEdge,
				nodeConfig,
				nodes,
				edges,
				beforeStep: beforeStep + 1
			})
		})

	}
}

/**
 * Check whether any execution nodes are outside the runnable flow (warn if some nodes will never run)
 */
export const checkHasNodeOutOfFlow = (nodes: MagicFlow.Node[], edges: Edge[]) => {
	if (!nodes || nodes.length === 0) return false
	const triggerNode = nodes[0]
	const postNodes = getAllPostNodes(triggerNode, nodes, edges)
	const uniqPostNodes = _.uniqBy(postNodes, "nodeId")
	return uniqPostNodes.length !== nodes.length - 1
}

/** Calculate the midpoint of all nodes */
export const calculateMidpoint = (nodes: MagicFlow.Node[]) => {
	const positions = nodes.map(n => n.position || {x: 0, y: 0})
    const totalPositions = positions.length;

		// Sum all x and y coordinates
    const total = positions.reduce((acc, pos) => {
        acc.x += pos.x;
        acc.y += pos.y;
        return acc;
    }, {x: 0, y: 0});

	// Compute midpoint coordinates
		const midpoint = {
        x: total.x / totalPositions,
        y: total.y / totalPositions
    };

    return midpoint;
}


/** Get rendered coordinates when adding a node inside a group */
export const getSubNodePosition = (event: any, screenToFlowPosition: ViewportHelperFunctions['screenToFlowPosition'], parentNode: MagicFlow.Node) => {

	const groupBodyPosition = parentNode?.position || { x: 0, y: 0 }

	const newNodePosition = screenToFlowPosition({
		x: event?.clientX || 100,
		y: event?.clientY || 200,
	})

	return {
		x: newNodePosition.x - groupBodyPosition.x,
		y: newNodePosition.y - groupBodyPosition.y,
	}
}


/**
 * Generate copied nodes and edges
 * @param nodeConfig full node configuration
 * @param selectionNodes selected nodes
 * @param selectionEdges selected edges
 * @param paramsName custom field names
 * @returns
 */
export const generatePasteNodesAndEdges = (nodeConfig: Record<string, MagicFlow.Node>,selectionNodes: MagicFlow.Node[], selectionEdges: Edge[], paramsName: MagicFlow.ParamsName) => {

    const selectedNodeIds = selectionNodes.map((n) => n.id)
    const oldId2NewIdMap = {} as Record<string, string>

	// Generate duplicated nodes and configs
    const pasteNodeInfos = selectionNodes.map((n) => {
        const paste = generatePasteNode(n, paramsName)
        oldId2NewIdMap[n.id] = paste.pasteNode.id
        
		// Handle next_nodes: keep references within the selection, drop external references
        if (paste.pasteNode[paramsName.nextNodes]) {
            const nextNodes = paste.pasteNode[paramsName.nextNodes] || []
			// Keep only nextNodes that are part of the selection
            const filteredNextNodes = nextNodes.filter((nodeId: string) => selectedNodeIds.includes(nodeId))
            paste.pasteNode[paramsName.nextNodes] = filteredNextNodes
        }

		// Handle branches on branch nodes
        if (nodeManager.branchNodeIds.includes(`${paste.pasteNode.node_type}`)) {
			// Get branches from params
            const branches = _.get(paste.pasteNode, [paramsName.params, 'branches'])
            
            if (branches && Array.isArray(branches)) {
				// For each branch, keep only next_nodes inside the selection
                branches.forEach(branch => {
                    if (branch && typeof branch === 'object' && Array.isArray(branch.next_nodes)) {
                        branch.next_nodes = branch.next_nodes.filter((nodeId: string) => selectedNodeIds.includes(nodeId))
                    }
                })
                
				// Update branches back on the node
                _.set(paste.pasteNode, [paramsName.params, 'branches'], branches)
            }
        }

        return { ...paste }
    })

	// Update node id references in next_nodes and branches
    pasteNodeInfos.forEach(({ pasteNode }) => {
		// Update regular next_nodes
        if (pasteNode[paramsName.nextNodes] && Array.isArray(pasteNode[paramsName.nextNodes])) {
            pasteNode[paramsName.nextNodes] = pasteNode[paramsName.nextNodes].map(
                (nodeId: string) => oldId2NewIdMap[nodeId] || nodeId
            );
        }
        
		// Update next_nodes inside branch definitions
        if (nodeManager.branchNodeIds.includes(`${pasteNode.node_type}`)) {
            const branches = _.get(pasteNode, [paramsName.params, 'branches']);
            if (branches && Array.isArray(branches)) {
                branches.forEach(branch => {
                    if (branch && typeof branch === 'object' && Array.isArray(branch.next_nodes)) {
                        branch.next_nodes = branch.next_nodes.map(
                            (nodeId: string) => oldId2NewIdMap[nodeId] || nodeId
                        );
                    }
                });
                _.set(pasteNode, [paramsName.params, 'branches'], branches);
            }
        }
    });

	// Replace any embedded id references so copied node A referencing copied node B now uses new ids
    pasteNodeInfos.forEach(({ pasteNode }) => {
		// Properties where ids need replacement
        const propsToReplace = [
            paramsName.params,
            'input',
            'output',
            'parentId',
            'meta'
        ]
        
		// Hold the stringified form of each property
        const stringifiedProps: Record<string, string> = {}
        
		// Stringify each property
        propsToReplace.forEach(propName => {
            if (pasteNode?.[propName]) {
                stringifiedProps[propName] = JSON.stringify(pasteNode[propName])
            }
        })
        
		// Replace all old ids with new ones
        Object.entries(oldId2NewIdMap).forEach(([oldId, newId]) => {
            const regex = new RegExp(oldId, "g")
            
			// Apply replacement to each property
            Object.keys(stringifiedProps).forEach(propName => {
                stringifiedProps[propName] = stringifiedProps[propName].replace(regex, newId)
            })
        })
        
		// Update node with replaced properties
        Object.keys(stringifiedProps).forEach(propName => {
            _.set(pasteNode, [propName], JSON.parse(stringifiedProps[propName]))
        })
    })

	// Generate duplicated edges
    const relationEdges = selectionEdges.filter((e) => {
		// Filter out edges whose source or target are outside the selection
        const isRelation =
            selectedNodeIds.includes(e.source) && selectedNodeIds.includes(e.target)

        return isRelation
    })
	/** Generate copied edges for the selected nodes */
    const pasteEdges = generatePasteEdges(oldId2NewIdMap, relationEdges)
    const pasteNodes = [] as MagicFlow.Node[]

    pasteNodeInfos.forEach(({ pasteNode }) => {
        pasteNodes.push(pasteNode)
    })

    return {
        pasteEdges,
        pasteNodes
    }
}