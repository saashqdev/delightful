import { useCurrentNode } from "@/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { ShowColumns } from "@/DelightfulJsonSchemaEditor/constants"
import Schema from "@/DelightfulJsonSchemaEditor/types/Schema"
import DropdownCard from "@/common/BaseUI/DropdownCard"
import JSONSchemaRenderer from "@/common/BaseUI/JSONSchemaRenderer"
import { DelightfulJsonSchemaEditor } from "@/index"
import { Form } from "antd"
import { useMount } from "ahooks"
import _ from "lodash"
import React, { useState } from "react"
import usePrevious from "../../../common/hooks/usePrevious"
import styles from "./index.module.less"

export default function SubFlowV0() {
	const [form] = Form.useForm()
	const { currentNode } = useCurrentNode()
	const [input, setInput] = useState<Schema>()

	const { expressionDataSource } = usePrevious()

	/** Synchronize upstream to downstream */
	useMount(() => {
		const serverInput = _.get(currentNode, ["input", "form", "structure"])
		setInput(serverInput)
	})

	return (
		<Form form={form} className={styles.subFlow} layout="vertical">
			<div className={styles.input}>
					<DropdownCard title="Input" height="auto">
					<DelightfulJsonSchemaEditor
						data={input}
						onChange={setInput}
						allowExpression
						expressionSource={expressionDataSource}
						displayColumns={[ShowColumns.Key, ShowColumns.Value, ShowColumns.Type]}
						columnNames={{
							[ShowColumns.Key]: "Variable Name",
							[ShowColumns.Type]: "Variable Type",
							[ShowColumns.Value]: "Variable Value",
							[ShowColumns.Label]: "Display Name",
							[ShowColumns.Description]: "Variable Description",
							[ShowColumns.Encryption]: "Encrypted",
							[ShowColumns.Required]: "Required",
						}}
					/>
				</DropdownCard>
			</div>

			<div className={styles.output}>
				<DropdownCard title="Output" height="auto">
					{/* @ts-ignore */}
					<JSONSchemaRenderer form={currentNode?.output?.form?.structure} />
				</DropdownCard>
			</div>
		</Form>
	)
}

