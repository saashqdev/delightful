/**
 * Toolset panel selection related data and behavior management
 */

import { useFlowStore } from "@/opensource/stores/flow"
import type { UseableToolSet } from "@/types/flow"
import { useEffect, useState, useMemo } from "react"

type UseToolPanelProps = {
	open: boolean
}

export default function useToolsPanel({ open }: UseToolPanelProps) {
	const [keyword, setKeyword] = useState("")

	const { useableToolSets } = useFlowStore()

	// Use useMemo to cache filtering results and avoid unnecessary recalculations
	const filteredUseableToolSets = useMemo(() => {
		if (!keyword.trim()) {
			return useableToolSets
		}

		return useableToolSets
			.map((useableToolSet) => {
				const filterTools = useableToolSet?.tools?.filter(
					(tool) =>
						tool?.name?.toLowerCase?.()?.includes?.(keyword.toLowerCase()) ||
						tool?.description?.toLowerCase?.()?.includes?.(keyword.toLowerCase()),
				)
				if (filterTools?.length > 0) {
					return {
						...useableToolSet,
						tools: filterTools,
					}
				}
				return null
			})
			.filter(Boolean) as UseableToolSet.Item[]
	}, [useableToolSets, keyword])

	// Reset keyword when panel opens
	useEffect(() => {
		if (open) {
			setKeyword("")
		}
	}, [open])

	return {
		useableToolSets,
		filteredUseableToolSets,
		keyword,
		setKeyword,
	}
}
