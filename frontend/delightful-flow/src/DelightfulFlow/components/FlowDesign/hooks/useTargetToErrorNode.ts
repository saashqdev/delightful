/** When logs run into error, locate to the error node */
import { useNodeTesting } from '@/DelightfulFlow/context/NodeTesingContext/useNodeTesting'
import React from 'react'
import useViewport from '../../common/hooks/useViewport'
import { useUpdateEffect } from 'ahooks'
import { useFlow } from '@/DelightfulFlow/context/FlowContext/useFlow'
import { useNodes } from '@/DelightfulFlow/context/NodesContext/useNodes'

export default function useTargetToErrorNode() {
	const { testingResultMap, position } = useNodeTesting()
	const { updateViewPortToTargetNode } = useViewport()
	const { nodes } = useNodes()

	useUpdateEffect(() => {
		if (!testingResultMap || !position) return
		const [errorNodeId] = Object.entries(testingResultMap)
			.filter(([, nodeDebug]) => {
				return !nodeDebug.success
			})
			.map(([nodeId]) => nodeId)
		
		// console.log("errorNodeId", errorNodeId, testingResultMap)
		// If error exists, locate to error node id
		if (errorNodeId) {
			const errorNode = nodes.find((n) => n.node_id === errorNodeId)
			// console.log("errorNode", errorNode)
			if (!errorNode?.width) return
			updateViewPortToTargetNode(errorNode)
		}
		// All success, then locate to last node
		else{
			const testNodeIds = Object.keys(testingResultMap)
			const [lastSuccessNodeId] = Object.entries(testingResultMap).filter(([, testConfig]) => {
				const filterChildrenIds = testConfig?.children_ids?.filter?.(id => testNodeIds.includes(id))
				return filterChildrenIds?.length === 0
			}).map(([nodeId]) => nodeId)
			const lastSuccessNode = nodes.find((n) => n.node_id === lastSuccessNodeId)
			if (!lastSuccessNode?.width) return
			updateViewPortToTargetNode(lastSuccessNode)
		}
	}, [testingResultMap, position])
}

