/**
 * Common hooks for all nodes, used to calculate data sources that can be referenced by preceding nodes
 */

import { mockMethodsSource } from "@/DelightfulExpressionWidget/components/dataSource"
import { transformDataSource } from "@/DelightfulExpressionWidget/helpers"
import { useFlow } from "@/DelightfulFlow/context/FlowContext/useFlow"
import { useCurrentNode } from "@/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { nodeManager } from "@/DelightfulFlow/register/node"
import { DelightfulFlow } from "@/DelightfulFlow/types/flow"
import {
	getNodeSchema,
	getNodeVersion,
	judgeIsVariableNode,
	schemaToDataSource,
} from "@/DelightfulFlow/utils"
import { getAllPredecessors } from "@/DelightfulFlow/utils/reactflowUtils"
import { Schema } from "@/DelightfulJsonSchemaEditor/components/editor/genson-js"
import { DataSourceOption } from "@/common/BaseUI/DropdownRenderer/Reference"
import { useMemoizedFn } from "ahooks"
import _ from "lodash"
import { useMemo } from "react"
import { customNodeType } from "../../constants"

export default function usePrevious() {
	const { nodeConfig, edges, flow } = useFlow()
	const { currentNode } = useCurrentNode()

	const updateVariableOption = useMemoizedFn(
		(
			currentOptions: DataSourceOption[],
			currentNode: DelightfulFlow.Node,
			pointerVariables?: Schema,
		) => {
			// Temporarily set as global variable when there is a specified variable schema
			const isGlobalVariable = !!pointerVariables

			const cloneOptions = _.cloneDeep(currentOptions)

			let variableOption = cloneOptions.find(
				(option) => option.nodeType === customNodeType.VariableSave,
			)

			const variables =
				pointerVariables ||
				_.get(currentNode, ["params", "variables", "form", "structure"], null)

			const nodeSchema = getNodeSchema(
				customNodeType.VariableSave,
				getNodeVersion(currentNode),
			)

			const option = schemaToDataSource(
				{
					...nodeSchema,
					type: customNodeType.VariableSave,
					id: currentNode?.node_id,
					label: currentNode?.name as string,
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
					title: "Variable",
				}
				// Push to first position
				cloneOptions.unshift(variableOption)
			} else {
				/** Deduplicated result for same variable names */
				const uniqOptions = _.uniqBy(
					[...(variableOption.children || []), ...(option.children || [])],
					"key",
				)
				/** Otherwise add values to variable option */
				_.set(variableOption, ["children"], uniqOptions)
			}

			return cloneOptions
		},
	)

	// Merge multiple data source items into one, as they all belong to the same node
	const mergeOptionsIntoOne = useMemoizedFn((options: DataSourceOption[]): DataSourceOption => {
		return options.reduce((mergeResult, currentOption) => {
			const newChildren = [
				...(mergeResult.children || []),
				...(currentOption.children || []),
			] as DataSourceOption[]
				// Deduplicated result based on node id and key
			const uniqueChildren = _.uniqBy(newChildren, (obj) => `${obj.nodeId}_${obj.key}`)
			mergeResult = {
				...mergeResult,
				...currentOption,
				children: uniqueChildren,
			}
			return mergeResult
		}, {} as DataSourceOption)
	})

	const expressionDataSource = useMemo(() => {
		if (!currentNode) return []
		const nodes = Object.values(nodeConfig)
		const allPreNodes = getAllPredecessors(currentNode, nodes, edges)
		// console.log(currentNode?.node_id, allPreNodes)
		let expressionSources = allPreNodes.reduce((acc, cur) => {
			let output = [cur?.output?.form]
			const systemOutput = cur?.system_output?.form?.structure
			// console.log("cur", cur)
			// If it's a branch node, need to get output from branches
			if (nodeManager.branchNodeIds.includes(`${cur.node_type}`)) {
				// getAllPredecessors calculated outputBranchIds, i.e., A->B, A's branch id list
				output = cur?.params?.outputBranchIds?.map((branchId: string) => {
					return cur?.params?.branches?.find(
						(branch: any) => branch.branch_id === branchId,
					)?.output?.form
				})
			}

			const currentNodeSchema = getNodeSchema(cur.node_type, getNodeVersion(cur))

			if (output.length === 0 || !currentNodeSchema) return acc

			// TODO A has multiple endpoints connecting to B, need to categorize by branch
			if (output.length > 1) {
			}
			// A -> B has only one endpoint, no need to distinguish branches
			else {
				const schema = output[0]?.structure
				const options = []
				/** Special handling for variable type nodes, no longer convert, but categorize under 'Variable' */
				const isVariableNode = judgeIsVariableNode(currentNodeSchema.id)

				if (!schema && !isVariableNode) return acc

				// Add system-level output
				if (systemOutput) {
					options.push(
						schemaToDataSource(
							{
								...currentNodeSchema,
								type: currentNodeSchema.id,
								id: `${cur.node_id}_system`,
								label: cur.name as string,
							},
							systemOutput,
						),
					)
				}

				if (isVariableNode) {
					acc = updateVariableOption(acc, cur)
				} else {
					options.push(
						schemaToDataSource(
							{
								...currentNodeSchema,
								type: currentNodeSchema.id,
								id: cur.node_id,
								label: cur.name as string,
							},
							schema!,
						),
					)
					// Merged result of multiple A branch outputs
					const resultOption = mergeOptionsIntoOne(options)
					acc = acc.concat(resultOption)
				}
			}

			return [...acc]
		}, [] as DataSourceOption[])

		// If global variable exists, need to re-update data source
		if (flow?.global_variable) {
			const variableSchema = _.get(
				nodeManager,
				["nodesMap", customNodeType.VariableSave, "schema"],
				null,
			)
			expressionSources = updateVariableOption(
				expressionSources,
				// @ts-ignore
				variableSchema,
				flow?.global_variable?.structure,
			)
		}

		// @ts-ignore
		expressionSources.push(...transformDataSource(mockMethodsSource))

		// console.log("expressionDataSource", expressionSources)

		return expressionSources
	}, [nodeConfig, edges, currentNode])

	return {
		expressionDataSource,
	}
}

