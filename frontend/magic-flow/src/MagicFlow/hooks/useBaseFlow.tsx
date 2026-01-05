/**
 * Define data state & behavior for the main page components
 */

import { generateSnowFlake } from "@/common/utils/snowflake"
import { useAsyncEffect, useEventEmitter, useMemoizedFn, useUpdateEffect } from "ahooks"
import i18next from "i18next"
import _ from "lodash"
import { useEffect, useMemo, useRef, useState } from "react"
import { useTranslation } from "react-i18next"
import { Connection, Edge, addEdge, applyEdgeChanges, applyNodeChanges } from "reactflow"
import { useNodeChangeListener } from "../context/NodeChangeListenerContext/NodeChangeListenerContext"
import { defaultEdgeConfig } from "../edges"
import { nodeManager } from "../register/node"
import { FlowType, MagicFlow } from "../types/flow"
import {
	generateStartNode,
	getExtraEdgeConfigBySourceNode,
	handleRenderProps,
	isRegisteredStartNode,
	useQuery,
} from "../utils"
import { sortByEdges, updateTargetNodesStep } from "../utils/reactflowUtils"
import useMacTouch from "./useMacTouch"
import useUndoRedo from "./useUndoRedo"
// 1. Import batch-processing hook
import useNodeBatchProcessing from "../hooks/useNodeBatchProcessing"
import { FLOW_EVENTS, flowEventBus } from "@/common/BaseUI/Select/constants"
import { useDebounceFn } from "ahooks"

export enum UpdateStepType {
	// Connect
	Connect = 1,

	// Delete edge
	DelEdge = 2,
}

type UseBaseFlowProps = {
	currentFlow?: MagicFlow.Flow
	paramsName: MagicFlow.ParamsName
}

export default function useBaseFlow({ currentFlow, paramsName }: UseBaseFlowProps) {
	const query = useQuery()

	const { t } = useTranslation()

	// Debug mode flag
	const debuggerMode = query.get("debug") === "true"

	// Current flow details
	const [flow, setFlow] = useState(null as MagicFlow.Flow | null)

	// 2. Use batch-processing hook
	const { processNodesBatch, isProcessing, progress, stopProcessing } = useNodeBatchProcessing({
		batchSize: 8,
		interval: 150,
	})

	// Current flow description
	const [description, setDescription] = useState("")

	// Current node configuration
	const [nodeConfig, setNodeConfig] = useState({} as Record<string, MagicFlow.Node>)

	// Whether to show the material panel
	const [showMaterialPanel, setShowMaterialPanel] = useState(true)

	const [selectedNodeId, setSelectedNodeId] = useState(null as null | string)

	const [selectedEdgeId, setSelectedEdgeId] = useState(null as null | string)

	const [nodes, setNodes] = useState([] as MagicFlow.Node[])
	const [edges, setEdges] = useState([] as Edge[])

	const flowInstance = useRef<HTMLDivElement>(null)

	const { nodeChangeEventListener } = useNodeChangeListener()

	const flowDesignListener = useEventEmitter<MagicFlow.FlowEventListener>()

	const { takeSnapshot, undo, redo } = useUndoRedo(debuggerMode)

	const edgesChangesRef = useRef([])

	useMacTouch()

	// // Undo
	// useKeyPress(
	// 	["meta.z", "ctrl.z"],
	// 	(e) => {
	// 		e.preventDefault()
	// 		const snapshot = undo({
	// 			nodes,
	// 			edges,
	// 			nodeConfig,
	// 		})
	// 		if (snapshot) {
	// 			setNodes(snapshot.nodes)
	// 			setEdges(snapshot.edges)
	// 			setNodeConfig(snapshot.nodeConfig)
	// 		}
	// 	},
	// 	{ exactMatch: true },
	// )

	// // Redo
	// useKeyPress(
	// 	["meta.shift.z", "ctrl.shift.z"],
	// 	(e) => {
	// 		e.preventDefault()
	// 		const snapshot = redo()
	// 		if (snapshot) {
	// 			setNodes(snapshot.nodes)
	// 			setEdges(snapshot.edges)
	// 			setNodeConfig(snapshot.nodeConfig)
	// 		}
	// 	},
	// 	{ exactMatch: true },
	// )

	const notifyNodeChange = useMemoizedFn(() => {
		nodeChangeEventListener.emit("NodeChange")
		console.log("nodeConfig", nodeConfig)
		// takeSnapshot(nodes, edges, nodeConfig)
	})

	const onNodesChange = useMemoizedFn((changes) => {
		//@ts-ignore
		return setNodes((nds) => applyNodeChanges(changes, nds))
	})

	// Debounce edge changes to reduce update churn
	const { run: debouncedEdgesChange } = useDebounceFn(
		() => {
			setEdges((eds) => applyEdgeChanges(edgesChangesRef.current, eds))
			edgesChangesRef.current = []
		},
		{ wait: 200 },
	)

	const onEdgesChange = useMemoizedFn((changes) => {
		edgesChangesRef.current = [...edgesChangesRef.current, ...changes]
		return debouncedEdgesChange()
	})

	// useUpdateEffect(() => {
	// 	console.log("nodes", nodes, edges)
	// }, [nodes, edges])

	const getDefaultFlow = useMemoizedFn(() => {
		const defaultNodes = [] as MagicFlow.Node[]

		/** If a start node is registered, add it to the list */
		if (isRegisteredStartNode()) {
			const newNode = generateStartNode(paramsName)
			defaultNodes.push(newNode)
		}

		// TODO Load node templates and insert into default nodes list

		return {
			name: i18next.t("flow.untitledFlow", { ns: "magicFlow" }),
			description: i18next.t("flow.defaultDesc", { ns: "magicFlow" }),
			enabled: false,
			edges: [],
			nodes: [...defaultNodes],
			type: FlowType.Main,
		}
	})

	const updateInternalDataByFlow = useMemoizedFn((serverFlow: MagicFlow.Flow) => {
		setDescription(serverFlow.description)

		const cacheNodes = [] as MagicFlow.Node[]
		const renderEdges = [] as Edge[]
		const cacheConfig = {} as Record<string, MagicFlow.Node>
		for (let i = 0; i < serverFlow.nodes.length; i++) {
			const node = serverFlow.nodes[i]

			// TODO Convert node schema for rendering

			/** Prepare node render props */
			handleRenderProps(node, i, paramsName)

			cacheConfig[node.id] = node

			cacheNodes.push(node)
		}

		serverFlow.edges.forEach((edge) => {
			renderEdges.push({
				...edge,
				...defaultEdgeConfig,
			})
		})

		// Initialization steps
		if (cacheNodes.length > 0) {
			updateTargetNodesStep({
				type: UpdateStepType.Connect,
				connection: {
					source: cacheNodes[0].node_id,
				} as Connection,
				nodeConfig: cacheConfig,
				nodes: cacheNodes,
				edges: renderEdges,
			})
		}
		if (isProcessing) {
			stopProcessing()
		}
		// Process nodes via the batch hook
		processNodesBatch(cacheNodes, (batchNodes) => {
			setNodes(batchNodes)
		})

		// Render edges after nodes finish rendering
		setEdges(_.cloneDeep(renderEdges))

		setNodeConfig(cacheConfig)
		setFlow({
			...serverFlow,
			nodes: [...cacheNodes],
		})
	})

	useAsyncEffect(async () => {
		const serverFlow = currentFlow || (getDefaultFlow() as MagicFlow.Flow)

		if (serverFlow?.nodes?.length === 0 && isRegisteredStartNode()) {
			const startNode = generateStartNode(paramsName)
			serverFlow?.nodes?.push?.(startNode)
		}

		updateInternalDataByFlow(serverFlow)
	}, [])

	// Sync upstream flow into local state
	useUpdateEffect(() => {
		console.log("currentFlow", currentFlow)
		if (currentFlow) {
			if (currentFlow?.nodes?.length === 0 && isRegisteredStartNode()) {
				const startNode = generateStartNode(paramsName)
				currentFlow?.nodes?.push?.(startNode)
			}
			updateInternalDataByFlow(currentFlow)
		}
	}, [currentFlow])

	const updateFlow = useMemoizedFn((flowConfig: MagicFlow.Flow) => {
		setFlow(flowConfig)
	})

	// Update node position info
	const updateNodesPosition = useMemoizedFn(
		_.debounce((nodeIds, positionMap) => {
			if (!flow) return
			const foundNode = nodes.find((n) => nodeIds.includes(n.node_id))
			if (!foundNode) return

			if (_.isEqual(foundNode.meta.position, positionMap[foundNode.node_id])) return
			foundNode.meta.position = positionMap[foundNode.node_id]
			foundNode.position = positionMap[foundNode.node_id]
		}, 100),
	)

	// Debounce node config updates to reduce churn
	const { run: debouncedUpdateConfig } = useDebounceFn(
		(node: MagicFlow.Node, previousConfig: Record<string, MagicFlow.Node>) => {
			// Functional update: only mutate the targeted node
			setNodeConfig((prevConfig) => {
				// Create a new config object while preserving other references
				const updatedConfig = { ...prevConfig }
				updatedConfig[node.id] = node
				return updatedConfig
			})

			// Notify listeners about the specific node change
			if (nodeChangeEventListener && nodeChangeEventListener.emit) {
				try {
					// Pass node ID so consumers can render selectively
					nodeChangeEventListener.emit("NodeChange")
				} catch (error) {
					// Backward compatibility for older emitters
					nodeChangeEventListener.emit("NodeChange")
					console.warn("Legacy nodeChangeEventListener in use; cannot pass node ID")
				}
			}
		},
		{ wait: 500 },
	)

	// Update node configuration
	const updateNodeConfig = useMemoizedFn(
		(node: MagicFlow.Node, originalNode?: MagicFlow.Node) => {
			const oldNodeIndex = nodes.findIndex((n) => n.id === node.id)

			// Create snapshot
			if (originalNode) {
				const snapshotNodeConfig = { ...nodeConfig }
				snapshotNodeConfig[node.id] = originalNode
				takeSnapshot(nodes, edges, snapshotNodeConfig)
			}

			// Update node
			if (oldNodeIndex !== -1) {
				const oldNode = nodes[oldNodeIndex]
				nodes.splice(oldNodeIndex, 1, {
					...oldNode,
					...node,
					meta: oldNode.meta,
					position: oldNode.position,
				})
			}

			// Let the debounced handler manage updates and notifications
			debouncedUpdateConfig(node, nodeConfig)
		},
	)

	// Trigger node
	const triggerNode = useMemo(() => {
		if (!flow || !flow.nodes || flow.nodes.length === 0) return null
		return flow.nodes[0]
	}, [flow])

	const updateNextNodeIdsByConnect = useMemoizedFn((newEdge: Edge) => {
		const { source, sourceHandle, target } = newEdge
		const node = nodeConfig[source]
		const updatedNode = _.cloneDeep(node)

		// When connecting from a branch source handle to another node
		// If the source is a branch node, update content.branches (sourceHandle corresponds to branch id)
		if (nodeManager.branchNodeIds.includes(`${node[paramsName.nodeType]}`)) {
			const branches = updatedNode?.[paramsName.params]?.branches || []
			const branchIndex = branches.findIndex(
				(branch: any) => (branch.branch_id || branch.branchId) === sourceHandle,
			)
			if (branchIndex === -1) return
			const oldBranch = branches[branchIndex]
			const newBranch = {
				...oldBranch,
				//@ts-ignore
				[paramsName.nextNodes]: [
					...(oldBranch?.[paramsName.nextNodes] || oldBranch?.[paramsName.nextNodes]),
					target,
				],
			}
			updatedNode?.[paramsName.params]?.branches?.splice(branchIndex, 1, newBranch)
		}

		// If target is missing from next-nodes list (including branch cases)
		if (!updatedNode?.[paramsName.nextNodes]?.includes(target)) {
			updatedNode?.[paramsName.nextNodes]?.push(target)
		}
		nodeConfig[source] = updatedNode

		// console.log(nodeConfig)

		updateTargetNodesStep({
			type: UpdateStepType.Connect,
			connection: newEdge,
			nodeConfig,
			nodes,
			edges: [...edges, newEdge],
		})

		setNodeConfig({ ...nodeConfig })
	})

	// When deleting a branch edge to another node
	const updateNextNodeIdsByDeleteEdge = useMemoizedFn((connection: Edge) => {
		// If source is a branch node, sourceHandle corresponds to branch id
		const { source, sourceHandle, target } = connection
		const node = nodeConfig[source]
		const updatedNode = _.cloneDeep(node)
		if (!updatedNode) return

		// For branch nodes, remove target from the branch next-nodes list
		if (nodeManager.branchNodeIds.includes(`${node[paramsName.nodeType]}`)) {
			const branches = updatedNode?.[paramsName.params]?.branches
			const branchIndex = branches?.findIndex(
				(branch: any) => (branch.branch_id || branch.branchId) === sourceHandle,
			)
			if (branchIndex === -1 || !branches) return
			const oldBranch = branches[branchIndex!]
			const newBranch = {
				...oldBranch,
				[paramsName.nextNodes]: oldBranch?.[paramsName.nextNodes]?.filter?.(
					(id: string) => id !== target,
				),
			}
			updatedNode?.[paramsName.params]?.branches?.splice(branchIndex!, 1, newBranch)
		}

		// Remove from outer nextNodes only if no branch still points to target
		const shouldRemoveFromNextNodes = !edges.some(
			(edge) =>
				edge.id !== connection.id && // Skip the edge being deleted
				edge.source === source &&
				edge.target === target,
		)

		if (shouldRemoveFromNextNodes && updatedNode?.[paramsName.nextNodes]?.includes(target)) {
			const nextNodeIndex = updatedNode[paramsName.nextNodes].indexOf(target)
			if (nextNodeIndex !== -1) {
				updatedNode[paramsName.nextNodes].splice(nextNodeIndex, 1)
			}
		}

		nodeConfig[source] = updatedNode

		updateTargetNodesStep({
			type: UpdateStepType.DelEdge,
			connection,
			nodeConfig,
			nodes,
			edges: [...edges],
		})

		setNodeConfig({ ...nodeConfig })
	})

	const onConnect = useMemoizedFn((connection) => {
		if (connection?.source === connection?.target) {
			return
		}
		notifyNodeChange()
		const sourceNode = nodeConfig[connection.source]
		const extraEdgeConfig = getExtraEdgeConfigBySourceNode(sourceNode)

		const newEdge = {
			id: generateSnowFlake(),
			source: connection.source,
			target: connection.target,
			sourceHandle: connection.sourceHandle,
			targetHandle: connection.targetHandle,
			...defaultEdgeConfig,
			...extraEdgeConfig,
		}

		// Update next-node configuration for the source node
		updateNextNodeIdsByConnect(newEdge)

		return setEdges((eds) => addEdge(newEdge, eds))
	})

	const getNewNodeIndex = useMemoizedFn(() => {
		if (nodes.length === 0) return 0
		return Math.max(...nodes.map((node) => node.step || 0)) + 1
	})

	const addNode = useMemoizedFn(
		(newNode: MagicFlow.Node | MagicFlow.Node[], newEdges?: Edge[]) => {
			const newNodes = _.castArray(newNode)
			const cloneNodes = _.cloneDeep(newNodes)
			cloneNodes.forEach((cloneNode) => {
				const i = getNewNodeIndex()
				handleRenderProps(cloneNode, i, paramsName)

				nodeConfig[cloneNode.id] = cloneNode

				// https://github.com/xyflow/xyflow/pull/2560
				// @ts-ignore
				cloneNode.zIndex = 1000

				nodes.push(cloneNode)
			})
			setNodeConfig({ ...nodeConfig })
			setNodes([...nodes])
			if (newEdges && newEdges.length > 0) {
				edges.push(...newEdges)
				setEdges([...edges])
			}
			setSelectedNodeId(cloneNodes?.[0]?.id)
			flowEventBus.emit(FLOW_EVENTS.NODE_SELECTED, cloneNodes?.[0]?.id)
			nodeChangeEventListener.emit("NodeChange")
		},
	)

	useUpdateEffect(() => {
		console.log("NODE Config ", nodeConfig)
	}, [nodeConfig])

	useUpdateEffect(() => {
		console.log("edges ", edges)
	}, [edges])

	useEffect(() => {
		try {
			if (debuggerMode) {
				/** Validate nodes: ensure all are in the main flow */
				if (debuggerMode) {
					const sortedNodes = sortByEdges(Object.values(nodeConfig), edges)

					const nextNodesMap = sortedNodes.reduce((acc, cur) => {
						if (cur[paramsName.params]?.branches?.length) {
							const branchNextNodesObject = cur[
								paramsName.params
							]?.branches?.reduce?.(
								// @ts-ignore
								(pre, cur) => {
									return {
										...pre,
										[cur?.branch_id]: cur?.next_nodes,
									}
								},
								{},
							)

							return {
								...acc,
								[cur.node_id]: branchNextNodesObject,
							}
						}
						return {
							...acc,
							[cur.node_id]: cur.next_nodes,
						}
					}, {})

					console.log("nextNodesMap", nextNodesMap)
					// @ts-ignore
					window.nextNodeMaps = nextNodesMap
				}
			}
		} catch (error) {
			console.error("Error validating nodes", error)
		}
	}, [debuggerMode, nodeConfig])

	useUpdateEffect(() => {
		if (selectedEdgeId) {
			setSelectedNodeId(null)
			flowEventBus.emit(FLOW_EVENTS.NODE_SELECTED, null)
		}
		const newEdges = edges.map((o) => {
			const strokeColor = o.id === selectedEdgeId ? "#37d0ff" : "#4d53e8"
			return {
				...o,
				markerEnd: {
					// @ts-ignore
					...o.markerEnd,
					color: strokeColor,
				},
				style: {
					...o.style,
					stroke: strokeColor,
				},
			}
		})

		setEdges(newEdges)
	}, [selectedEdgeId])

	useUpdateEffect(() => {
		if (selectedNodeId) {
			setSelectedEdgeId(null)
			flowEventBus.emit(FLOW_EVENTS.EDGE_SELECTED, null)
		}
	}, [selectedNodeId])

	// Delete nodes helper
	const deleteNodes = useMemoizedFn((ids: string[]) => {
		notifyNodeChange()
		ids.forEach((id) => {
			delete nodeConfig[id]
			const deleteEdges = edges.filter((e) => id === e.target || id === e.source)
			const leaveEdges = edges.filter((e) => id !== e.target && id !== e.source)

			// Update edge state
			setEdges(leaveEdges)

			// Update nextNodeIds
			deleteEdges.forEach((e) => updateNextNodeIdsByDeleteEdge(e))

			if (Reflect.has(nodeConfig, id)) {
				Reflect.deleteProperty(nodeConfig, id)
			}
		})

		const newNodes = nodes.filter((n) => !ids.includes(n.id))
		setNodes(newNodes)
		if (debuggerMode) {
			console.trace("Deleted nodes", ids)
		}
	})

	// Batch delete edges and sync nextNodes
	const deleteEdges = useMemoizedFn((edgesToDelete: Edge[]) => {
		if (!edgesToDelete.length) return

		notifyNodeChange()

		// 1. Update nextNodes for each edge being removed
		edgesToDelete.forEach((edge) => {
			updateNextNodeIdsByDeleteEdge(edge)
		})

		// 2. Remove edges from state
		const updatedEdges = edges.filter((edge) => !edgesToDelete.some((e) => e.id === edge.id))
		setEdges(updatedEdges)
	})

	return {
		flow,
		setFlow,
		updateFlow,
		triggerNode,
		updateNodesPosition,
		updateNodeConfig,
		nodes,
		edges,
		onNodesChange,
		onEdgesChange,
		onConnect,
		nodeConfig,
		addNode,
		selectedNodeId,
		setSelectedNodeId,
		setNodes,
		setEdges,
		selectedEdgeId,
		setSelectedEdgeId,
		updateNextNodeIdsByDeleteEdge,
		updateNextNodeIdsByConnect,
		description,
		flowInstance,
		debuggerMode,
		getNewNodeIndex,
		showMaterialPanel,
		setShowMaterialPanel,
		flowDesignListener,
		deleteNodes,
		deleteEdges,
		setNodeConfig,
		notifyNodeChange,
		isProcessing,
		progress,
		edgeLength: 0,
		processNodesBatch,
	}
}
