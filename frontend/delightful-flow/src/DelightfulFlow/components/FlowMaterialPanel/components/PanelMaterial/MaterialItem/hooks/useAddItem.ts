import { useFlowNodes, useNodeConfig } from "@/DelightfulFlow/context/FlowContext/useFlow"
import { useExternalConfig } from "@/DelightfulFlow/context/ExternalContext/useExternal"
import { useMemoizedFn } from "ahooks"
import { useReactFlow, Edge } from "reactflow"
import _ from "lodash"
import { generateSnowFlake } from "@/common/utils/snowflake"
import {
  generateLoopBody,
  generateNewNode,
  judgeIsLoopBody,
  judgeLoopNode
} from "@/DelightfulFlow/utils"

interface UseAddItemProps {
  item: any
}

const useAddItem = ({ item }: UseAddItemProps) => {
  const { addNode, selectedNodeId } = useFlowNodes()
  const { nodeConfig } = useNodeConfig()
  const reactflow = useReactFlow()
  const { paramsName } = useExternalConfig()

  const onAddItem = useMemoizedFn(() => {
    //  Whenaddloop体oftime候，actualaddof元素yesmanyitemof
    const newNodes = []
    const newEdges = [] as Edge[]
    // whetherat divisiongroupinsideaddnode
    let isAddInGroup = false
    const selectedNode = nodeConfig?.[selectedNodeId!]
    const isLoopBody = judgeIsLoopBody(selectedNode?.[paramsName.nodeType])
    
    if (selectedNodeId) {
      isAddInGroup = isLoopBody || !!selectedNode?.parentId
    }
    
    const id = generateSnowFlake()
    const position = reactflow.screenToFlowPosition({
      x: 400,
      y: 200,
    })

    const currentNodeSchema = _.cloneDeep(item)
    const newNode = generateNewNode(currentNodeSchema, paramsName, id, position)

    if (isAddInGroup) {
      //  forhandlewhenat divisiongroupbodynewincreasenodeback，continuenewincreasenodeshould还yesat divisiongroupinside
      const parentId = isLoopBody ? selectedNodeId || undefined : selectedNode?.parentId
      newNode.parentId = parentId
      newNode.expandParent = true
      newNode.extent = "parent"
      newNode.meta = {
        position: {
          x: 100,
          y: 200,
        },
        parent_id: parentId,
      }
    }

    newNodes.push(newNode)
    const edges = reactflow?.getEdges?.() || []
    //  Ifnewincreaseofyesloop，thenneedmanynewincreaseCHSitemloop体 and CHS条边
    if (judgeLoopNode(newNode[paramsName.nodeType])) {
      const { newNodes: bodyNodes, newEdges: bodyEdges } = generateLoopBody(
        newNode,
        paramsName,
        edges,
      )
      newNodes.push(...bodyNodes)
      newEdges.push(...bodyEdges)
    }

    addNode(newNodes, newEdges)
  })

  return { onAddItem }
}

export default useAddItem 
