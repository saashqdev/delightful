import { message } from 'antd'
import { useMemoizedFn } from 'ahooks'
import i18next from 'i18next'
import React, { useEffect, useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import resolveToString from '@/common/utils/template'
import { getAllPredecessors } from '@/DelightfulFlow/utils/reactflowUtils'
import { checkIsInGroup } from '@/DelightfulFlow/utils'
import { useNodeConfig, useNodeConfigActions } from '@/DelightfulFlow/context/FlowContext/useFlow'
import { useExternalConfig } from '@/DelightfulFlow/context/ExternalContext/useExternal'
import { useReactFlow } from 'reactflow'

type DebugProps = {
	id: string
}

export default function useDebug({ id }: DebugProps) {

    const { t } = useTranslation()

	const [isDebug, setIsDebug] = useState(false)

	const { allowDebug } = useExternalConfig()

    const { nodeConfig } = useNodeConfig()
    const { updateNodeConfig } = useNodeConfigActions()
    const { getNodes, getEdges } = useReactFlow()
    
	const currentNode = useMemo(() => {
		return nodeConfig[id]
	}, [nodeConfig, id])

	const checkHasPreNodeInDebug = useMemoizedFn(() => {
        
		if(!currentNode) return false
        const edges = getEdges()
        const nodes = getNodes()
		let predecessors = getAllPredecessors(currentNode, nodes, edges)
		
		// For nodes inside a loop body, reference sources include current upstream nodes plus the loop body upstream nodes
		if (checkIsInGroup(currentNode)) {
			const loopBodyNode = nodeConfig?.[currentNode?.meta?.parent_id]
			const loopBodyAllPrevNodes = getAllPredecessors(loopBodyNode, nodes, edges)
			predecessors = [...loopBodyAllPrevNodes, ...predecessors]
		}
		// If a preceding node is in debug mode, set current node explicitly to debug
		const hasPreNodeDebug = predecessors.find(preNode => preNode.debug)
		return hasPreNodeDebug
	})

	useEffect(() => {
        const nodes = getNodes()
		// Determine if React Flow finished rendering by checking if size is attached
		if(!nodes?.[0]?.width) return
		const hasPreNodeDebug = checkHasPreNodeInDebug()
		if(hasPreNodeDebug) {
			setIsDebug(true)
		}else{
			// Whether current node is in debug mode
			setIsDebug(!!currentNode?.debug)
		}
	}, [nodeConfig])


	const onDebugChange = useMemoizedFn((debug: boolean) => {
		const hasPreNodeDebug = checkHasPreNodeInDebug()
		if(hasPreNodeDebug) {
			message.warning(resolveToString(i18next.t("flow.hasBeforeNodeDebugTips", { ns: "delightfulFlow" }), { name: hasPreNodeDebug.name }))
			return
		}
		updateNodeConfig({
			...currentNode,
			debug
		})
	})


	return {
		isDebug,
		onDebugChange,
		allowDebug
	}
}

