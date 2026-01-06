import { useExternal } from "@/DelightfulFlow/context/ExternalContext/useExternal"
import { useFlow } from "@/DelightfulFlow/context/FlowContext/useFlow"
import { useDelightfulFlow } from "@/DelightfulFlow/context/DelightfulFlowContext/useDelightfulFlow"
import { InnerHandleType } from "@/DelightfulFlow/nodes"
import CustomHandle from "@/DelightfulFlow/nodes/common/Handle/Source"
import { useCurrentNode } from "@/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { judgeIsLoopBody } from "@/DelightfulFlow/utils"
import { Tooltip } from "antd"
import { IconInfoCircle } from "@tabler/icons-react"
import { useUpdateEffect } from "ahooks"
import React from "react"
import { customNodeType } from "../../../constants"
import styles from "./index.module.less"

export default function Loop() {
	const { currentNode } = useCurrentNode()

	const { selectedNodeId, nodeConfig } = useFlow()

	const { paramsName } = useExternal()

	const { displayMaterialTypes, updateDisplayMaterialType } = useDelightfulFlow()

	useUpdateEffect(() => {
		const selectedNode = nodeConfig?.[selectedNodeId!]
		const isLoopBody = judgeIsLoopBody(selectedNode?.[paramsName.nodeType])
		if (isLoopBody) {
			updateDisplayMaterialType([...displayMaterialTypes, customNodeType.LoopEnd])
		} else {
			updateDisplayMaterialType(
				displayMaterialTypes.filter((nodeType) => nodeType !== customNodeType.LoopEnd),
			)
		}
	}, [selectedNodeId])

	return (
		<div className={styles.loop}>
			<div className={styles.loopBody}>
				<span>循环体</span>
				<Tooltip title="用于编排循环逻辑">
					<IconInfoCircle stroke={1} width={16} height={16} />
				</Tooltip>
				<CustomHandle
					type="source"
					isConnectable
					nodeId={currentNode?.node_id || ""}
					isSelected
					id={InnerHandleType.LoopHandle}
				/>
			</div>
			<div className={styles.loopNext}>
				<span>循环结束后执行下一步</span>
				<CustomHandle
					type="source"
					isConnectable
					nodeId={currentNode?.node_id || ""}
					isSelected
					id={InnerHandleType.LoopNext}
				/>
			</div>
		</div>
	)
}

