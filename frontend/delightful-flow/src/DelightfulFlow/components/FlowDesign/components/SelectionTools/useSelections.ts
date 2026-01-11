/**
 * Selection box related state
 */
import { DelightfulFlow } from '@/DelightfulFlow/types/flow'
import { copyToClipboard } from '@/DelightfulFlow/utils'
import { calculateMidpoint } from '@/DelightfulFlow/utils/reactflowUtils'
import { useMemoizedFn, useUpdate, useUpdateEffect } from 'ahooks'
import { message } from 'antd'
import { useEffect, useState } from 'react'
import { Edge, useKeyPress, useStoreApi } from 'reactflow'
import { useTranslation } from 'react-i18next'
import i18next from 'i18next'

type UseSelectionsProps = {
    flowInstance: any
}

export default function useSelections({ flowInstance }:UseSelectionsProps) {

    const { t } = useTranslation()

	// Whether to show selection tools
	const [showSelectionTools, setShowSelectionTools] = useState(false)

	// Selected nodes
	const [selectionNodes, setSelectionNodes] = useState([] as DelightfulFlow.Node[])

	// Selected edges
	const [selectionEdges, setSelectionEdges] = useState([] as Edge[])

	useUpdateEffect(() => {
		// console.log("selectionNodes",selectionNodes, selectionEdges)
	}, [selectionNodes])

	const [selectionCenter, setSelectionCenter] = useState({
		left: "0",
		top: "0"
	})

	/** Selection change event */
	const onSelectionChange = useMemoizedFn(({ nodes, edges }: any) => {
		// console.log(nodes, edges)
		setSelectionNodes(nodes)
		setSelectionEdges(edges)
	})

	
	/** Selection end event */
	const onSelectionEnd = useMemoizedFn(() => {
		const center = calculateMidpoint(selectionNodes)
		setSelectionCenter({
			left: `${center.x}px`,
			top: `${center.y}px`
		})

		setShowSelectionTools(selectionNodes.length > 1)
	})

	useUpdateEffect(() => {
		if(selectionNodes.length === 0) {
			setShowSelectionTools(false)
		}
	}, [selectionNodes])


	const { getState } = useStoreApi()

	const store = getState()

	const onCopy = useMemoizedFn(() => {
        if(!selectionNodes.length) return
		copyToClipboard(
			JSON.stringify({
				nodes: selectionNodes,
				edges: selectionEdges,
			}),
		)

		message.success(i18next.t("common.copySuccess", { ns: "delightfulFlow" }))   

		/** Clear current selection */
		store.unselectNodesAndEdges()
	})

    

	// useEffect(() => {
	// 	const target = flowInstance.current
	// 	if (target) {
	// 		target.addEventListener("copy", onCopy, { capture: true })
	// 	}

	// 	return () => {
	// 		if (target) {
	// 			target.removeEventListener("copy", onCopy)
	// 		}
	// 	}
	// }, [flowInstance])


	return {
		showSelectionTools,
		setShowSelectionTools,
		selectionNodes,
		setSelectionNodes,
		selectionEdges,
		setSelectionEdges,
		onSelectionChange,
		onSelectionEnd,
		selectionCenter,
        onCopy
	}
}

