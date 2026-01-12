/**
 * Handle flow mouse events
 */

import { useMemoizedFn, useUpdateEffect } from "ahooks"
import { useFlowEdges, useFlowNodes, useFlowUI, useNodeConfig, useNodeConfigActions } from "@/DelightfulFlow/context/FlowContext/useFlow"
import { useNodes } from "@/DelightfulFlow/context/NodesContext/useNodes"
import { NodeSchema } from "@/DelightfulFlow/register/node"
import { generateSnowFlake } from "@/common/utils/snowflake"
import { useEffect, useRef, useState } from "react"
import { Edge, Node, NodeDragHandler, XYPosition, useReactFlow, useStoreApi, useUpdateNodeInternals } from "reactflow"
import _ from "lodash"
import { useExternalConfig } from "@/DelightfulFlow/context/ExternalContext/useExternal"
import { FlowDesignerEvents, renderSkeletonRatio } from "@/DelightfulFlow/constants"
import useViewport from "../../common/hooks/useViewport"
import { controlDuration } from "./useFlowControls"
import { generateLoopBody, generateNewNode, judgeIsLoopBody, judgeLoopNode } from "@/DelightfulFlow/utils"
import { DelightfulFlow } from "@/DelightfulFlow/types/flow"
import { FLOW_EVENTS, flowEventBus } from "@/common/BaseUI/Select/constants"

type UseFlowEventProps = {
    // Reset last layout data
    resetLastLayoutData: () => void
    // Reset whether can layout
    resetCanLayout: () => void
	// Current zoom scale
	currentZoom: number
	// Whether to show component parameter configuration change function
	setShowParamsComp: React.Dispatch<React.SetStateAction<boolean>>
}

/** Position for adding node */
export enum AddPosition {
	// Add node on edge
	Edge = 'edge',
	// Add node on node
	Node = 'node',
	// Add on canvas
	Canvas = 'canvas'
}

export default function useFlowEvents ({ resetLastLayoutData, resetCanLayout, currentZoom, setShowParamsComp }: UseFlowEventProps) {
	const store = useStoreApi()


    const { 
		addNode,
        selectedNodeId,
		setSelectedNodeId,
		deleteNodes,
		updateNodesPosition,
    } = useFlowNodes()

	const { nodeConfig } = useNodeConfig()

	const { notifyNodeChange } = useNodeConfigActions()

	const {
		edges,
		setEdges,
		setSelectedEdgeId,
		updateNextNodeIdsByDeleteEdge,
    } = useFlowEdges()

    const {
        flowDesignListener
    } = useFlowUI()
	
	const { 
		nodes
	} = useNodes()

	const updateNodeInternals = useUpdateNodeInternals();
	const states = store.getState()

	const reactFlowWrapper = useRef<HTMLDivElement>(null)

	const reactflowRef = useRef(null)
	const { screenToFlowPosition } = useReactFlow()

	/** Whether lazy rendering */
	const [ onlyRenderVisibleElements, setOnlyRenderVisibleElements ] = useState(true)

	/** Position flow view to target position */
	const { updateViewPortToTargetNode } = useViewport()

	useUpdateEffect(() => {
		setShowParamsComp(Math.ceil(currentZoom * 100) > renderSkeletonRatio || !onlyRenderVisibleElements)
	}, [ currentZoom, onlyRenderVisibleElements ])


	// Whether dragging
	const [ isDragging, setIsDragging ] = useState(false)

	const { paramsName } = useExternalConfig()


	const onEdgeClick = useMemoizedFn((event, edge) => {
		event.stopPropagation()
		setSelectedEdgeId(edge.id)
        flowEventBus.emit(FLOW_EVENTS.EDGE_SELECTED, edge.id)
	})

	const onNodeDragStart = useMemoizedFn((event, node) => {
		setIsDragging(true)
		setSelectedNodeId(node.id)
		resetLastLayoutData()
		resetCanLayout()

	})

	flowDesignListener.useSubscription((event) => {
		switch (event.type) {
			case FlowDesignerEvents.SubmitStart:
				setOnlyRenderVisibleElements(false)
				break
			case FlowDesignerEvents.SubmitFinished:
				setOnlyRenderVisibleElements(true)
				break
			case FlowDesignerEvents.ValidateError:
				setOnlyRenderVisibleElements(true)
				const errorNodeIds = event.data || []
				if (errorNodeIds.length > 0) {
					const locationId = errorNodeIds[0]

				// Validation failed node
				const errorNode = nodes.find(n => n.node_id === locationId)
				const errorNodeRef = nodeConfig[locationId]

				updateViewPortToTargetNode(errorNode)

				// Need to re-validate the failed node because lazy rendering invalidated the previous validation result
					setTimeout(() => {
						errorNodeRef?.validate?.()
					}, controlDuration)
				}
				break
			default:
				return
		}
	})

	// Node drag end
	const onNodeDragStop = useMemoizedFn((event: any, node: Node, dragNodes: Node[]) => {
		setIsDragging(false)
		const nodeIds = dragNodes.map((n) => n.id)

		// {[id]: position}
        // @ts-ignore
		const positionMap = nodes.reduce((acc, cur) => ({ ...acc, [cur.id]: cur.position }), {} as Record<string, XYPosition>)
		updateNodesPosition(nodeIds, positionMap)
		if(node?.parentId) {
			updateNodeInternals(node?.parentId)
		}
	})

	// Node drag event
	// areaNodes only lists nodes in the current group
	const onNodeDrag: NodeDragHandler = useMemoizedFn((event, node, areaNodes) => {
		// If within a group, get boundary of all nodes in the group and set parent node size
		// if(node.parentId) {
		// 	const childNodes = nodes.filter(n => n.parentId === node.parentId)
		// 	const restNodes = childNodes.filter(n => n.id !== node.id)
		// 	const parentNode = nodes.find(n => n.id === node.parentId)
		// 	const childBounds = getNodesBounds(childNodes)
		// 	console.log("bounds", childBounds)


		// 	if(parentNode) {

				
				// Copy parent node and update dimensions and position
				const newParentNode = {
		// 			...parentNode,
					position: { ...parentNode.position }, // Ensure position is a new object
					style: { ...parentNode.style }, // Ensure style is a new object
		// 		};

		// 		const rawParentX = newParentNode.position.x
		// 		const rawParentY = newParentNode.position.y
		// When exceeding left boundary
		// 		if(childBounds.x < newParentNode.position.x + MIN_DISTANCE) {
		console.log("Exceeds left boundary")
		// 			newParentNode.position.x =  childBounds.x - MIN_DISTANCE
		// 		}
		// When exceeding right boundary
		// 		else if((childBounds.x + childBounds.width) > (newParentNode.position.x + newParentNode.width! - MIN_DISTANCE)) {
		console.log("Exceeds right boundary ",childBounds.width)
		// 			newParentNode.position.x = childBounds.x - MIN_DISTANCE
					
		// 			// const parentOffsetX = Math.abs(newParentNode.position.x - rawParentX )
		// 			// restNodes.forEach(n => {
		// 			// 	n.position.x = n.position.x - parentOffsetX
		// 			// })
		// 		}
		// When exceeding top boundary, 38px is blank area
		// 		else if(childBounds.y < newParentNode.position.y + MIN_DISTANCE + TOP_GAP) {
		console.log("Exceeds top boundary")
		// 			newParentNode.position.y =  childBounds.y - MIN_DISTANCE - TOP_GAP
					
		// 		}
		// When exceeding bottom boundary
		// 		else if((childBounds.y + childBounds.height) > (newParentNode.position.y + newParentNode.height! - MIN_DISTANCE)) {
		console.log("Exceeds bottom boundary")
		// 			newParentNode.position.y = childBounds.y - MIN_DISTANCE - TOP_GAP
		// 		}

		// 		newParentNode.width = childBounds.width + MIN_DISTANCE * 2// @ts-ignore
		// 		newParentNode.style.width = newParentNode.width
		// 		newParentNode.height = childBounds.height + MIN_DISTANCE * 2 + TOP_GAP // @ts-ignore
		// 		newParentNode.style.height = newParentNode.height
		// 		console.log("parent", _.pick(newParentNode, ['width', 'height']))
		// 		setNodes(prevNodes => prevNodes.map(n => n.id === newParentNode.id ? newParentNode : n));
		// 	}
			
		// }
	})

	const onAddItem = useMemoizedFn(async (event: any, nodeData: NodeSchema, extraConfig?: Record<string, any>) => {
        if(!reactFlowWrapper.current) return
		const newNodes = []
		const newEdges = []
		// TODO Render via node schema
		const nodeModel = {
			remark: ""
		}
		if (!nodeModel) {
			return
		}
		const id = event.uniqueNodeId || generateSnowFlake()

		const position = screenToFlowPosition({
			x: (event.clientX || 100),
			y: (event.clientY || 200)
		})

		const newNode = generateNewNode(nodeData, paramsName, id, position, extraConfig)

		newNodes.push(newNode)

		// If adding a loop, need to add a loop body and an edge
		if (judgeLoopNode(newNode[paramsName.nodeType])) {
			const { newNodes: bodyNodes, newEdges: bodyEdges } = generateLoopBody(
				newNode,
				paramsName,
				edges,
			)
			newNodes.push(...bodyNodes)
			newEdges.push(...bodyEdges)
		}

		addNode(newNodes, newEdges)

		resetLastLayoutData()
		resetCanLayout()
	})

	// Drag and drop material release
	const onDrop = useMemoizedFn(async (event) => {
		console.log("states", states, event)

		event.preventDefault()

		const jsonString = event.dataTransfer.getData("node-data")

		try {
			// Check if input is empty or undefined
			if (jsonString) {
				let dragData = JSON.parse(jsonString);
				onAddItem(event, dragData)
			} else {
				console.log("JSON string is empty or undefined. Returning default value.");
			}
		} catch (error) {
			console.error("Failed to parse JSON:", error);
			// Here you can choose to return a default value or take other error handling measures
		}


	})

	const onDragOver = useMemoizedFn((event) => {
		event.stopPropagation()
		event.preventDefault()
	})

	const onReactFlowClick = useMemoizedFn(() => {
		setSelectedNodeId(null)
		setSelectedEdgeId(null)
        flowEventBus.emit(FLOW_EVENTS.EDGE_SELECTED, null)
        flowEventBus.emit(FLOW_EVENTS.NODE_SELECTED, null)
		// states.unselectNodesAndEdges()
	})

	// backspacedeleteevent
	const onNodesDelete = useMemoizedFn((_nodes: (Node & Partial<DelightfulFlow.Node>)[]) => {
		const deleteIds = _nodes.reduce((acc, n) => {
			// @ts-ignore
			const nodeType = n.node_type
			// If deleting a group node, delete its child nodes too
			if(judgeIsLoopBody(nodeType)) {
				const subNodeIds = nodes.filter(_n => _n.parentId === n.id).map(_n => _n.id)
				const result = [...acc,...subNodeIds, n.id]
				// If deleting a loop body, delete the loop node as well
				// @ts-ignore
				if(n.meta.parent_id) {
					result.push(n?.meta?.parent_id)
				}
				return result
			}
			// If deleting a loop node, delete the loop body and its nodes
			if(judgeLoopNode(nodeType)) {
				const loopBodyNodeIds = nodes.filter(_n => _n.parentId === n.id || n?.meta?.parent_id === n.id).map(_n => _n.id)
				return [...acc,...loopBodyNodeIds, n.id]
			}
			return [...acc, n.id]
		}, [] as string[])

        deleteNodes(deleteIds)

		resetLastLayoutData()
		resetCanLayout()
		
		notifyNodeChange?.()
	})

	const onEdgesDelete = useMemoizedFn((_edges: Edge[]) => {
		const deleteIds = _edges.map(n => {
			updateNextNodeIdsByDeleteEdge(n)
			return n.id
		})

		const leaveEdges = edges.filter(e => !deleteIds.includes(e.target))

		// Update edge data
		setEdges(leaveEdges)

		resetLastLayoutData()
		resetCanLayout()

		notifyNodeChange?.()
	})

	useEffect(() => {
		const handleSaveShortcut = (event: any) => {
		  if ((event.metaKey || event.ctrlKey) && event.key === 's') {
			event.preventDefault();
		  }
		};
	
		window.addEventListener('keydown', handleSaveShortcut);
	
		return () => {
		  window.removeEventListener('keydown', handleSaveShortcut);
		};
	  }, []);

	return {
		onEdgeClick,
		selectedNodeId,
		setSelectedNodeId,
		onNodeDragStop,
		onDrop,
		onDragOver,
		reactFlowWrapper,
		onReactFlowClick,
		onNodeDragStart,
		isDragging,
		onNodesDelete,
		onEdgesDelete,
		reactflowRef,
		onAddItem,
		onlyRenderVisibleElements,
		onNodeDrag
	}
}

