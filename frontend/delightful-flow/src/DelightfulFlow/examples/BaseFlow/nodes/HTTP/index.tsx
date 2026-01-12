import { useFlow } from "@/DelightfulFlow/context/FlowContext/useFlow"
import { useCurrentNode } from "@/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { ShowColumns } from "@/DelightfulJsonSchemaEditor/constants"
import Schema from "@/DelightfulJsonSchemaEditor/types/Schema"
import DropdownCard from "@/common/BaseUI/DropdownCard"
import { DelightfulJsonSchemaEditor } from "@/index"
import { useUpdateEffect } from "ahooks"
import React, { useEffect, useState } from "react"
import usePrevious from "../../common/hooks/usePrevious"
import styles from "./index.module.less"

export default function HTTPNode() {
	const { expressionDataSource } = usePrevious()

	const { currentNode } = useCurrentNode()
	const { nodeConfig } = useFlow()

	const [output, setOutput] = useState<Schema>(
		// @ts-ignore
		currentNode?.output?.form?.structure,
	)

	// Sync from upstream to downstream
	useEffect(() => {
		if (!currentNode) return
		if (currentNode?.output?.form?.structure) setOutput(currentNode?.output?.form?.structure)
	}, [currentNode])

	// Sync from downstream to upstream
	useUpdateEffect(() => {
		if (!currentNode) return
		const currentNodeConfig = nodeConfig[currentNode?.node_id]
		if (!currentNodeConfig) return
		if (currentNodeConfig?.output) {
			currentNodeConfig.output.form.structure = output
		}
	}, [output])

	return (
		<div className={styles.http}>
			<DropdownCard title="Output" height="auto" headerClassWrapper={styles.output}>
				<DelightfulJsonSchemaEditor
					data={output}
					onChange={setOutput}
					allowExpression
					expressionSource={expressionDataSource}
					displayColumns={[
						ShowColumns.Key,
						ShowColumns.Label,
						ShowColumns.Type,
						ShowColumns.Value,
					]}
				/>
			</DropdownCard>
		</div>
	)
}

