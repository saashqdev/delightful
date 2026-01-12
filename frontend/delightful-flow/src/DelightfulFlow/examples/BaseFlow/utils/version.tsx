import { NodeSchema, NodeWidget } from "@/DelightfulFlow/register/node"
import { IconArrowLeftFromArc, IconArrowsSplit2, IconBrain, IconRepeat, IconRepeatOff, IconTimeline, IconVariable } from "@tabler/icons-react"
import _ from "lodash"
import React from "react"
import { customNodeType, templateMap } from "../constants"
import { nodeComponentVersionMap } from "../nodes"

const commonSchemaConfigMap: Record<string, NodeSchema> = {
	[customNodeType.Start]: {
		label: "startnode",
		icon: <IconArrowLeftFromArc color="#fff" stroke={2} size={18} />,
		color: "#315CEC",
		id: customNodeType.Start,
		desc: "When the following event is triggered, the flow will start executing from this module",
		handle: {
			withSourceHandle: false,
			withTargetHandle: false,
		},
		changeable: {
			nodeType: false,
			operation: true,
		},
		style: {
			width: "480px",
		},
	},
	[customNodeType.Sub]: {
		label: "subprocess",
		icon: <IconTimeline color="#fff" stroke={1} size={18} />,
		color: "#9DC900",
		id: customNodeType.Sub,
		desc: "Can assign partial functionality modules to subprocesses for orchestration, avoiding main flow from becoming too large",
		handle: {
			withSourceHandle: true,
			withTargetHandle: true,
		},
		style: {
			width: "800px",
		},
	},
	[customNodeType.VariableSave]: {
		label: "variable saving",
		icon: <IconVariable color="#fff" stroke={1} size={18} />,
		color: "#FFC900",
		id: customNodeType.VariableSave,
		desc: "Add new variables and assign values to them",
		handle: {
			withSourceHandle: true,
			withTargetHandle: true,
		},
		style: {
			width: "900px",
		},
	},
	[customNodeType.LLM]: {
		label: "AI Chat",
		icon: <IconBrain color="#fff" stroke={1} size={18} />,
		color: "#00A8FF",
		id: customNodeType.LLM,
		desc: "Call large language model, use variables and prompts to generate responses",
		handle: {
			withSourceHandle: true,
			withTargetHandle: true,
		},
		style: {
			width: "600px",
		},
	},
	[customNodeType.Tools]: {
		label: "tool",
		icon: <IconTimeline color="#fff" stroke={1} size={18} />,
		color: "#555761",
		id: customNodeType.Tools,
		desc: "tool",
		handle: {
			withSourceHandle: true,
			withTargetHandle: true,
		},
		style: {
			width: "600px",
		},
	},

	[customNodeType.Loop]: {
		label: "loop",
		icon: <IconRepeat color="#fff" stroke={1} size={18} />,
		color: "#A61CCB",
		id: customNodeType.Loop,
		desc: "loop",
		handle: {
			withSourceHandle: false,
			withTargetHandle: true,
		},
		style: {
			width: "800px",
		},
	},
	[customNodeType.LoopEnd]: {
		label: "loopend",
		icon: <IconRepeatOff color="#fff" stroke={1} size={18} />,
		color: "#7885ff",
		id: customNodeType.LoopEnd,
		desc: "loopend",
		handle: {
			withSourceHandle: false,
			withTargetHandle: true,
		},
		style: {
			width: "480px",
		},
		addable: false,
	},

	[customNodeType.LoopBody]: {
		label: "Loop Body",
		icon: <IconRepeatOff color="#fff" stroke={1} size={18} />,
		color: "#7885ff",
		id: customNodeType.LoopBody,
		desc: "Loop body",
		handle: {
			withSourceHandle: false,
			withTargetHandle: true,
		},
		style: {
			width: "480px",
		},
		addable: false,
	},

	[customNodeType.If]: {
		label: "selector",
		icon: <IconArrowsSplit2 color="#fff" stroke={1} size={18} />,
		color: "#FFA400",
		id: customNodeType.If,
		desc: "",
		handle: {
			withSourceHandle: false,
			withTargetHandle: true,
		},
		style: {
			width: "900px",
		},
	},
}

type Version = string

export const generateNodeVersionSchema = () => {
	const versionTemplate = Object.entries(templateMap).reduce(
		(accVersionTemplate, [nodeType, versionMap]) => {
			return {
				[nodeType]: Object.entries(versionMap).reduce(
					(versionTemplate, [version, nodeSchema]) => {
						/** Configuration that needs to be versioned */
                        // @ts-ignore
						const versionConfig = _.pick(nodeSchema as NodeSchema, [
							"input",
							"output",
							"params",
						])
						/** Get the component for specific version */
						const versionComp = _.get(nodeComponentVersionMap, [nodeType, version])
						versionTemplate[version] = {
							schema: {
								...commonSchemaConfigMap[nodeType],
								...versionConfig,
							},
							component: versionComp,
						}
						return versionTemplate
					},
					{} as Record<Version, NodeWidget>,
				),
				...accVersionTemplate,
			}
		},
		{},
	)
	console.log("versionTemplate", versionTemplate)
	return versionTemplate
}

