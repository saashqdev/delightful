import { getNodeVersion, schemaToDataSource } from "@bedelightful/delightful-flow/dist/DelightfulFlow/utils"
import type { DelightfulFlow } from "@bedelightful/delightful-flow/dist/DelightfulFlow/types/flow"
import type { NodeVersionMap } from "@bedelightful/delightful-flow/dist/common/context/NodeMap/Context"
import { customNodeType } from "../../constants"

/** Generate data source for special referenceable items within loop body */
export const generateLoopItemOptions = (
	loopNode: DelightfulFlow.Node,
	nodeSchemaMap: NodeVersionMap,
) => {
	const nodeVersion = getNodeVersion(loopNode)
	const loopNodeSchema = nodeSchemaMap[customNodeType.Loop]?.[nodeVersion]?.schema
	const loopOutput = {
		type: "object",
		key: "root",
		sort: 0,
		title: "root node",
		description: "",
		required: ["item", "index"],
		value: null,
		items: null,
		properties: {
			item: {
				type: "",
				key: "item",
				sort: 0,
				title: "Current Item",
				description: "",
				required: null,
				value: null,
				items: null,
				properties: null,
			},
			index: {
				type: "number",
				key: "index",
				sort: 1,
				title: "Current Index",
				description: "",
				required: null,
				value: null,
				items: null,
				properties: null,
			},
		},
	}
	const option = schemaToDataSource(
		{
			...loopNodeSchema,
			type: loopNodeSchema.id,
			id: loopNode.node_id,
			label: loopNode.name as string,
		},
		// @ts-ignore
		loopOutput,
	)

	return option
}

export default {}





