import type { Knowledge } from "@/types/knowledge"
import { useCurrentNode } from "@delightful/delightful-flow/dist/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { useMemoizedFn } from "ahooks"
import React, { useEffect, useMemo, useRef } from "react"
import { get } from "lodash-es"
import { KnowledgeStatus } from "../../../constants"
import { KnowledgeApi } from "@/apis"
import { useCommercial } from "@/opensource/pages/flow/context/CommercialContext"

type UseProgressProps = {
	knowledgeListName: string[]
}

export default function useProgress({ knowledgeListName }: UseProgressProps) {
	const [progressList, setProgressList] = React.useState(
		[] as Knowledge.KnowledgeDatabaseProgress[],
	)

	const extraData = useCommercial()
	const isCommercial = useMemo(() => {
		return !!extraData
	}, [extraData])

	const { currentNode } = useCurrentNode()

	const intervalId = useRef<NodeJS.Timeout>()

	const clearProgressInterval = useMemoizedFn(() => {
		if (intervalId.current) {
			clearInterval(intervalId.current)
			intervalId.current = undefined
		}
	})

	const checkIsAllProgressDone = useMemoizedFn((list: Knowledge.KnowledgeDatabaseProgress[]) => {
		return list.every((item) => item.vector_status !== KnowledgeStatus.Vectoring)
	})

	const searchProgress = useMemoizedFn(async () => {
		const knowledgeList = get(
			currentNode,
			["params", ...knowledgeListName],
			[],
		) as Knowledge.KnowledgeDatabaseItem[]
		if (knowledgeList.length > 0) {
			const progressListResult = await KnowledgeApi.getTeamshareKnowledgeProgress({
				knowledge_codes: knowledgeList.map((item) => item.knowledge_code),
			})
			if (progressListResult.list.length > 0) {
				setProgressList(progressListResult.list)
				// If no progress is in progress, stop
				if (checkIsAllProgressDone(progressListResult.list)) {
					clearProgressInterval()
				}
			}
		}
	})

	const initInterval = useMemoizedFn(() => {
		searchProgress()
		// Set 3 second timer
		intervalId.current = setInterval(searchProgress, 3000)
		return intervalId.current
	})

	useEffect(() => {
		if (!isCommercial) return
		const id = initInterval()
		return () => {
			clearInterval(id)
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [])

	return {
		progressList,
		initInterval,
		setProgressList,
	}
}






