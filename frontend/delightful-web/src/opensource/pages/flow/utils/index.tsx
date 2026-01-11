import {
	installNodes,
	registerBranchNodes,
	registerCanReferenceNodeTypes,
	registerLoopBodyType,
	registerLoopNodeType,
	registerLoopStartType,
	registerNodeType2ReferenceKey,
	registerVariableNodeTypes,
	// @ts-ignore
	registerNodeGroups,
	registerLoopStartConfig,
	registerMaterialNodeTypeMap,
} from "@delightful/delightful-flow/dist/DelightfulFlow/register/node"
import {
	IconAlignBoxLeftMiddle,
	IconDatabase,
	IconFileTextAi,
	IconHistory,
	IconVariable,
	IconVocabulary,
} from "@tabler/icons-react"
import i18next from "i18next"
import { TabObject } from "@delightful/delightful-flow/dist/DelightfulFlow/components/FlowMaterialPanel/constants"
import { customNodeType } from "../constants"
import { generateNodeVersionSchema } from "./version"
import { BaseFlowProps } from ".."

export const installAllNodes = (extraData?: BaseFlowProps["extraData"]) => {
	/**
	 * Register branch nodes, to follow branch-related paths, next_nodes will be added to branches
	 */
	registerBranchNodes([
		customNodeType.Start,
		customNodeType.If,
		customNodeType.IntentionRecognition,
	])

	/**
	 * Register referenceable node types, used as basis when calculating upstream node referenceable data sources:
	 *  Nodes not in this list will not be treated as referenceable node types
	 */
	registerCanReferenceNodeTypes([
		customNodeType.Start,
		customNodeType.Code,
		customNodeType.LLM,
		customNodeType.VariableSave,
		customNodeType.Loader,
		customNodeType.HTTP,
		customNodeType.Sub,
		customNodeType.VectorSearch,
		customNodeType.MessageSearch,
		customNodeType.CacheGetter,
		customNodeType.TextSplit,
		customNodeType.WaitForReply,
		customNodeType.SearchUsers,
		customNodeType.Excel,
		customNodeType.Tools,
		customNodeType.VectorDatabaseMatch,
		customNodeType.Text2Image,
		customNodeType.Instructions,
		...(extraData?.canReferenceNodeTypes || []),
	])

	/** When referencing LLM, use LLM.value instead of node_id.value */
	registerVariableNodeTypes([customNodeType.VariableSave])

	/** When referencing variables, use variable.xxx instead of [node_id].xxx */
	registerNodeType2ReferenceKey({
		[customNodeType.VariableSave]: "variables",
		[customNodeType.Instructions]: "instructions",
	})

	/** When specifying start node, a start node will be added by default on blank canvas */
	// registerStartNodeType(customNodeType.Start)

	/** Specify loop node, will carry a loop body by default */
	registerLoopNodeType(customNodeType.Loop)

	/** Specify loop body type */
	registerLoopBodyType(customNodeType.LoopBody)

	/** Specify loop start node type */
	registerLoopStartType(customNodeType.Start)

	/** Register loop start node configuration */
	registerLoopStartConfig({
		height: 128,
	})

	registerNodeGroups([
		{
			groupName: i18next.t("common.base", { ns: "flow" }),
			desc: "",
			nodeTypes: [
				customNodeType.Start,
				customNodeType.End,
				customNodeType.ReplyMessage,
				customNodeType.WaitForReply,
			],
		},

		{
			groupName: i18next.t("common.bigModel", { ns: "flow" }),
			desc: "",
			nodeTypes: [
				customNodeType.LLM,
				// customNodeType.LLMCall,
				customNodeType.IntentionRecognition,
			],
		},

		{
			groupName: i18next.t("common.operation", { ns: "flow" }),
			desc: "",
			nodeTypes: [
				customNodeType.If,
				customNodeType.Code,
				customNodeType.HTTP,
				customNodeType.Tools,
				customNodeType.Sub,
				customNodeType.Loop,
				customNodeType.LoopEnd,
				customNodeType.SearchUsers,
				...(extraData?.extraNodeInfos?.operation ?? []),
				customNodeType.Text2Image,
				customNodeType.GroupChat,
			],
		},

		{
			groupName: i18next.t("common.dataHandler", { ns: "flow" }),
			desc: "",
			nodeTypes: [
				customNodeType.MessageSearch,
				customNodeType.MessageMemory,
				customNodeType.VectorSearch,
			],
			children: [
				{
					groupName: i18next.t("historyMessage.name", { ns: "flow" }),
					desc: i18next.t("historyMessage.desc", { ns: "flow" }),
					icon: <IconHistory color="#fff" stroke={1} size={18} />,
					color: "#6431E5",
					nodeTypes: [customNodeType.MessageSearch, customNodeType.MessageMemory],
				},

				{
					groupName: i18next.t("vectorDatabase.name", { ns: "flow" }),
					desc: i18next.t("vectorDatabase.desc", { ns: "flow" }),
					color: "#007EFF",
					icon: <IconFileTextAi color="#fff" stroke={2} size={18} />,
					nodeTypes: [
						customNodeType.VectorStorage,
						customNodeType.VectorSearch,
						customNodeType.VectorDelete,
						customNodeType.VectorDatabaseMatch,
					],
				},

				{
					groupName: i18next.t("cache.name", { ns: "flow" }),
					desc: i18next.t("cache.desc", { ns: "flow" }),
					color: "#00C1D2",
					icon: <IconDatabase color="#fff" stroke={2} size={18} />,
					nodeTypes: [customNodeType.CacheSetter, customNodeType.CacheGetter],
				},

				{
					groupName: i18next.t("variable.name", { ns: "flow" }),
					desc: i18next.t("variable.desc", { ns: "flow" }),
					color: "#FFC900",
					icon: <IconVariable color="#fff" stroke={2} size={18} />,
					nodeTypes: [customNodeType.VariableSave],
				},

				...(extraData?.extraNodeInfos?.dataHandler?.delightfulTable ?? []),

				{
					groupName: i18next.t("file.name", { ns: "flow" }),
					desc: i18next.t("file.desc", { ns: "flow" }),
					color: "#00C1D2",
					icon: <IconVocabulary color="#fff" stroke={2} size={18} />,
					nodeTypes: [
						customNodeType.Loader,
						customNodeType.Excel,
						...(extraData?.extraNodeInfos?.dataHandler?.file ?? []),
					],
				},

				{
					groupName: i18next.t("text.name", { ns: "flow" }),
					desc: i18next.t("text.desc", { ns: "flow" }),
					color: "#00C1D2",
					icon: <IconAlignBoxLeftMiddle color="#fff" stroke={2} size={18} />,
					nodeTypes: [customNodeType.TextSplit],
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
	installNodes(
		generateNodeVersionSchema(
			extraData?.enterpriseNodeComponentVersionMap || {},
			extraData?.getEnterpriseSchemaConfigMap || (() => ({})),
			extraData?.enterpriseNodeTypes || {},
		),
	)
}





