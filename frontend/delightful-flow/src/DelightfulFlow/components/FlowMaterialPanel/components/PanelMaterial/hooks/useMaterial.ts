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
    //  cache nodeList result, avoid creating new reference every render time
    const nodeList = getExecuteNodeList()

    const { flow } = useFlowData()

	//  dynamic node list
	const dynamicNodeList = useMemo(() => {
		return nodeList.filter(n => n.schema.label.includes(keyword))
	}, [keyword, nodeList])

	// get divided group node list
	const getGroupNodeList = useMemoizedFn((nodeTypes: BaseNodeType[]) => {
		return dynamicNodeList.filter(n => {
			return nodeTypes.includes(n.schema.id)
		})
	})

	//  Filter out divided group list with node data, and put node schema inside
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

