import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { RoutePath } from "@/const/routes"
import type { DelightfulFlow } from "@delightful/delightful-flow/dist/DelightfulFlow/types/flow"
import { IconEdit, IconTrash, IconEye, IconKey } from "@tabler/icons-react"
import {
	useMemoizedFn,
	useResetState,
	useUpdateEffect,
	useBoolean,
	useSize,
	useEventEmitter,
} from "ahooks"
import { message, Popconfirm } from "antd"
import { useNavigate } from "@/opensource/hooks/useNavigate"
import type { Flow, FlowTool } from "@/types/flow"
import { FlowRouteType, FlowType, VectorKnowledge } from "@/types/flow"
import { useTranslation } from "react-i18next"
import { useMemo, useRef } from "react"
import { replaceRouteParams } from "@/utils/route"
import { openModal } from "@/utils/react"
import DeleteDangerModal from "@/opensource/components/business/DeleteDangerModal"
import useSWRInfinite from "swr/infinite"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import { FlowApi, KnowledgeApi } from "@/apis"
import { hasAdminRight, hasEditRight, hasViewRight } from "../../components/AuthControlButton/types"
import { useDebounceSearch } from "../../hooks/useDebounceSearch"
import type { Knowledge } from "@/types/knowledge"
import { knowledgeType } from "@/opensource/pages/vectorKnowledge/constant"
import { useFlowStore } from "@/opensource/stores/flow"
import { pick } from "lodash-es"
interface FlowListHooksProps {
	flowType: FlowRouteType
}

export interface FlowWithTools extends DelightfulFlow.Flow {
	tools?: FlowTool.Tool[]
}

interface KeyProp {
	pageIndex: number
	previousPageData: { list: DelightfulFlow.Flow[]; page: number; total: number } | null
	type: FlowRouteType
	name: string
	size: number
}

type CurrentDataType = any[] | undefined

export default function useFlowList({ flowType }: FlowListHooksProps) {
	const { t: globalT } = useTranslation()
	const navigate = useNavigate()

	const { t } = useTranslation("interface")

	const [keyword, setKeyword, resetKeyword] = useResetState("")

	const [vkSearchType, setVkSearchType, resetVkSearchType] =
		useResetState<VectorKnowledge.SearchType>(VectorKnowledge.SearchType.All)

	const [groupId, setGroupId, resetGroupId] = useResetState("")

	const [currentTool, setCurrentTool, resetCurrentTool] = useResetState<
		FlowTool.Tool | Flow.Mcp.ListItem
	>({} as FlowTool.Tool)
	const [currentFlow, setCurrentFlow, resetCurrentFlow] = useResetState<
		FlowWithTools | Knowledge.KnowledgeItem | Flow.Mcp.Detail
	>({} as FlowWithTools)

	const isMcp = useMemo(() => flowType === FlowRouteType.Mcp, [flowType])
	const { updateUseableToolSets } = useFlowStore()

	const mcpEventListener = useEventEmitter<string>()

	const scrollRef = useRef<HTMLDivElement | null>(null)
	const scrollSize = useSize(scrollRef)
	const pageSize = useMemo(() => {
		if (scrollSize?.height) {
			const size = Math.floor(scrollSize.height / 60)
			return size % 2 === 0 ? size : size + 1
		}
		return 10
	}, [scrollSize?.height])

	// Dynamically select the API fetcher function
	const fetcher = useMemoizedFn(
		async (key: {
			type: FlowRouteType
			name: string
			page: number
			pageSize: number
			searchType: VectorKnowledge.SearchType
		}) => {
			const { type, searchType, ...params } = key
			if (type === FlowRouteType.Sub) {
				const response = await FlowApi.getFlowList({ type: FlowType.Sub, ...params })
				const { list, page, total } = response
				return { list, page, total }
			}
			if (type === FlowRouteType.Tools) {
				const response = await FlowApi.getToolList(params)
				const { list, page, total } = response
				return { list, page, total }
			}
			if (type === FlowRouteType.VectorKnowledge) {
				const response = await KnowledgeApi.getKnowledgeList({
					...params,
					searchType,
				})
				const { list, page, total } = response
				return { list, page, total }
			}
			if (type === FlowRouteType.Mcp) {
				const response = await FlowApi.getMcpList(params)
				const { list, page, total } = response
				return { list, page, total }
			}
			return { list: [], page: 0, total: 0 }
		},
	)

	const getKey = ({ pageIndex, previousPageData, type, name, size }: KeyProp) => {
		if (previousPageData && !previousPageData.list.length) return null
		return { page: pageIndex + 1, pageSize: size, type, name, searchType: vkSearchType } // Request parameters
	}

	const usePaginatedData = (value: string, type: FlowRouteType, size: number) => {
		const name = useDebounceSearch(value, 400)
		const { data, error, mutate, setSize, isLoading } = useSWRInfinite(
			(pageIndex, previousPageData) =>
				getKey({ pageIndex, previousPageData, type, name, size }),
			fetcher,
		)

		const items = data ? data.map((page) => page?.list).flat() : []
		const total = data?.[0]?.total || 0

		// Determine if more data is available
		const hasMore = items.length < total

		return { items, error, mutate, setSize, hasMore, total, loading: isLoading }
	}

	const {
		items: flowList,
		hasMore,
		setSize,
		mutate,
		total,
		loading,
	} = usePaginatedData(keyword, flowType, pageSize)

	const loadMoreData = useMemoizedFn(() => {
		if (!hasMore) return
		setSize((size) => size + 1)
	})

	const addNewFlow = useMemoizedFn((newItem) => {
		mutate((currentData: CurrentDataType) => {
			if (!currentData) return currentData
			const updatedData = [...currentData]
			updatedData[0] = {
				...updatedData[0],
				list: [newItem, ...updatedData[0].list], // Insert new item into the first page
				total: updatedData[0].total + 1, // Update total count
			}
			return updatedData
		}, false) // Do not re-fetch data
	})

	const [expandPanelOpen, { setTrue: openExpandPanel, setFalse: closeExpandPanel }] =
		useBoolean(false)

	const [addOrUpdateFlowOpen, { setTrue: openAddOrUpdateFlow, setFalse: closeAddOrUpdateFlow }] =
		useBoolean(false)

	const title = useMemo(() => {
		const map = {
			[FlowRouteType.Agent]: globalT("common.agent", { ns: "flow" }),
			[FlowRouteType.Sub]: globalT("common.flow", { ns: "flow" }),
			[FlowRouteType.Tools]: globalT("common.toolset", { ns: "flow" }),
			[FlowRouteType.VectorKnowledge]: globalT("common.knowledgeDatabase", { ns: "flow" }),
			[FlowRouteType.Mcp]: globalT("mcp.name", { ns: "flow" }),
		}
		return map[flowType]
	}, [flowType, globalT])

	const handleCardCancel = useMemoizedFn(() => {
		closeExpandPanel()
		resetCurrentFlow()
		resetGroupId()
	})

	useUpdateEffect(() => {
		resetKeyword()
		resetVkSearchType()
		handleCardCancel()
	}, [flowType])

	const goToFlow = useMemoizedFn((id) => {
		if (!id) return
		if (flowType === FlowRouteType.VectorKnowledge) {
			navigate(
				replaceRouteParams(RoutePath.FlowKnowledgeDetail, {
					id,
				}),
			)
		} else {
			navigate(
				replaceRouteParams(RoutePath.FlowDetail, {
					id,
					type: flowType,
				}),
			)
		}
	})

	const updateVersion = useMemoizedFn(async (tool: Flow.Mcp.ListItem) => {
		const mcpTool = pick(tool, [
			"id",
			"source",
			"name",
			"description",
			"icon",
			"rel_code",
			"enabled",
		])
		await FlowApi.saveMcpTool(
			{ ...mcpTool, rel_version_code: tool.source_version?.latest_version_code },
			tool.mcp_server_code,
		)
		mcpEventListener.emit("updateMcpList")
		message.success(t("mcp.updateVersionSuccess", { ns: "flow" }))
	})

	const checkIsLatestMcpTool = useMemoizedFn((tool: Flow.Mcp.ListItem) => {
		if (!tool.version || !tool?.source_version?.latest_version_name) {
			return true
		}
		return tool.source_version?.latest_version_code === tool.rel_version_code
	})

	const deleteFlow = useMemoizedFn(async (flow, tool = false) => {
		openModal(DeleteDangerModal, {
			content: flow.name,
			needConfirm: false,
			onSubmit: async () => {
				switch (flowType) {
					case FlowRouteType.Tools:
						// 'tool' indicates a single tool, not a toolset
						if (tool) {
							// Delete tool
							await FlowApi.deleteFlow(flow.code)
						} else {
							// Delete toolset
							await FlowApi.deleteTool(flow.id)
						}
						break
					case FlowRouteType.Sub:
						// Delete subflow
						await FlowApi.deleteFlow(flow.id)
						break
					case FlowRouteType.VectorKnowledge:
						// Delete knowledge base
						await KnowledgeApi.deleteKnowledge(flow.code)
						break
					case FlowRouteType.Mcp:
						// Delete MCP
						if (tool) {
							await FlowApi.deleteMcpTool(flow.id, currentFlow.id ?? "")
						} else {
							await FlowApi.deleteMcp(flow.id)
						}
						break
					default:
						break
				}
				// When deleting an MCP tool
				if (isMcp && tool) {
					mutate((currentData: CurrentDataType) => {
						return currentData?.map((page) => ({
							...page,
							list: page?.list.map((item: Flow.Mcp.Detail) => {
								if (item.id === flow.mcp_server_code) {
									return {
										...item,
										tools_count: item.tools_count - 1,
									}
								}
								return item
							}),
						}))
					}, false)
					setCurrentFlow((prev) => {
						return {
							...prev,
							tools_count: (prev as Flow.Mcp.Detail).tools_count - 1,
						}
					})
					mcpEventListener.emit("updateMcpList")
				}
				// Update tools list
				else if (tool) {
					let { tools = [] } = currentFlow as FlowWithTools
					tools = tools.filter((n) => n.code !== flow.code)
					setCurrentFlow(() => {
						return {
							...currentFlow,
							tools,
						}
					})
						// Update tool count in the toolset
					mutate((currentData: CurrentDataType) => {
						const updatedData = currentData?.map((page) => {
							const list = page?.list.map((item: DelightfulFlow.Flow) => {
								if (item.id === flow.tool_set_id) {
									item.tools = tools
									return item
								}
								return item
							})
							return {
								...page,
								list,
							}
						})
						return updatedData
					}, false)
				} else {
					// Update flow list
					mutate((currentData: CurrentDataType) => {
						if (!currentData) return currentData
						const updatedData = currentData?.map((page) => ({
							...page,
							list: page?.list.filter((item: DelightfulFlow.Flow) => item.id !== flow.id),
						}))
						updatedData[0].total -= 1 // Update total count
						return updatedData
					}, false)
				}
				message.success(
					`${globalT("common.delete", { ns: "flow" })} ${title} ${flow.name} ${globalT(
						"common.success",
						{ ns: "flow" },
					)}`,
				)
			},
		})
	})

	const updateFlowEnable = useMemoizedFn(async (flow) => {
		switch (flowType) {
			case FlowRouteType.Mcp:
				// MCP
				await FlowApi.saveMcp({
					id: flow.id,
					name: flow.name,
					description: flow.description,
					icon: flow.icon,
					enabled: !flow.enabled,
				})
				break
			case FlowRouteType.Tools:
				// Toolset
				await FlowApi.saveTool({
					id: flow?.id,
					name: flow.name,
					description: flow.description,
					icon: flow.icon,
					enabled: !flow.enabled,
				})
				break
			case FlowRouteType.Sub:
				// Flow
				await FlowApi.changeEnableStatus(flow.id)
				break
			case FlowRouteType.VectorKnowledge:
				// Knowledge base
				await KnowledgeApi.updateKnowledge({
					code: flow.code,
					name: flow.name,
					description: flow.description,
					icon: flow.icon,
					enabled: !flow.enabled,
				})
				break
			default:
				break
		}
		const text = flow.enabled
			? globalT("common.baned", { ns: "flow" })
			: globalT("common.enabled", { ns: "flow" })
		message.success(`${flow.name} ${text}`)

		// Update flow list
		mutate((currentData: CurrentDataType) => {
			return currentData?.map((page) => ({
				...page,
				list: page?.list.map(
					(item: DelightfulFlow.Flow) =>
						item.id === flow.id
							? { ...item, enabled: !flow.enabled } // Update the target item
							: item, // Keep other items unchanged
				),
			}))
		}, false)
	})

		// Update current card and list info
	const updateFlowOrTool = useMemoizedFn((flow, isTool = false, update = false) => {
			// Tools
		if (isTool) {
			if (update) {
					// Update
				setCurrentFlow(
					(prev: FlowWithTools | Knowledge.KnowledgeItem | Flow.Mcp.Detail) => {
						return {
							...prev,
							tools: (prev as FlowWithTools)?.tools?.map?.((n: FlowTool.Tool) => {
								if (n.code === flow.id) {
									return {
										...n,
										name: flow.name,
										description: flow.description,
									}
								}
								return n
							}),
						}
					},
				)
			} else {
				// Add
				let { tools = [] } = currentFlow as FlowWithTools
				tools = [...tools, { ...flow, code: flow.id }]
				setCurrentFlow(() => {
					return {
						...currentFlow,
						tools,
					}
				})
				// Added: increase tool count in the toolset
				mutate((currentData: CurrentDataType) => {
					return currentData?.map((page) => ({
						...page,
						list: page?.list.map(
							(item: DelightfulFlow.Flow) =>
								item.id === flow.tool_set_id
									? { ...item, tools } // Update the target item
									: item, // Keep other items unchanged
						),
					}))
				}, false)
			}
		} else {
				// Flows (subflows/toolsets)
				// Update current flow
			setCurrentFlow((prev: FlowWithTools | Knowledge.KnowledgeItem | Flow.Mcp.Detail) => {
				return {
					...prev,
					name: flow.name,
					description: flow.description,
					icon: flow.icon,
				}
			})
			// Update flow list
			mutate((currentData: CurrentDataType) => {
				return currentData?.map((page) => ({
					...page,
					list: page?.list.map((item: DelightfulFlow.Flow) =>
						item.id === flow.id
							? {
									...item,
									name: flow.name,
									description: flow.description,
									icon: flow.icon,
							  }
							: item,
					),
				}))
			}, false)
		}
	})

	// const handleCopy = useMemoizedFn((flow: DelightfulFlow.Flow | FlowTool.Tool) => {
	// 	copyToClipboard(flow.id!)
	// 	message.success(`${t("chat.copy")} ${t("flow.apiKey.success")}`)
	// })

		/** Go to vector knowledge base details */
	const goToKnowledgeDetail = useMemoizedFn((code: string) => {
		navigate(`${RoutePath.VectorKnowledgeDetail}?code=${code}`)
	})

	const getDropdownItems = useMemoizedFn(
		(flow: DelightfulFlow.Flow | Knowledge.KnowledgeItem | Flow.Mcp.Detail) => {
			return (
				<>
					{flowType === FlowRouteType.VectorKnowledge &&
						hasViewRight(flow.user_operation) && (
							<DelightfulButton
								justify="flex-start"
								icon={
									<DelightfulIcon component={IconEye} size={20} color="currentColor" />
								}
								size="large"
								type="text"
								block
								onClick={() => {
									goToKnowledgeDetail(flow.code)
								}}
							>
								{t("flow.viewDetails")}
							</DelightfulButton>
						)}
					{hasEditRight(flow.user_operation) && (
						<DelightfulButton
							justify="flex-start"
							icon={<DelightfulIcon component={IconEdit} size={20} color="currentColor" />}
							size="large"
							type="text"
							block
							onClick={() => {
								setCurrentFlow(flow)
								openAddOrUpdateFlow()
							}}
						>
							{changeInfoTitle}
						</DelightfulButton>
					)}
					{hasAdminRight(flow.user_operation) && (
						<DelightfulButton
							justify="flex-start"
							icon={
								<DelightfulIcon component={IconTrash} size={20} color="currentColor" />
							}
							size="large"
							type="text"
							block
							danger
							onClick={() => deleteFlow(flow)}
						>
							{globalT("common.deleteSomething", { ns: "flow", name: title })}
						</DelightfulButton>
					)}
				</>
			)
		},
	)

	const changeInfoTitle = useMemo(() => {
		if (isMcp) return globalT("mcp.modifyMcp", { ns: "flow" })
		return t("flow.changeInfo")
	}, [isMcp, globalT, t])

	const getRightPanelDropdownItems = useMemoizedFn(
		(tool: FlowTool.Tool | Flow.Mcp.ListItem, flow: DelightfulFlow.Flow) => {
			return (
				<>
					{isMcp && !checkIsLatestMcpTool(tool as Flow.Mcp.ListItem) && (
						<Popconfirm
							title={t("mcp.confirmToUpdateVersion", { ns: "flow" })}
							onConfirm={() => updateVersion(tool as Flow.Mcp.ListItem)}
						>
							<DelightfulButton
								justify="flex-start"
								icon={
									<DelightfulIcon component={IconKey} size={20} color="currentColor" />
								}
								size="large"
								type="text"
								block
							>
								{t("mcp.updateVersion", { ns: "flow" })}
							</DelightfulButton>
						</Popconfirm>
					)}
					<DelightfulButton
						justify="flex-start"
						icon={<DelightfulIcon component={IconEdit} size={20} color="currentColor" />}
						size="large"
						type="text"
						block
						onClick={() => {
							setCurrentTool(tool)
							if (isMcp) {
								setGroupId((tool as Flow.Mcp.ListItem).mcp_server_code)
							} else {
								setGroupId(tool.tool_set_id)
							}
							openAddOrUpdateFlow()
						}}
					>
						{globalT("common.editSomething", {
							ns: "flow",
							name: t("flow.tools"),
						})}
					</DelightfulButton>
					{/* <DelightfulButton
						justify="flex-start"
						icon={<DelightfulIcon component={IconCopy} size={20} color="currentColor" />}
						size="large"
						type="text"
						block
						onClick={() => handleCopy(tool)}
					>
						{t("chat.copy")}
						{t("flow.tools")}
					</DelightfulButton> */}
					{hasAdminRight(flow.user_operation) && (
						<DelightfulButton
							justify="flex-start"
							icon={
								<DelightfulIcon component={IconTrash} size={20} color="currentColor" />
							}
							size="large"
							type="text"
							block
							danger
							onClick={() => deleteFlow(tool, true)}
						>
							{globalT("common.deleteSomething", {
								ns: "flow",
								name: t("flow.tools"),
							})}
						</DelightfulButton>
					)}
				</>
			)
		},
	)

	const handleCardClick = useMemoizedFn(
		(flow: DelightfulFlow.Flow | Knowledge.KnowledgeItem | Flow.Mcp.Detail) => {
			// Click vector knowledge base card to go directly to details
		if (flowType === FlowRouteType.VectorKnowledge && flow.code) {
			return goToKnowledgeDetail(flow.code)
		}
		const checked = currentFlow?.id === flow.id
			if (checked) {
				resetCurrentFlow()
				closeExpandPanel()
			} else {
				setCurrentFlow(flow)
				openExpandPanel()
			}
		},
	)

	const handleCloseAddOrUpdateFlow = useMemoizedFn(() => {
		closeAddOrUpdateFlow()
		resetGroupId()
		resetCurrentTool()
	})

	const initUseableToolSets = useMemoizedFn(async () => {
		try {
			const response = await FlowApi.getUseableToolList()
			console.log("FlowApi.getUseableToolList()", response)
			if (response && response.list) {
				updateUseableToolSets(response.list)
			}
		} catch (e) {
			console.log("initUseableToolSets error", e)
		}
	})

	useUpdateEffect(() => {
		if (isMcp) {
			initUseableToolSets()
		}
	}, [isMcp])

	return {
		scrollRef,
		goToFlow,
		deleteFlow,
		updateFlowEnable,
		getDropdownItems,
		getRightPanelDropdownItems,
		keyword,
		setKeyword,
		vkSearchType,
		setVkSearchType,
		loading,
		flowList,
		title,
		groupId,
		setGroupId,
		currentFlow,
		setCurrentFlow,
		resetCurrentFlow,
		currentTool,
		setCurrentTool,
		expandPanelOpen,
		openExpandPanel,
		closeExpandPanel,
		addOrUpdateFlowOpen,
		openAddOrUpdateFlow,
		closeAddOrUpdateFlow,
		handleCardClick,
		handleCardCancel,
		handleCloseAddOrUpdateFlow,
		updateFlowOrTool,
		addNewFlow,
		mutate,
		loadMoreData,
		hasMore,
		total,
		mcpEventListener,
	}
}
