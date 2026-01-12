import { TabObject } from "@/DelightfulFlow/components/FlowMaterialPanel/constants"
import {
	installNodes,
	registerBranchNodes,
	registerCanReferenceNodeTypes,
	registerLoopBodyType,
	registerLoopNodeType,
	registerLoopStartConfig,
	registerLoopStartType,
	registerMaterialNodeTypeMap,
	registerNodeGroups,
	registerNodeType2ReferenceKey,
	registerStartNodeType,
	registerVariableNodeTypes,
} from "@/DelightfulFlow/register/node"
import { IconArrowLeftFromArc } from "@tabler/icons-react"
import React from "react"
import { customNodeType } from "../constants"
import { generateNodeVersionSchema } from "./version"

export const installAllNodes = () => {
	/**
	 * Register branch nodes. To follow branch-related paths, next_nodes will be added to branches
	 */
	registerBranchNodes([
		customNodeType.Start,
		customNodeType.If,
		customNodeType.IntentionRecognition,
	])

	/**
	 * Register referenceable class-type nodes. When calculating which nodes can be referenced as data sources:
	 *  Nodes not in this list will not be considered as referenceable class-type nodes
	 */
	registerCanReferenceNodeTypes([
		customNodeType.Start,
		customNodeType.Code,
		customNodeType.LLM,
		customNodeType.Variable,
		customNodeType.Loader,
		// TODO What can Vector reference
		customNodeType.Vector,
		customNodeType.HTTP,
		customNodeType.Sub,
		customNodeType.Knowledge,
		customNodeType.Tools,
	])

	/** When referencing large language models, it's no longer node_id.value but LLM.value */
	registerNodeType2ReferenceKey({
		[customNodeType.VariableSave]: "variables",
	})

	/** When referencing large language models, it's no longer node_id.value but LLM.value */
	registerVariableNodeTypes([customNodeType.VariableSave])

	/** When specifying start node, a blank canvas will add a start node by default */
	registerStartNodeType(customNodeType.Start)

	/** When specifying loop node, it will carry a loop body by default */
	registerLoopNodeType(customNodeType.Loop)

	/** Specify loop body class type */
	registerLoopBodyType(customNodeType.LoopBody)

	/** Specify loop start Node type */
	registerLoopStartType(customNodeType.Start)

	/** Register loop start node configuration */
	registerLoopStartConfig({
		height: 128,
	})

	/** Register node grouping information */
	registerNodeGroups([
		{
			groupName: "Basic",
			desc: "",
			nodeTypes: [
				customNodeType.Start,
				customNodeType.Code,
				customNodeType.Variable,
				customNodeType.SearchUsers,
				customNodeType.Tools,
				customNodeType.Sub,
				customNodeType.Loop,
			],
		},

		{
			groupName: "Data Processing",
			desc: "",
			nodeTypes: [
				customNodeType.Vector,
				customNodeType.HTTP,
				customNodeType.Knowledge,
				customNodeType.LLM,
				customNodeType.If,
			],
			children: [
				{
					groupName: "Multi-dimensional Table",
					desc: "",
					color: "#32C436",
					icon: <IconArrowLeftFromArc color="#fff" stroke={2} size={18} />,
					nodeTypes: [customNodeType.LLM, customNodeType.Tools, customNodeType.If],
				},
			],
		},
	])

	registerMaterialNodeTypeMap({
		[TabObject.Tools]: customNodeType.Tools,
		[TabObject.Flow]: customNodeType.Sub,
		[TabObject.Agent]: customNodeType.Agent,
	})

	/**
	 * Register all node schemas
	 */
	// @ts-ignore
	installNodes(generateNodeVersionSchema())
}

