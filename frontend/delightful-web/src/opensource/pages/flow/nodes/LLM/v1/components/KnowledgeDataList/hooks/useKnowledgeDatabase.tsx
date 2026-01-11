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

	// Get enabled user-created knowledge base options with pagination support
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

	// Listen for scroll to load more
	const userDatabasePopupScroll = useMemoizedFn((e: any) => {
		// Get scroll container
		const target = e.target

		// Detect if scrolled to bottom (with tolerance)
		const isBottom = target.scrollTop + target.clientHeight >= target.scrollHeight - 20

		// If scrolled to bottom and has more data to load and not currently loading
		if (isBottom && userDbPagination.hasMore && !userDbPagination.loading) {
			loadMoreUserDatabases()
		}
	})

	// Load more user-created knowledge bases
	const loadMoreUserDatabases = useMemoizedFn(async () => {
		// Set loading state
		setUserDbPagination((prev) => ({
			...prev,
			loading: true,
		}))

		try {
			// Load next page data
			const nextPage = userDbPagination.page + 1
			const { list, total } = await getUserDatabaseOptions(
				nextPage,
				userDbPagination.pageSize,
			)

			// Format options and append to existing list
			const formattedOptions = list.map((item: Knowledge.KnowledgeItem) => {
				return {
					business_id: "",
					name: item.name,
					description: item.description,
					knowledge_type: item.type,
					knowledge_code: item.code,
				}
			})

			// Merge data
			setUserDatabaseOptions((prev) => [...prev, ...formattedOptions])

			// Update pagination state
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

	// Initial load of user-created knowledge base data
	useEffect(() => {
		const loadInitialUserDatabaseOptions = async () => {
			// Reset pagination parameters
			setUserDbPagination({
				page: 1,
				pageSize: 10,
				hasMore: true,
				loading: true,
				total: 0,
			})

			// Clear existing options
			setUserDatabaseOptions([])

			try {
				// Load first page data
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

				// Set options and pagination state
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



