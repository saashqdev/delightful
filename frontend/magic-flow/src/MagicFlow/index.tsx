import ErrorContent from "@/common/BaseUI/ErrorComponent/ErrorComponent"
import "antd/dist/reset.css"
import clsx from "clsx"
import _ from "lodash"
import React, { useImperativeHandle, useMemo } from "react"
import { ErrorBoundary } from "react-error-boundary"
import { Edge, ReactFlowProvider } from "reactflow"
import "reactflow/dist/style.css"
import FlowDesign from "./components/FlowDesign"
import FlowHeader from "./components/FlowHeader"
import FlowMaterialPanel from "./components/FlowMaterialPanel"
import { defaultParamsName, prefix } from "./constants"
import { ExternalProvider } from "./context/ExternalContext/Provider"
import { FlowProvider } from "./context/FlowContext/Provider"
import { MagicFlowProvider } from "./context/MagicFlowContext/Provider"
import { NodesProvider } from "./context/NodesContext/Provider"
import { ResizeProvider } from "./context/ResizeContext/Provider"
import useBaseFlow from "./hooks/useBaseFlow"
import useResize from "./hooks/useResize"
import "./index.css"
import styles from "./index.module.less"
import { MagicFlow } from "./types/flow"
import { CLASSNAME_PREFIX } from "@/common/constants"
import { ConfigProvider } from "antd"

export * from "./register/node"

type MagicFlowProps = {
	/** Upstream flow */
	flow?: MagicFlow.Flow
	/** Header right-side action buttons */
	header?: {
		buttons?: React.ReactElement
		backIcon?: React.ReactElement
	}
	/** Show the flow header bar */
	showHeader?: boolean
	/** Custom toolbar */
	nodeToolbar?: {
		list: Array<{
			icon: () => React.ReactElement
			tooltip?: string
		}>
		mode?: "append" | "replaceAll"
	}
	/** Custom left material panel header */
	materialHeader?: React.ReactElement
	/** Custom node config field names */
	customParamsName?: Partial<MagicFlow.ParamsName>
	/** Keys to omit from node parameters */
	omitNodeKeys?: string[]
	/** Enable partial rendering */
	onlyRenderVisibleElements?: boolean
	/** Auto layout on mount */
	layoutOnMount?: boolean
	/** Allow debug mode */
	allowDebug?: boolean
	/** Show extra flow info */
	showExtraFlowInfo?: boolean
	/** Flow instance reference */
	flowInteractionRef?: React.MutableRefObject<any>
}

export type MagicFlowInstance = {
	/** Get internal flow data **/
	getFlow: () => MagicFlow.Flow
	/** Add a node */
	addNode: (node: MagicFlow.Node) => void
	/** Set all nodes */
	setNodes: (nodes: MagicFlow.Node[]) => void
	/** Set node configuration */
	setNodeConfig: (nodeConfig: Record<string, MagicFlow.Node>) => void
	/** Update node configuration */
	updateNodeConfig: (nodeConfig: Record<string, MagicFlow.Node>) => void
	/** Delete nodes */
	deleteNodes: (nodeIds: string[]) => void
	/** Update node positions */
	updateNodesPosition: (nodeIds: string[], position: { x: number; y: number }) => void
	/** Update node connections on connect */
	updateNextNodeIdsByConnect: (nodeId: string, nextNodeIds: string[]) => void
	/** Update node connections on delete */
	updateNextNodeIdsByDeleteEdge: (nodeId: string, nextNodeIds: string[]) => void
	/** Set the selected node */
	setSelectedNodeId: (nodeId: string) => void
	/** Get node configuration */
	getNodeConfig: () => Record<string, MagicFlow.Node>
	/** Get edges */
	getEdges: () => Edge[]
}

// Wrap content with memo to avoid redundant renders
const FlowContent = React.memo(
	({ showHeader, className }: { showHeader: boolean; className: string }) => {
		return (
			<div className={className}>
				{showHeader && <FlowHeader />}
				<div className={clsx(styles.content, `${prefix}content`)}>
					<FlowMaterialPanel />
					<FlowDesign />
				</div>
			</div>
		)
	},
)

const MagicFlowComponent = React.forwardRef(
	(
		{
			flow: currentFlow,
			header,
			showHeader = true,
			nodeToolbar,
			materialHeader,
			customParamsName,
			omitNodeKeys = [],
			onlyRenderVisibleElements = true,
			layoutOnMount = true,
			allowDebug = false,
			showExtraFlowInfo = true,
			flowInteractionRef,
		}: MagicFlowProps,
		ref,
	) => {
		const { windowSize } = useResize()

		const paramsName = useMemo(() => {
			return { ...defaultParamsName, ...(customParamsName || {}) }
		}, [customParamsName])

		const {
			flow,
			updateFlow,
			triggerNode,
			updateNodesPosition,
			nodes,
			setNodes,
			edges,
			onNodesChange,
			onEdgesChange,
			onConnect,
			nodeConfig,
			addNode,
			selectedNodeId,
			setSelectedNodeId,
			selectedEdgeId,
			setSelectedEdgeId,
			setEdges,
			updateNextNodeIdsByDeleteEdge,
			updateNodeConfig,
			description,
			flowInstance,
			updateNextNodeIdsByConnect,
			debuggerMode,
			getNewNodeIndex,
			showMaterialPanel,
			setShowMaterialPanel,
			flowDesignListener,
			deleteNodes,
			setNodeConfig,
			notifyNodeChange,
			deleteEdges,
			isProcessing,
			progress,
			processNodesBatch,
		} = useBaseFlow({ currentFlow, paramsName })

		useImperativeHandle(ref, () => ({
			getFlow: () => {
				return {
					...flow,
					nodes: nodes.map((n) => {
						const node = nodeConfig?.[n.node_id] || {}
						const omitKeysNode = _.omit(node, omitNodeKeys)
						return omitKeysNode
					}),
					edges,
					description,
				}
			},
			getNodeConfig: () => {
				return { ...nodeConfig }
			},
			addNode,
			setNodes,
			setNodeConfig,
			updateNodeConfig,
			deleteNodes,
			updateNodesPosition,
			updateNextNodeIdsByConnect,
			updateNextNodeIdsByDeleteEdge,
			setSelectedNodeId,
			getEdges: () => {
				return edges
			},
		}))

		// 使用useMemo缓存flowProviderProps以减少重新渲染
		const flowProviderProps = useMemo(
			() => ({
				flow,
				edges,
				onEdgesChange,
				onConnect,
				updateFlow,
				nodeConfig,
				setNodeConfig,
				updateNodeConfig,
				addNode,
				deleteNodes,
				updateNodesPosition,
				selectedNodeId,
				setSelectedNodeId,
				triggerNode,
				selectedEdgeId,
				setSelectedEdgeId,
				setEdges,
				updateNextNodeIdsByDeleteEdge,
				updateNextNodeIdsByConnect,
				description,
				flowInstance,
				debuggerMode,
				getNewNodeIndex,
				showMaterialPanel,
				setShowMaterialPanel,
				flowDesignListener,
				notifyNodeChange,
				deleteEdges,
				processNodesBatch,
			}),
			[
				flow,
				edges,
				onEdgesChange,
				onConnect,
				updateFlow,
				nodeConfig,
				setNodeConfig,
				updateNodeConfig,
				addNode,
				deleteNodes,
				updateNodesPosition,
				selectedNodeId,
				setSelectedNodeId,
				triggerNode,
				selectedEdgeId,
				setSelectedEdgeId,
				setEdges,
				updateNextNodeIdsByDeleteEdge,
				updateNextNodeIdsByConnect,
				description,
				flowInstance,
				debuggerMode,
				getNewNodeIndex,
				showMaterialPanel,
				setShowMaterialPanel,
				flowDesignListener,
				notifyNodeChange,
				deleteEdges,
				processNodesBatch,
			],
		)

		// 使用useMemo缓存externalProviderProps以减少重新渲染
		const externalProviderProps = useMemo(
			() => ({
				header,
				nodeToolbar,
				materialHeader,
				paramsName,
				onlyRenderVisibleElements,
				layoutOnMount,
				allowDebug,
				showExtraFlowInfo,
				flowInteractionRef,
				omitNodeKeys,
			}),
			[
				header,
				nodeToolbar,
				materialHeader,
				paramsName,
				onlyRenderVisibleElements,
				layoutOnMount,
				allowDebug,
				showExtraFlowInfo,
				flowInteractionRef,
				omitNodeKeys,
			],
		)

		// 使用useMemo缓存wrapper样式类名
		const wrapperClassName = useMemo(() => clsx(styles.magicFlow, `${prefix}magic-flow`), [])

		return (
			<ErrorBoundary
				fallbackRender={({ error }) => {
					console.log("error", error)
					return <ErrorContent />
				}}
			>
				<ConfigProvider prefixCls={CLASSNAME_PREFIX}>
					<MagicFlowProvider>
						<ExternalProvider {...externalProviderProps}>
							<ResizeProvider windowSize={windowSize}>
								<ReactFlowProvider>
									<NodesProvider
										nodes={nodes}
										setNodes={setNodes}
										onNodesChange={onNodesChange}
									>
										<FlowProvider {...flowProviderProps}>
											<FlowContent
												showHeader={showHeader}
												className={wrapperClassName}
											/>
											{/* 分批加载指示器 */}
											{isProcessing && (
												<div className={styles.batchLoadingIndicator}>
													加载中: {progress.current}/{progress.total} 批次
												</div>
											)}
										</FlowProvider>
									</NodesProvider>
								</ReactFlowProvider>
							</ResizeProvider>
						</ExternalProvider>
					</MagicFlowProvider>
				</ConfigProvider>
			</ErrorBoundary>
		)
	},
)

export default MagicFlowComponent
