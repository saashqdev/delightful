/**
 * Large language model knowledge base data source hooks
 */

import type { Knowledge } from "@/types/knowledge"
import { useCurrentNode } from "@delightful/delightful-flow/dist/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { useMemo, useState, useEffect } from "react"
import RenderLabel from "../../KnowledgeDatabaseSelect/RenderLabel"
import { useFlowStore } from "@/opensource/stores/flow"
import { KnowledgeApi } from "@/apis"
import { knowledgeType } from "@/opensource/pages/vectorKnowledge/constant"
import { VectorKnowledge } from "@/types/flow"
import { useMemoizedFn } from "ahooks"

export default function useKnowledgeDatabases() {
	const { useableTeamshareDatabase } = useFlowStore()

	const { currentNode } = useCurrentNode()

	// Tiangshu knowledge base
	const teamshareDatabaseOptions = useMemo(() => {
		return useableTeamshareDatabase.map((item) => {
			const hasSelected = currentNode?.params?.knowledge_config?.knowledge_list?.find?.(
				(knowledge: Knowledge.KnowledgeDatabaseItem) =>
					knowledge?.business_id === item.business_id,
			)
			return {
				label: <RenderLabel item={item} />,
				value: item.business_id,
				disabled: !!hasSelected,
			}
		})
	}, [currentNode, useableTeamshareDatabase])

	// User-created knowledge base pagination parameters
	const [userDbPagination, setUserDbPagination] = useState({
		page: 1,
		pageSize: 10,
		hasMore: true,
		loading: false,
		total: 0,
	})

	// Get已启用的用户自建知识库选项，支持分页
	const getUserDatabaseOptions = useMemoizedFn(async (page = 1, pageSize = 10) => {
		const response = await KnowledgeApi.getKnowledgeList({
			type: knowledgeType.UserKnowledgeDatabase,
			searchType: VectorKnowledge.SearchType.Enabled,
			name: "",
			page,
			pageSize,
		})
		const { list, total } = response
		return { list, total }
	})

	// User-created knowledge base (vector database)
	const [userDatabaseOptions, setUserDatabaseOptions] = useState<any[]>([])

	// 监听滚动加载更多
	const userDatabasePopupScroll = useMemoizedFn((e: any) => {
		// Get滚动容器
		const target = e.target

		// Detect if scrolled to bottom (with tolerance)
		const isBottom = target.scrollTop + target.clientHeight >= target.scrollHeight - 20

		// 如果滚动到底部且有更多数据可加载且当前不在加载中
		if (isBottom && userDbPagination.hasMore && !userDbPagination.loading) {
			loadMoreUserDatabases()
		}
	})

	// 加载更多用户自建知识库
	const loadMoreUserDatabases = useMemoizedFn(async () => {
		// Set加载状态
		setUserDbPagination((prev) => ({
			...prev,
			loading: true,
		}))

		try {
			// 加载下一页数据
			const nextPage = userDbPagination.page + 1
			const { list, total } = await getUserDatabaseOptions(
				nextPage,
				userDbPagination.pageSize,
			)

			// 格式化选项并追加到现有列表
			const formattedOptions = list.map((item: Knowledge.KnowledgeItem) => {
				return {
					business_id: "",
					name: item.name,
					description: item.description,
					knowledge_type: item.type,
					knowledge_code: item.code,
				}
			})

			// 合并数据
			setUserDatabaseOptions((prev) => [...prev, ...formattedOptions])

			// 更新分页状态
			setUserDbPagination({
				page: nextPage,
				pageSize: userDbPagination.pageSize,
				hasMore: nextPage * userDbPagination.pageSize < total,
				loading: false,
				total,
			})
		} catch (error) {
			console.error("Failed to load more user database options:", error)
			setUserDbPagination((prev) => ({
				...prev,
				loading: false,
			}))
		}
	})

	// 初次加载用户自建知识库数据
	useEffect(() => {
		const loadInitialUserDatabaseOptions = async () => {
			// 重置分页参数
			setUserDbPagination({
				page: 1,
				pageSize: 10,
				hasMore: true,
				loading: true,
				total: 0,
			})

			// 清空现有选项
			setUserDatabaseOptions([])

			try {
				// 加载第一页数据
				const { list, total } = await getUserDatabaseOptions(1, 10)
				const formattedOptions = list.map((item: Knowledge.KnowledgeItem) => {
					return {
						business_id: "",
						name: item.name,
						description: item.description,
						knowledge_type: item.type,
						knowledge_code: item.code,
					}
				})

				// Set选项和分页状态
				setUserDatabaseOptions(formattedOptions)
				setUserDbPagination({
					page: 1,
					pageSize: 10,
					hasMore: 10 < total,
					loading: false,
					total,
				})
			} catch (error) {
				console.error("Failed to load user database options:", error)
				setUserDatabaseOptions([])
				setUserDbPagination((prev) => ({
					...prev,
					loading: false,
					hasMore: false,
				}))
			}
		}

		loadInitialUserDatabaseOptions()
	}, [currentNode, getUserDatabaseOptions])

	return {
		teamshareDatabaseOptions,
		userDatabaseOptions,
		userDatabasePopupScroll,
	}
}

