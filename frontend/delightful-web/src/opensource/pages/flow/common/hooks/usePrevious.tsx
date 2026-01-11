/**
 * Common hooks for all nodes, used to calculate data sources that can be referenced by preceding nodes
 */

import { cloneDeep, get, set, uniqBy } from "lodash-es"
import {
	useFlowData,
	useFlowEdges,
	useNodeConfig,
} from "@bedelightful/delightful-flow/dist/DelightfulFlow/context/FlowContext/useFlow"
import { useCurrentNode } from "@bedelightful/delightful-flow/dist/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import type { NodeSchema } from "@bedelightful/delightful-flow/dist/DelightfulFlow/register/node"
import { nodeManager } from "@bedelightful/delightful-flow/dist/DelightfulFlow/register/node"
import {
	schemaToDataSource,
	judgeIsVariableNode,
	getNodeVersion,
} from "@bedelightful/delightful-flow/dist/DelightfulFlow/utils"
import { getAllPredecessors } from "@bedelightful/delightful-flow/dist/DelightfulFlow/utils/reactflowUtils"
import type { DataSourceOption } from "@bedelightful/delightful-flow/dist/common/BaseUI/DropdownRenderer/Reference"
import { useMemo } from "react"
import { useMemoizedFn } from "ahooks"
import type { DelightfulFlow } from "@bedelightful/delightful-flow/dist/DelightfulFlow/types/flow"
import type Schema from "@bedelightful/delightful-flow/dist/DelightfulJsonSchemaEditor/types/Schema"
import { useNodeMap } from "@bedelightful/delightful-flow/dist/common/context/NodeMap/useResize"
import { useFlowStore } from "@/opensource/stores/flow"
import { DefaultNodeVersion } from "@bedelightful/delightful-flow/dist/DelightfulFlow/constants"
import { useBotStore } from "@/opensource/stores/bot"
import { InstructionType } from "@/types/bot"
import { useTranslation } from "react-i18next"
import { customNodeType, DynamicOutputNodeTypes } from "../../constants"
import { checkIsInLoop, mergeOptionsIntoOne } from "../../utils/helpers"
import { generateLoopItemOptions } from "./helpers"
import { LoopTypes } from "../../nodes/Loop/v0/components/LoopTypeSelect"

export default function usePrevious() {
	const { flow } = useFlowData()
	const { edges } = useFlowEdges()
	const { nodeConfig } = useNodeConfig()
	const { currentNode } = useCurrentNode()
	const { nodeMap: nodeSchemaMap } = useNodeMap()
	const { instructList } = useBotStore()
	const { t } = useTranslation()
	const { methodsDataSource } = useFlowStore()

	const updateVariableOption = useMemoizedFn(
		(
			currentOptions: DataSourceOption[],
			curNode: DelightfulFlow.Node | any,
			pointerVariables?: Schema,
		) => {
			// Temporarily treat cases with specified variable schema as environment variables
			const isGlobalVariable = !!pointerVariables

			const cloneOptions = cloneDeep(currentOptions)

			let variableOption = cloneOptions.find(
				(option) => option.nodeType === customNodeType.VariableSave,
			)

			const variables =
				pointerVariables || get(curNode, ["params", "variables", "form", "structure"], null)

			const id = isGlobalVariable ? "variables" : curNode.node_id

			const nodeVersion = getNodeVersion(curNode)

			const option = schemaToDataSource(
				{
					...nodeSchemaMap[customNodeType.VariableSave]?.[nodeVersion]?.schema,
					type: customNodeType.VariableSave,
					id,
					label: curNode?.name as string,
				},
				// @ts-ignore
				variables,
				true,
				isGlobalVariable,
			)

			/** If variable option doesn't exist, create a new one */
			if (!variableOption) {
				variableOption = {
					...option,
					title: "Variables",
				}
				// Push to the beginning
				cloneOptions.unshift(variableOption as DataSourceOption)
			} else {
				// console.log("option", option)
				/** Result after deduplicating same variable names */
				const uniqOptions = uniqBy(
					[...(variableOption.children || []), ...(option.children || [])],
					"key",
				)
				/** Otherwise add new values to variable options */
				set(variableOption, ["children"], uniqOptions)
			}

			return cloneOptions
		},
	)

	const filterCanReferenceNodes = useMemoizedFn((allNodes: DelightfulFlow.Node[]) => {
		const { canReferenceNodeTypes } = nodeManager
		return allNodes.filter((n) => {
			return canReferenceNodeTypes.includes(`${n.node_type}`)
		})
	})

	// Dynamically generate data sources
	const generateDynamicSource = useMemoizedFn(
		(outputs: Schema[], currentNodeSchema: NodeSchema, cur: DelightfulFlow.Node, suffix: string) => {
			return outputs.map((output) => {
				return schemaToDataSource(
					{
						...currentNodeSchema,
						type: currentNodeSchema.id,
						id: `${cur.node_id}${suffix}`,
						label: cur.name as string,
					},
					output,
				)
			})
		},
	)

	// Process flow instructions and generate data sources
	const generateInstructionsDataSource = useMemoizedFn(() => {
		// If there's no instruction data or current node doesn't exist, return empty array
		if (!instructList?.length || !currentNode) return []

		// Get flow instructions (instructions with instruction_type 1)
		const flowInstructions = instructList.flatMap((group) => {
			return group.items.filter(
				(item) => "instruction_type" in item && item.instruction_type === 1,
			)
		})

		if (!flowInstructions.length) return []

		// Create instruction data source, use any type to bypass type checking
		const instructionsSchema: any = {
			type: "object",
			key: "instructs",
			sort: 0,
			title: t("flow:flowInstructions.flowInstructions"),
			description: "",
			required: [],
			value: null,
			encryption: false,
			encryption_value: "",
			items: undefined,
			properties: {},
		}

		// Build different schemas for different instruction types
		flowInstructions.forEach((instruction) => {
			if ("type" in instruction) {
				// Create different schemas based on instruction type
				switch (instruction.type) {
					case InstructionType.SINGLE_CHOICE: {
						// Single choice instruction
						if ("values" in instruction) {
							// Create an object containing name and value properties
							const instructionObj: any = {
								type: "object",
								key: instruction.id,
								sort: 0,
								title: instruction.name,
								description: instruction.description || "",
								required: [],
								value: null,
								encryption: false,
								encryption_value: "",
								items: undefined,
								properties: {
									name: {
										type: "string",
										key: "name",
										sort: 0,
										title: `${t("flow:flowInstructions.instructionName")}`,
										description: t(
											"flow:flowInstructions.singleChoiceInstructionName",
										),
										required: [],
										value: null,
										encryption: false,
										encryption_value: "",
										items: undefined,
										properties: undefined,
									},
									value: {
										type: "string",
										key: "value",
										sort: 1,
										title: `${t("flow:flowInstructions.instructionValue")}`,
										description: t(
											"flow:flowInstructions.singleChoiceInstructionValue",
										),
										required: [],
										value: null,
										encryption: false,
										encryption_value: "",
										items: undefined,
										properties: undefined,
									},
								},
							}
							instructionsSchema.properties![instruction.id] = instructionObj
						}
						break
					}
					case InstructionType.SWITCH: {
						// Switch instruction - also contains name and value properties
						const instructionObj: any = {
							type: "object",
							key: instruction.id,
							sort: 0,
							title: instruction.name,
							description: instruction.description || "",
							required: [],
							value: null,
							encryption: false,
							encryption_value: "",
							items: undefined,
							properties: {
								name: {
									type: "string",
									key: "name",
									sort: 0,
									title: `${t("flow:flowInstructions.instructionStatus")}`,
									description: t("flow:flowInstructions.switchInstructionName"),
									required: [],
									value: null,
									encryption: false,
									encryption_value: "",
									items: undefined,
									properties: undefined,
								},
								value: {
									type: "string",
									key: "value",
									sort: 1,
									title: `${t("flow:flowInstructions.instructionValue")}`,
									description: t("flow:flowInstructions.switchInstructionValue"),
									required: [],
									value: null,
									encryption: false,
									encryption_value: "",
									items: undefined,
									properties: undefined,
								},
							},
						}
						instructionsSchema.properties![instruction.id] = instructionObj
						break
					}
					default:
						// By default, don't handle other types
						break
				}
			}
		})

		// Only generate data source when valid flow instructions exist
		if (Object.keys(instructionsSchema.properties!).length === 0) return []

		// Generate instruction data source
		const instructionsDataSource = schemaToDataSource(
			{
				...nodeSchemaMap[customNodeType.Instructions]?.v0?.schema,
				type: customNodeType.Instructions,
				id: "instructions",
				label: t("flow:flowInstructions.flowInstructions"),
			},
			instructionsSchema,
			true,
			true, // Global level
		)

		return [instructionsDataSource]
	})

	const expressionDataSource = useMemo(() => {
		if (!currentNode) return []
		const nodes = Object.values(nodeConfig)
		let allPreNodes = getAllPredecessors(currentNode, nodes, edges)
		// If it's a node in loop body, referenceable data sources are the preceding nodes of current node + preceding nodes of loop body
		if (checkIsInLoop(currentNode)) {
			const loopBodyNode = nodeConfig?.[currentNode?.meta?.parent_id]
			const loopBodyAllPrevNodes = getAllPredecessors(loopBodyNode, nodes, edges)
			allPreNodes = [...loopBodyAllPrevNodes, ...allPreNodes]
		}
		// Filter out nodes that don't need to be referenced
		allPreNodes = filterCanReferenceNodes(allPreNodes)
		// Deduplicate by id
		const uniquePreNodes = uniqBy(allPreNodes, "node_id")
		// console.log(currentNode?.node_id, allPreNodes)
		let expressionSources = uniquePreNodes.reduce((acc, cur) => {
			let output = [cur?.output?.form]
			let systemOutputs = [cur?.system_output?.form?.structure]
			let customSystemOutputs = [cur?.custom_system_output?.form?.structure]

			// If it's a branch node, need to get output from branches
			if (nodeManager.branchNodeIds.includes(`${cur.node_type}`)) {
				// getAllPredecessors calculated outputBranchIds, i.e., A->B, list of branch ids from A
				output = cur?.params?.outputBranchIds?.map((branchId: string) => {
					const targetBranch = cur?.params?.branches?.find(
						(branch: any) => branch.branch_id === branchId,
					)
					// Handle branch-level system output
					// @ts-ignore
					systemOutputs.push(targetBranch?.system_output?.form?.structure)
					// @ts-ignore
					customSystemOutputs.push(targetBranch?.custom_system_output?.form?.structure)
					// Filter out empty outputs
					return targetBranch?.output?.form
				})
			}
			// Filter out empty system outputs
			systemOutputs = systemOutputs.filter((systemOutput) => !!systemOutput)
			// Filter out empty custom system outputs
			customSystemOutputs = customSystemOutputs.filter((systemOutput) => !!systemOutput)
			const nodeVersion = getNodeVersion(cur)
			const currentNodeSchema = get(
				nodeManager.nodesMap,
				[cur.node_type, nodeVersion, "schema"],
				null,
			)

			if (output.length === 0 || !currentNodeSchema) return acc

			// A with multiple endpoints connecting to B, need to classify by branches
			if (output.length > 1) {
				const options = [] as DataSourceOption[]
				output?.forEach((branchOutput) => {
					let schema = branchOutput?.structure

					// If it's a node with dynamically generated output, get its output
					if (DynamicOutputNodeTypes.includes(cur?.node_type as customNodeType)) {
						schema = cur?.output?.form?.structure
					}
					if (!schema) return
					// Add system-level outputs
					if (systemOutputs.length > 0) {
						options.push(
							...generateDynamicSource(
								systemOutputs,
								currentNodeSchema,
								cur,
								"_system",
							),
						)
					}

					// Add custom system-level outputs
					if (customSystemOutputs.length > 0) {
						options.push(
							...generateDynamicSource(
								customSystemOutputs,
								currentNodeSchema,
								cur,
								"_custom_system",
							),
						)
					}

					options.push(...generateDynamicSource([schema!], currentNodeSchema, cur, ""))
				})
				// Merged result of multiple branch outputs from A
				const resultOption = mergeOptionsIntoOne(options)
				acc = [...acc, resultOption]
			}
			// Case where A -> B has only one endpoint, no need to distinguish branches
			else {
				const options = [] as DataSourceOption[]
				let schema = output[0]?.structure
				/** Special handling for variable type nodes, don't convert, instead categorize under "Variables" */
				const isVariableNode = judgeIsVariableNode(currentNodeSchema.id)

				if (!schema && !isVariableNode) return acc
				// If it's a node with dynamically generated output, get its output
				if (DynamicOutputNodeTypes.includes(cur?.node_type as customNodeType)) {
					schema = cur?.output?.form?.structure
				}
				if (!schema && !isVariableNode) return acc

				// Add system-level output data sources
				if (systemOutputs.length > 0) {
					options.push(
						...generateDynamicSource(systemOutputs, currentNodeSchema, cur, "_system"),
					)
				}
				// Add custom system-level outputs
				if (customSystemOutputs.length > 0) {
					options.push(
						...generateDynamicSource(
							customSystemOutputs,
							currentNodeSchema,
							cur,
							"_custom_system",
						),
					)
				}

				if (isVariableNode) {
					acc = updateVariableOption(acc, cur)
				} else {
					options.push(...generateDynamicSource([schema!], currentNodeSchema, cur, ""))
					const resultOption = mergeOptionsIntoOne(options)
					acc = acc.concat(resultOption)
				}
			}

			return [...acc]
		}, [] as DataSourceOption[])

		// If it's a node in loop body, need to manually add hardcoded referenceable items like item and index at the beginning
		if (checkIsInLoop(currentNode)) {
			const loopBodyNode = nodeConfig?.[currentNode?.meta?.parent_id]
			const loopNode = nodeConfig?.[loopBodyNode?.meta?.parent_id]
			// And when loop type is "Loop Array"
			const isLoopArrayType = loopNode?.params?.type === LoopTypes.Array
			if (loopNode && isLoopArrayType) {
				const extraLoopDataSource = generateLoopItemOptions(loopNode, nodeSchemaMap)
				expressionSources.unshift(extraLoopDataSource)
			}
		}

		// If environment variables exist, need to update data sources
		if (flow?.global_variable) {
			const variableSchema = get(
				nodeManager,
				["nodesMap", customNodeType.VariableSave, DefaultNodeVersion, "schema"],
				null,
			)
			// Ensure variableSchema is not undefined before passing to updateVariableOption
			if (variableSchema) {
				expressionSources = updateVariableOption(
					expressionSources,
					// @ts-ignore
					variableSchema,
					flow?.global_variable?.structure,
				)
			}
		}

		// Add flow instruction data sources
		const instructionsDataSource = generateInstructionsDataSource()
		if (instructionsDataSource.length > 0) {
			expressionSources.unshift(...instructionsDataSource)
		}

		// console.log("expressionDataSource", expressionSources)

		// Add function data sources at the end
		expressionSources.push(...methodsDataSource)

		return expressionSources
	}, [
		currentNode,
		nodeConfig,
		edges,
		filterCanReferenceNodes,
		flow?.global_variable,
		generateInstructionsDataSource,
		methodsDataSource,
		generateDynamicSource,
		updateVariableOption,
		nodeSchemaMap,
	])

	return {
		expressionDataSource,
	}
}





