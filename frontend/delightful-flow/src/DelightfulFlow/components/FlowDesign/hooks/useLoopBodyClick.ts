
import { DelightfulFlow } from '@/DelightfulFlow/types/flow'
import { useMemoizedFn } from 'ahooks'

export default function useLoopBodyClick() {

	const elevateBodyEdgesLevel = useMemoizedFn((node: DelightfulFlow.Node) => {
		// const relationNodesIds = nodes.filter((n) => n.meta.parent_id === node.id).map((n) => n.id)
		// const relationEdges = edges.filter(
		// 	(e) => relationNodesIds.includes(e.source) || relationNodesIds.includes(e.target),
		// )
		// relationEdges.forEach((e) => {
		// 	e.zIndex = 2001
		// })
		// setEdges([...edges])
	})

	const resetEdgesLevels = useMemoizedFn((node: DelightfulFlow.Node) => {
		
		// // Clicking a regular node
		// const relationNodeIds = [node.id]

		// // If clicking a node inside loop body, all edges in loop body should be highest level


		// const relationEdgesIds = edges.filter(
		// 	(e) => node.id === e.source ||  node.id === e.target,
		// ).map(e => e.id)
		// edges.forEach(e => {
		// 	if(!relationEdgesIds.includes(e.id)) {
		// 		e.zIndex = 0
		// 	}
		// })
		// setEdges([...edges])
	})

	return {
		elevateBodyEdgesLevel,
		resetEdgesLevels
	}
}

