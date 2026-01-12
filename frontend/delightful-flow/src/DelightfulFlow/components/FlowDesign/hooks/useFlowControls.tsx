/**
 * Handle node layout-related operations
 */
import { fitViewRatio } from "@/DelightfulFlow/constants"
import { useExternalConfig } from "@/DelightfulFlow/context/ExternalContext/useExternal"
import {
	useFlowEdges,
	useFlowNodes,
	useNodeConfig,
	useNodeConfigActions,
} from "@/DelightfulFlow/context/FlowContext/useFlow"
import { useNodes } from "@/DelightfulFlow/context/NodesContext/useNodes"
import { useResize } from "@/DelightfulFlow/context/ResizeContext/useResize"
import { EdgeModelTypes, defaultEdgeConfig } from "@/DelightfulFlow/edges"
import { DelightfulFlow } from "@/DelightfulFlow/types/flow"
import { handleRenderProps, isRegisteredStartNode } from "@/DelightfulFlow/utils"
import { generatePasteNodesAndEdges, getLayoutElements } from "@/DelightfulFlow/utils/reactflowUtils"
import {
	IconCapture,
	IconChevronDown,
	IconDeviceIpadHorizontal,
	IconDragDrop,
	IconLayout,
	IconLine,
	IconLock,
	IconLockOpen,
	IconMouse,
	IconRuler,
	IconVectorSpline,
	IconZoomIn,
	IconZoomOut,
} from "@tabler/icons-react"
import { useDebounceFn, useMemoizedFn, useResetState, useUpdateEffect } from "ahooks"
import { Flex, Popover, message } from "antd"
import i18next from "i18next"
import _ from "lodash"
import React, { useEffect, useMemo, useState } from "react"
import { useTranslation } from "react-i18next"
import { Edge, useReactFlow, useStoreApi } from "reactflow"
import InteractionSelect, { Interactions } from "../components/InteractionSelect"
import useInteraction from "../components/InteractionSelect/useInteraction"
import styles from "../index.module.less"

export const controlDuration = 200

// Define the left offset for the first node during paste
const PASTE_LEFT_OFFSET = 200

interface FlowLayoutProps {
	setShowParamsComp: React.Dispatch<React.SetStateAction<boolean>>
	flowInstance: any
}

export default function useFlowLayout({ setShowParamsComp, flowInstance }: FlowLayoutProps) {
	const { t } = useTranslation()

	const { setEdges, edges } = useFlowEdges()
	const { nodeConfig } = useNodeConfig()
	const { setNodeConfig, notifyNodeChange } = useNodeConfigActions()
	const { processNodesBatch } = useFlowNodes()

	const { setNodes, nodes } = useNodes()

	const { zoomIn, zoomOut, fitView, getZoom, setViewport, getViewport } = useReactFlow()

	const { layoutOnMount, paramsName } = useExternalConfig()

	const [currentZoom, setCurrentZoom] = useState(getZoom())

	const { windowSize } = useResize()

	const [showMinMap, setShowMinMap] = useState(false)

	// Whether viewport is locked
	const [isLock, setIsLock] = useState(false)

	// Whether edge type is bezier curve
	const [isBezier, setIsBezier] = useState(true)

	// Whether layout optimization can be performed
	const [canLayout, setCanLayout, resetCanLayout] = useResetState(true)

	const store = useStoreApi()

	const [isMountLayout, setIsMountLayout] = useState(false)

	// Data before layout
	const [lastLayoutData, setLastLayoutData, resetLastLayoutData] = useResetState({
		undoable: false,
		nodes: [] as DelightfulFlow.Node[],
		edges: [] as Edge[],
	})

	const { interaction, onInteractionChange, openInteractionSelect, setOpenInteractionSelect } =
		useInteraction()

	const updateConfigPosition = useMemoizedFn((layoutNodes: DelightfulFlow.Node[]) => {
		layoutNodes.forEach((n) => {
			const curNodeConfig = nodeConfig[n.node_id]
			_.set(curNodeConfig, ["meta", "position"], n.position)
			_.set(curNodeConfig, ["position"], n.position)
		})
	})

	const onLayout = useMemoizedFn((direction = "LR", duration = controlDuration) => {
		// If node positions have not changed, no need to layout
		if (!canLayout) return nodes

		// if is undo
		if (lastLayoutData.undoable) {
			setNodes([...lastLayoutData.nodes])
			setEdges([...lastLayoutData.edges])
			updateConfigPosition(lastLayoutData.nodes)

			resetLastLayoutData()
			setCanLayout(true)
			return lastLayoutData.nodes
		} else {
			const beforeNodes = _.cloneDeep(nodes)
			const beforeLayoutData = {
				undoable: true,
				nodes: beforeNodes,
				edges: _.cloneDeep(edges),
			}

			const { nodes: layoutNodes, edges: layoutEdges } = getLayoutElements(
				nodes,
				edges,
				direction,
				paramsName,
			)

			if (_.isEqual(layoutNodes, beforeNodes)) {
				setCanLayout(false)
				return nodes
			}
			updateConfigPosition(layoutNodes)

			// Record data before layout
			setLastLayoutData(beforeLayoutData)

			setNodes([...layoutNodes])
			setEdges([...layoutEdges])
			return layoutNodes
		}

		// Perform view auto-fit after layout completion to avoid nodes leaving user's view
		// setTimeout(() => {
		// 	fitView({ includeHiddenNodes: true, duration })
		// 	setCurrentZoom(getZoom())
		// }, 0)
	})

	const onFitView = useMemoizedFn(() => {
		// When exceeding threshold, use skeleton rendering to avoid lag
		if (nodes.length >= fitViewRatio) {
			setShowParamsComp(false)
		}
		fitView({ includeHiddenNodes: true, duration: controlDuration })
		setTimeout(() => {
			setCurrentZoom(getZoom())
		}, controlDuration)
	})

	const onLock = useMemoizedFn(() => {
		store.setState({
			nodesDraggable: isLock,
			nodesConnectable: isLock,
			elementsSelectable: isLock,
		})
		setIsLock(!isLock)
	})

	const onZoomIn = useMemoizedFn((duration?: number) => {
		zoomIn({ duration: duration || controlDuration })
		setTimeout(() => {
			setCurrentZoom(getZoom())
		}, duration || controlDuration)
	})

	const onZoomOut = useMemoizedFn(() => {
		zoomOut({ duration: controlDuration })
		setTimeout(() => {
			setCurrentZoom(getZoom())
		}, controlDuration)
	})

	const { run: onMove } = useDebounceFn(
		useMemoizedFn(() => {
			setCurrentZoom(getZoom())
		}),
		{
			wait: 500,
		},
	)

	const onEdgeTypeChange = useMemoizedFn(() => {
		const currentIsBezier = !isBezier
		setIsBezier(currentIsBezier)

		// Modify default config type
		defaultEdgeConfig.type = currentIsBezier
			? EdgeModelTypes.CommonEdge
			: EdgeModelTypes.SmoothStep

		const newEdges = edges.map((o) => ({
			...o,
			...defaultEdgeConfig,
		}))

		setEdges([...newEdges])
	})

	// Add helper lines control state in hooks status section
	const [helperLinesEnabled, setHelperLinesEnabled] = useState(false)

	// Operation bar list
	const controlItemGroups = useMemo(() => {
		return [
			[
				{
					icon: (
						<Popover
							content={
								<InteractionSelect
									interaction={interaction}
									onInteractionChange={onInteractionChange}
								/>
							}
							showArrow={false}
							open={openInteractionSelect}
						>
							<Flex
								className={styles.interaction}
								align="center"
								onClick={(e) => {
									e.stopPropagation()
									setOpenInteractionSelect(!openInteractionSelect)
								}}
							>
								{interaction === Interactions.Mouse ? (
									<IconMouse stroke={1} />
								) : (
									<IconDeviceIpadHorizontal stroke={1} />
								)}
								<IconChevronDown stroke={1} />
							</Flex>
						</Popover>
					),
					callback: () => {
						setOpenInteractionSelect(!openInteractionSelect)
					},
					tooltips: (
						<Flex justify="space-between" gap={4}>
							<span>
								{interaction === Interactions.Mouse
									? i18next.t("flow.mouseFriendly", { ns: "delightfulFlow" })
									: i18next.t("flow.touchpadFriendly", { ns: "delightfulFlow" })}
							</span>
							<div className={styles.shortCutsBlock}>
								{navigator.platform.indexOf("Mac") > -1 ? "⌘" : "Ctrl"}
							</div>
							<div className={styles.shortCutsBlock}>I</div>
						</Flex>
					),
				},
			],
			[
				{
					icon: <IconZoomOut stroke={1} />,
					callback: () => onZoomOut(),
					tooltips: (
						<Flex justify="space-between" gap={4}>
							<span>{i18next.t("flow.zoomOut", { ns: "delightfulFlow" })}</span>
							<div className={styles.shortCutsBlock}>
								{navigator.platform.indexOf("Mac") > -1 ? "⌘" : "Ctrl"}
							</div>
							<div className={styles.shortCutsBlock}>-</div>
						</Flex>
					),
				},

				{
					icon: <div className={styles.scaleWrap}>{Math.ceil(currentZoom * 100)}%</div>,
					callback: () => {},
					isNotIcon: true,
				},
				{
					icon: <IconZoomIn stroke={1} />,
					callback: () => onZoomIn(),
					tooltips: (
						<Flex justify="space-between" gap={4}>
							<span>{i18next.t("flow.zoomIn", { ns: "delightfulFlow" })}</span>
							<div className={styles.shortCutsBlock}>
								{navigator.platform.indexOf("Mac") > -1 ? "⌘" : "Ctrl"}
							</div>
							<div className={styles.shortCutsBlock}>+</div>
						</Flex>
					),
				},
			],
			[
				{
					icon: (
						<IconLayout
							stroke={1}
							className={lastLayoutData.undoable ? styles.undoLayoutItem : ""}
						/>
					),
					callback: () => onLayout("LR"),
					tooltips: lastLayoutData.undoable ? (
						i18next.t("flow.recallLayout", { ns: "delightfulFlow" })
					) : (
						<Flex justify="space-between" gap={4}>
							<span>{i18next.t("flow.layout", { ns: "delightfulFlow" })}</span>
							<div className={styles.shortCutsBlock}>
								{navigator.platform.indexOf("Mac") > -1 ? "⌘" : "Ctrl"}
							</div>
							<div className={styles.shortCutsBlock}>P</div>
						</Flex>
					),
				},
				{
					icon: <IconCapture stroke={1} />,
					callback: () => onFitView(),
					tooltips: (
						<Flex justify="space-between" gap={4}>
							<span>{i18next.t("flow.adaptView", { ns: "delightfulFlow" })}</span>
							<div className={styles.shortCutsBlock}>
								{navigator.platform.indexOf("Mac") > -1 ? "⌘" : "Ctrl"}
							</div>
							<div className={styles.shortCutsBlock}>A</div>
						</Flex>
					),
				},
			],
			[
				{
					icon: isLock ? (
						<IconLock stroke={1} color="#FF7D00" />
					) : (
						<IconLockOpen stroke={1} />
					),
					callback: () => onLock(),
					tooltips: (
						<Flex justify="space-between" gap={4}>
							<span>
								{isLock
									? i18next.t("flow.unlockView", { ns: "delightfulFlow" })
									: i18next.t("flow.lockView", { ns: "delightfulFlow" })}
							</span>
							<div className={styles.shortCutsBlock}>
								{navigator.platform.indexOf("Mac") > -1 ? "⌘" : "Ctrl"}
							</div>
							<div className={styles.shortCutsBlock}>L</div>
						</Flex>
					),
					isLock,
				},
			],
			[
				{
					icon: isBezier ? <IconVectorSpline stroke={1} /> : <IconLine stroke={1} />,
					callback: () => onEdgeTypeChange(),
					tooltips: (
						<Flex justify="space-between" gap={4}>
							<span>
								{isBezier
									? i18next.t("flow.changeToPolygonLine", { ns: "delightfulFlow" })
									: i18next.t("flow.changeToSmoothLine", { ns: "delightfulFlow" })}
							</span>
							<div className={styles.shortCutsBlock}>
								{navigator.platform.indexOf("Mac") > -1 ? "⌘" : "Ctrl"}
							</div>
							<div className={styles.shortCutsBlock}>M</div>
						</Flex>
					),
				},
			],
			[
				{
					icon: <IconDragDrop stroke={1} />,
					callback: () => {
						setShowMinMap(!showMinMap)
					},
					tooltips: (
						<Flex justify="space-between" gap={4}>
							<span>
								{showMinMap
									? i18next.t("flow.closeMinMap", { ns: "delightfulFlow" })
									: i18next.t("flow.openMinMap", { ns: "delightfulFlow" })}
							</span>
							<div className={styles.shortCutsBlock}>
								{navigator.platform.indexOf("Mac") > -1 ? "⌘" : "Ctrl"}
							</div>
							<div className={styles.shortCutsBlock}>D</div>
						</Flex>
					),
					showMinMap,
				},
			],
			[
				{
					icon: helperLinesEnabled ? (
						<IconRuler stroke={1} color="#FF7D00" />
					) : (
						<IconRuler stroke={1} />
					),
					callback: () => setHelperLinesEnabled(!helperLinesEnabled),
					tooltips: (
						<Flex justify="space-between" gap={4}>
							<span>
								{helperLinesEnabled
									? i18next.t("flow.disableHelperLines", {
											ns: "delightfulFlow",
										defaultValue: "Disable Helper Lines",
								  })
								: i18next.t("flow.enableHelperLines", {
										ns: "delightfulFlow",
										defaultValue: "Enable Helper Lines",
									  })}
							</span>
							<div className={styles.shortCutsBlock}>
								{navigator.platform.indexOf("Mac") > -1 ? "⌘" : "Ctrl"}
							</div>
							<div className={styles.shortCutsBlock}>H</div>
						</Flex>
					),
					helperLinesEnabled,
				},
			],
		]
	}, [
		currentZoom,
		isBezier,
		isLock,
		lastLayoutData.undoable,
		onEdgeTypeChange,
		onFitView,
		onLayout,
		onLock,
		onZoomIn,
		onZoomOut,
		showMinMap,
		openInteractionSelect,
		interaction,
		onInteractionChange,
		helperLinesEnabled,
	])

	useUpdateEffect(() => {
		if (isMountLayout) return
		if (nodes.length) {
			let node = nodes[0]

			// Only when node has width is it truly rendered by reactflow, then layout can be performed
			if (!isMountLayout && node.width) {
				if (isRegisteredStartNode()) {
					if (nodes.length !== 1) setIsMountLayout(true)
				} else {
					setIsMountLayout(true)
				}
				/** Determine whether automatic layout is needed on initialization */
				if (layoutOnMount) {
					const layoutNodes = onLayout("LR", 0)
					resetLastLayoutData()
					node = layoutNodes?.[0]
				}
				/** Set canvas center to first node center */
				setViewport({
					x: 100,
					// @ts-ignore
					y: -node?.position?.y - node?.height / 2 + windowSize.height / 2,
					zoom: 1,
				})
			}
		}
	}, [nodes, edges, isMountLayout, setViewport, windowSize, layoutOnMount])

	// Replace all shortcuts with useEffect and low-level event listener
	useEffect(() => {
		const handleKeyDown = (e: KeyboardEvent) => {
			// Check if element is in editable area, if so don't handle shortcuts
			const activeElement = document.activeElement
			if (
				activeElement?.tagName === "INPUT" ||
				activeElement?.tagName === "TEXTAREA" ||
				// @ts-ignore
				activeElement?.isContentEditable
			) {
				return
			}

			// Detect if Cmd (Mac) or Ctrl (Windows) is pressed
			const isMetaPressed = e.metaKey || e.ctrlKey

			if (isMetaPressed) {
				switch (e.key.toLowerCase()) {
					// Zoom out canvas: Cmd/Ctrl + -
					case "-":
						e.preventDefault()
						e.stopPropagation()
						onZoomOut()
						break

					// Zoom in canvas: Cmd/Ctrl + = or Cmd/Ctrl + +
					case "=":
					case "+":
						e.preventDefault()
						e.stopPropagation()
						onZoomIn()
						break

					// Optimize layout: Cmd/Ctrl + P
					case "p":
						e.preventDefault()
						e.stopPropagation()
						onLayout("LR")
						break

					// Fit view: Cmd/Ctrl + A
					case "a":
						e.preventDefault()
						e.stopPropagation()
						onFitView()
						break

					// Lock/unlock view: Cmd/Ctrl + L
					case "l":
						e.preventDefault()
						e.stopPropagation()
						onLock()
						break

					// Toggle line style: Cmd/Ctrl + M
					case "m":
						e.preventDefault()
						e.stopPropagation()
						onEdgeTypeChange()
						break

					// Toggle interaction mode: Cmd/Ctrl + I
					case "i":
						e.preventDefault()
						e.stopPropagation()
						onInteractionChange(
							interaction === Interactions.Mouse
								? Interactions.TouchPad
								: Interactions.Mouse,
						)
						break

					// Show/hide minimap: Cmd/Ctrl + D
					case "d":
						e.preventDefault()
						e.stopPropagation()
						setShowMinMap(!showMinMap)
						break
				}
			}
		}

		// Add event listener to document instead of specific element to ensure capture regardless of focus
		// Use capture: true to ensure capture at earliest stage of event propagation chain
		document.addEventListener("keydown", handleKeyDown, { capture: true })

		return () => {
			document.removeEventListener("keydown", handleKeyDown, { capture: true })
		}
	}, [
		onZoomIn,
		onZoomOut,
		onLayout,
		onFitView,
		onLock,
		onEdgeTypeChange,
		interaction,
		onInteractionChange,
		showMinMap,
	])

	// Add helper lines shortcut support in useKeyboardShortcuts or related keyboard shortcut handling section
	useEffect(() => {
		const handleKeyDown = (event: KeyboardEvent) => {
			// Avoid triggering shortcuts in input fields
			if (
				document.activeElement?.tagName === "INPUT" ||
				document.activeElement?.tagName === "TEXTAREA"
			) {
				return
			}

			// Ctrl+H or Command+H toggles helper lines functionality
			if ((event.ctrlKey || event.metaKey) && event.key === "h") {
				event.preventDefault()
				setHelperLinesEnabled(!helperLinesEnabled)
			}

			// Other shortcut handling...
		}

		document.addEventListener("keydown", handleKeyDown)
		return () => {
			document.removeEventListener("keydown", handleKeyDown)
		}
	}, [helperLinesEnabled, setHelperLinesEnabled])

	const handlePaste = useMemoizedFn((e: any) => {
		// Get active element, check if in input field or editable area
		const activeElement = document.activeElement
		if (
			activeElement?.tagName === "INPUT" ||
			activeElement?.tagName === "TEXTAREA" ||
			// @ts-ignore
			activeElement?.isContentEditable
		) {
			return
		}

		// Ensure mouse is on ReactFlow canvas or its children
		const reactFlowEl =
			flowInstance?.current?.querySelector(".react-flow") ||
			document.querySelector(".react-flow")
		if (!reactFlowEl) return

		// Check if click position is within ReactFlow area
		const rect = reactFlowEl.getBoundingClientRect()
		if (
			e.clientX &&
			(e.clientX < rect.left ||
				e.clientX > rect.right ||
				e.clientY < rect.top ||
				e.clientY > rect.bottom)
		) {
			return
		}

		// Get current viewport information
		const { x: viewX, y: viewY, zoom } = getViewport()

		// Calculate view center coordinates in flow diagram
		const viewCenterX = (rect.width / 2 - viewX) / zoom
		const viewCenterY = (rect.height / 2 - viewY) / zoom

		// Calculate view left side coordinates in flow diagram
		const viewLeftX = (0 - viewX) / zoom

		navigator.clipboard.readText().then((text) => {
			try {
				const json = JSON.parse(text)
				if (json?.nodes && json?.edges) {
					const cacheConfig = {} as Record<string, DelightfulFlow.Node>
					const cacheNodes = [] as DelightfulFlow.Node[]
					const { pasteEdges, pasteNodes } = generatePasteNodesAndEdges(
						nodeConfig,
						json.nodes,
						json.edges,
						paramsName,
					)

					// If node array is empty, don't handle
					if (pasteNodes.length === 0) return

					// Extract first node position of original node group as reference
					const firstNodeOriginalPos = {
						x: pasteNodes[0].position.x,
						y: pasteNodes[0].position.y,
					}

					for (let i = 0; i < pasteNodes.length; i++) {
						let node = pasteNodes[i]
						delete node.data.icon
						node.selected = false

						// Calculate offset relative to first node
						const deltaX = node.position.x - firstNodeOriginalPos.x
						const deltaY = node.position.y - firstNodeOriginalPos.y

						// Get first node width and height
						const firstNodeWidth = pasteNodes[0].width || 0
						const firstNodeHeight = pasteNodes[0].height || 0

						// Set new position:
						// Horizontal: based on current view left side plus fixed offset
						// Vertical: view center minus half node height for vertical centering
						// Note: apply same offset to all nodes to maintain relative positions
						node.meta = {
							position: {
								x: viewLeftX + PASTE_LEFT_OFFSET + deltaX,
								y: viewCenterY - firstNodeHeight / 2 + deltaY,
							},
						}

						/** Handle node rendering fields */
						handleRenderProps(node, i, paramsName)

						cacheConfig[node.id] = node
						cacheNodes.push(node)
					}
					setNodeConfig({
						...nodeConfig,
						...cacheConfig,
					})
					// Only enter progressive load strategy if node is empty
					if (nodes.length === 0) {
						processNodesBatch([...nodes, ...cacheNodes], (nodes) => {
							setNodes(nodes)
						})
					} else {
						setNodes([...nodes, ...cacheNodes])
					}
					setEdges([...edges, ...pasteEdges])
					message.success(i18next.t("common.pasteSuccess", { ns: "delightfulFlow" }))
					notifyNodeChange?.()
				}
			} catch (e) {
				console.log("Does not meet format requirements", e)
			}
		})
	})

	useEffect(() => {
		// Listen to paste event at document level to ensure all paste operations are captured
		document.addEventListener("paste", handlePaste, { capture: true })

		return () => {
			document.removeEventListener("paste", handlePaste, { capture: true })
		}
	}, [handlePaste])

	return {
		controlItemGroups,
		lastLayoutData,
		resetLastLayoutData,
		resetCanLayout,
		layout: onLayout,
		setIsMountLayout,
		showMinMap,
		currentZoom,
		onMove,
		interaction,
		onFitView,
		onZoomIn,
		onZoomOut,
		onEdgeTypeChange,
		onLock,
		onInteractionChange,
		helperLinesEnabled,
	}
}

