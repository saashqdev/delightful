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
	 * 注册分支node，为了走分支相关的path，next_nodes会加在branches里面
	 */
	registerBranchNodes([
		customNodeType.Start,
		customNodeType.If,
		customNodeType.IntentionRecognition,
	])

	/**
	 * 注册可饮用class型node，计算上文node可引用数据源时，会以此为依据：
	 *  不在此列的，不做为可以引用class型的node
	 */
	registerCanReferenceNodeTypes([
		customNodeType.Start,
		customNodeType.Code,
		customNodeType.LLM,
		customNodeType.Variable,
		customNodeType.Loader,
		// TODO Vertor可以引用什么
		customNodeType.Vector,
		customNodeType.HTTP,
		customNodeType.Sub,
		customNodeType.Knowledge,
		customNodeType.Tools,
	])

	/** 指定引用大语言模型时，不再是node_id.value  而是LLM.value */
	registerNodeType2ReferenceKey({
		[customNodeType.VariableSave]: "variables",
	})

	/** 指定引用大语言模型时，不再是node_id.value  而是LLM.value */
	registerVariableNodeTypes([customNodeType.VariableSave])

	/** 指定startnode时，空白画布默认会新增一个startnode */
	registerStartNodeType(customNodeType.Start)

	/** 指定loopnode，会默认携带一个loop体 */
	registerLoopNodeType(customNodeType.Loop)

	/** 指定loop体class型 */
	registerLoopBodyType(customNodeType.LoopBody)

	/** 指定loop起始Node type */
	registerLoopStartType(customNodeType.Start)

	/** 注册loop起始nodeconfiguration */
	registerLoopStartConfig({
		height: 128,
	})

	/** 注册node分组information */
	registerNodeGroups([
		{
			groupName: "基础",
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
			groupName: "数据handle",
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
					groupName: "多维table",
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
	 * 注册所有nodeschema
	 */
	// @ts-ignore
	installNodes(generateNodeVersionSchema())
}

