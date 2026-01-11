import { getExecuteNodeList } from "@/DelightfulFlow/constants"
import { useFlowData } from "@/DelightfulFlow/context/FlowContext/useFlow"
import { BaseNodeType } from "@/DelightfulFlow/register/node"
import { getNodeGroups } from "@/DelightfulFlow/utils"
import { useMemoizedFn } from "ahooks"
import { useMemo } from "react"


type MaterialProps = {
	keyword:string
}

export default function useMaterial ({ keyword }: MaterialProps) {
    // 缓存nodeList的结果，避免每次渲染时都create新的引用
    const nodeList = getExecuteNodeList()

    const { flow } = useFlowData()

	// 动态的nodelist
	const dynamicNodeList = useMemo(() => {
		return nodeList.filter(n => n.schema.label.includes(keyword))
	}, [keyword, nodeList])

	// get分组nodelist
	const getGroupNodeList = useMemoizedFn((nodeTypes: BaseNodeType[]) => {
		return dynamicNodeList.filter(n => {
			return nodeTypes.includes(n.schema.id)
		})
	})

	// 过滤出有node数据的分组list，并往里边塞node的schema
	const filterNodeGroups = useMemo(() => {
		const allNodeGroups = getNodeGroups()
		return allNodeGroups.map(nodeGroup => {
			const dynamicNodes = getGroupNodeList(nodeGroup.nodeTypes)
			const nodeTypes = dynamicNodes.map(n => n.schema.id)

			if( nodeTypes?.length === 0) return null

			return {
				...nodeGroup,
				nodeTypes,
				nodeSchemas: dynamicNodes,
				isGroupNode: nodeGroup.children?.length! > 0
			}
		}).filter(n => !!n)
	}, [getGroupNodeList, keyword, flow])

	return {
		nodeList: dynamicNodeList,
		getGroupNodeList,
		filterNodeGroups
	}
}

