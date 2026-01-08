// @ts-nocheck
/**
 * YAML to JSON conversion tool
 * Converts YAML DSL format to Flow JSON format
 */

import yaml from "js-yaml"
import { v4 as uuidv4 } from "uuid"
import { customNodeType } from "../constants"
import { DelightfulFlow } from "@delightful/delightful-flow/dist/DelightfulFlow/types/flow"
import { getLatestNodeVersion } from "@delightful/delightful-flow/dist/DelightfulFlow/utils"

interface Position {
	x: number
	y: number
}

interface Edge {
	id: string
	source: string
	target: string
	sourceHandle?: string
	targetHandle?: string
	type: string
	data?: any
	style?: any
	markerEnd?: any
	selected?: boolean
}

interface Node {
	id: string
	node_id: string
	node_type: string
	node_version: string
	name: string
	description?: string
	position: Position
	params: any
	meta: any
	next_nodes: string[]
	step: number
	data: any
	system_output: any
	debug?: any
	input?: any
	output?: any
}

interface Flow {
	id: string
	name: string
	description: string
	icon: string
	type: number
	tool_set_id: string
	edges: Edge[]
	nodes: Node[]
	global_variable: any
	enabled: boolean
	version_code: string
	creator?: string
	created_at?: string
	modifier?: string
	updated_at?: string
	creator_info?: any
	modifier_info?: any
	user_operation?: number
}

interface FlowDSL {
	flow: {
		id: string
		name: string
		description: string
		version: string
		type: string
		icon?: string
		enabled?: boolean
	}
	variables: any[]
	nodes: any[]
	edges: any[]
}

/**
 * Get node type number
 * @param nodeTypeName Node type name
 * @returns Flow node type number
 */
const getNodeTypeNumber = (nodeTypeName: string): string => {
	switch (nodeTypeName) {
		case "start":
			return customNodeType.Start
		case "llm":
			return customNodeType.LLM
		case "reply-message":
			return customNodeType.ReplyMessage
		case "if-else":
			return customNodeType.If
		case "code":
			return customNodeType.Code
		case "loader":
			return customNodeType.Loader
		case "http-request":
			return customNodeType.HTTP
		case "sub-flow":
			return customNodeType.Sub
		case "end":
			return customNodeType.End
		case "message-search":
			return customNodeType.MessageSearch
		case "text-split":
			return customNodeType.TextSplit
		case "vector-storage":
			return customNodeType.VectorStorage
		case "vector-search":
			return customNodeType.VectorSearch
		case "vector-delete":
			return customNodeType.VectorDelete
		case "cache-setter":
			return customNodeType.CacheSetter
		case "cache-getter":
			return customNodeType.CacheGetter
		case "message-memory":
			return customNodeType.MessageMemory
		case "variable-save":
			return customNodeType.VariableSave
		case "intention-recognition":
			return customNodeType.IntentionRecognition
		case "loop":
			return customNodeType.Loop
		case "loop-body":
			return customNodeType.LoopBody
		case "loop-end":
			return customNodeType.LoopEnd
		case "search-users":
			return customNodeType.SearchUsers
		case "wait-for-reply":
			return customNodeType.WaitForReply
		case "llm-call":
			return customNodeType.LLMCall
		case "add-record":
			return customNodeType.AddRecord
		case "update-record":
			return customNodeType.UpdateRecord
		case "find-record":
			return customNodeType.FindRecord
		case "delete-record":
			return customNodeType.DeleteRecord
		case "document-resolve":
			return customNodeType.DocumentResolve
		case "agent":
			return customNodeType.Agent
		case "excel":
			return customNodeType.Excel
		case "tool":
			return customNodeType.Tools
		case "vector-database-match":
			return customNodeType.VectorDatabaseMatch
		case "knowledge-search":
			return customNodeType.KnowledgeSearch
		case "text-to-image":
			return customNodeType.Text2Image
		case "group-chat":
			return customNodeType.GroupChat
		default:
			return customNodeType.Start
	}
}

/**
 * Get flow type number
 * @param typeName Flow type name
 * @returns Flow type number
 */
const getFlowTypeNumber = (typeName: string): number => {
	switch (typeName) {
		case "workflow":
			return 1
		case "chat":
			return 2
		default:
			return 1
	}
}

/**
 * Convert DSL variable array to Flow global variables
 * @param variables DSL variable array
 * @returns Flow global variables
 */
const convertToGlobalVariables = (variables: any[]): any => {
	if (!variables || !variables.length) return null

	const globalVariable = {
		variables: variables.map((variable) => ({
			name: variable.name,
			type: variable.type,
			default_value: variable.default,
			description: variable.description || "",
		})),
	}

	return globalVariable
}

/**
 * Restore simplified form structure to complete structure
 * @param simplifiedForm Simplified form data
 * @returns Restored complete form data
 */
const restoreFormStructure = (simplifiedForm: any): any => {
	// If not simplified or already complete structure, return directly
	if (!simplifiedForm || !simplifiedForm.fields) {
		return simplifiedForm
	}

	const result = {
		id: simplifiedForm.id,
		version: simplifiedForm.version,
		type: "form",
		structure: restoreSchema(simplifiedForm.fields),
	}

	return result
}

/**
 * Restore simplified Schema to complete structure
 * @param fields Simplified field list
 * @returns Restored complete Schema structure
 */
const restoreSchema = (fields: any[]): any => {
	if (!fields || !Array.isArray(fields)) return null

	const properties = {}
	const required = []

	// Process all fields
	fields.forEach((field, index) => {
		const fieldType =
			field.type && field.type.includes(":") ? field.type.split(":")[0] : field.type

		const fieldItemType =
			field.type && field.type.includes(":") ? field.type.split(":")[1] : null

		// Build properties
		const prop: any = {
			type: fieldType,
			key: field.name,
			sort: index,
			title: field.title || field.name,
			description: field.desc || "",
			required: null,
			value: field.value || null,
			encryption: false,
			encryption_value: null,
			items: null,
			properties: null,
		}

		// Handle required fields
		if (field.required) {
			required.push(field.name)
		}

		// Handle object type
		if (fieldType === "object" && field.fields) {
			prop.properties = {}
			const childSchema = restoreSchema(field.fields)
			prop.properties = childSchema.properties
			// Merge required fields
			if (childSchema.required && childSchema.required.length > 0) {
				prop.required = childSchema.required
			}
		}

		// Handle array type
		if (fieldType === "array") {
			if (fieldItemType === "object" && field.fields) {
				prop.items = {
					type: "object",
					key: field.name,
					sort: 0,
					title: field.itemTitle || field.title || "",
					description: field.desc || "",
					required: [],
					value: null,
					encryption: false,
					encryption_value: null,
					items: null,
					properties: {},
				}

				const childSchema = restoreSchema(field.fields)
				prop.items.properties = childSchema.properties

				// Set required fields
				if (childSchema.required && childSchema.required.length > 0) {
					prop.items.required = childSchema.required
				}
			} else if (fieldItemType) {
				prop.items = {
					type: fieldItemType,
					key: "",
					sort: 0,
					title: field.itemTitle || "",
					description: "",
					required: null,
					value: null,
					encryption: false,
					encryption_value: null,
					items: null,
					properties: null,
				}
			}
		}

		properties[field.name] = prop
	})

	// Build complete Schema
	return {
		type: "object",
		key: "root",
		sort: 0,
		title: "root node",
		description: "",
		required: required.length > 0 ? required : [],
		value: null,
		encryption: false,
		encryption_value: null,
		items: null,
		properties: properties,
	}
}

/**
 * Convert DSL node to Flow node
 * @param dslNode DSL node
 * @returns Flow node
 */
const convertToFlowNode = (dslNode: any): Node => {
	const result = {
		id: dslNode.node_id || dslNode.id,
		node_id: dslNode.node_id || dslNode.id,
		node_type: dslNode.node_type,
		node_version: dslNode.node_version || getLatestNodeVersion(nodeType),
		name: dslNode.name || "",
		description: dslNode.description || "",
		position: dslNode.position,
		params: { ...dslNode.params } || {},
		meta: dslNode.meta || {},
		next_nodes: dslNode.next_nodes || [],
		system_output: dslNode.system_output,
		debug: dslNode.debug,
		input: dslNode.input,
		output: dslNode.output,
	}

	// Process and restore form structure
	if (result.params) {
		// Restore input form
		if (result.params.input && result.params.input.form) {
			result.params.input = {
				...result.params.input,
				form: restoreFormStructure(result.params.input.form),
			}
		}

		// Restore output form
		if (result.params.output && result.params.output.form) {
			result.params.output = {
				...result.params.output,
				form: restoreFormStructure(result.params.output.form),
			}
		}

		// Restore system output form
		if (result.params.system_output && result.params.system_output.form) {
			result.params.system_output = {
				...result.params.system_output,
				form: restoreFormStructure(result.params.system_output.form),
			}
		}

		// Recursively process forms in branches
		if (result.params.branches && Array.isArray(result.params.branches)) {
			result.params.branches = result.params.branches.map((branch) => {
				const newBranch = { ...branch }

				// Process branch input
				if (branch.input && branch.input.form) {
					newBranch.input = {
						...branch.input,
						form: restoreFormStructure(branch.input.form),
					}
				}

				// Process branch output
				if (branch.output && branch.output.form) {
					newBranch.output = {
						...branch.output,
						form: restoreFormStructure(branch.output.form),
					}
				}

				// Process branch system output
				if (branch.system_output && branch.system_output.form) {
					newBranch.system_output = {
						...branch.system_output,
						form: restoreFormStructure(branch.system_output.form),
					}
				}

				// Process branch custom system output
				if (branch.custom_system_output && branch.custom_system_output.form) {
					newBranch.custom_system_output = {
						...branch.custom_system_output,
						form: restoreFormStructure(branch.custom_system_output.form),
					}
				}

				return newBranch
			})
		}
	}

	// When meta is empty array, use default position
	if (Array.isArray(result.meta) && result.meta.length === 0) {
		result.meta = { position: { x: 200, y: 200 } }
	}

	return result
}

/**
 * Convert DSL edge to Flow edge
 * @param dslEdge DSL edge
 * @returns Flow edge
 */
const convertToFlowEdge = (dslEdge: any): Edge => {
	return {
		id: dslEdge.id,
		source: dslEdge.source,
		target: dslEdge.target,
		sourceHandle: dslEdge.sourceHandle,
		targetHandle: dslEdge.targetHandle,
		type: "commonEdge",
		markerEnd: {
			type: "arrow",
			width: 20,
			height: 20,
			color: "#4d53e8",
		},
		style: {
			stroke: "#4d53e8",
			strokeWidth: 2,
		},
		data: {
			allowAddOnLine: true,
		},
	}
}

/**
 * Convert YAML DSL to Flow JSON
 * @param yamlDSL DSL object
 * @returns Flow JSON object
 */
export const yaml2json = (yamlDSL: FlowDSL): Flow => {
	try {
		// Convert basic information
		const flow: Flow = {
			id:
				yamlDSL.flow.id ||
				`DELIGHTFUL-FLOW-${uuidv4().replace(/-/g, "")}-${Date.now().toString().slice(-8)}`,
			name: yamlDSL.flow.name,
			description: yamlDSL.flow.description || "",
			icon: yamlDSL.flow.icon || "",
			type: getFlowTypeNumber(yamlDSL.flow.type),
			tool_set_id: "not_grouped",
			edges: yamlDSL.edges.map((edge) => convertToFlowEdge(edge)),
			nodes: yamlDSL.nodes.map((node) => convertToFlowNode(node)),
			global_variable: convertToGlobalVariables(yamlDSL.variables),
			enabled: yamlDSL.flow.enabled !== undefined ? yamlDSL.flow.enabled : true,
			version_code: yamlDSL.flow.version || "1.0.0",
			creator: null,
			created_at: new Date().toISOString().replace("T", " ").substring(0, 19),
			modifier: null,
			updated_at: new Date().toISOString().replace("T", " ").substring(0, 19),
			creator_info: null,
			modifier_info: null,
			user_operation: 1,
		}

		return flow
	} catch (error) {
		console.error("Failed to convert YAML to JSON:", error)
		throw new Error(`Failed to convert YAML to JSON: ${error.message}`)
	}
}

/**
 * Convert YAML string to Flow JSON object
 * @param yamlString YAML string
 * @returns Flow JSON object
 */
export const yamlString2json = (yamlString: string): Flow => {
	try {
		const yamlDSL = yaml.load(yamlString) as FlowDSL
		return yaml2json(yamlDSL)
	} catch (error) {
		console.error("Failed to parse YAML string:", error)
		throw new Error(`Failed to parse YAML string: ${error.message}`)
	}
}

/**
 * Convert YAML string to JSON string
 * @param yamlString YAML string
 * @returns JSON string
 */
export const yamlString2jsonString = (yamlString: string): string => {
	try {
		const flow = yamlString2json(yamlString)
		return JSON.stringify(flow, null, 2)
	} catch (error) {
		console.error("Failed to convert YAML string to JSON string:", error)
		throw new Error(`Failed to convert YAML string to JSON string: ${error.message}`)
	}
}

/**
 * Convert node YAML string to single Flow node object
 * @param nodeYamlString Node's YAML string
 * @returns Flow node object
 */
export const nodeYamlString2json = (nodeYamlString: string): DelightfulFlow.Node => {
	try {
		// Parse YAML string to object
		const dslNode = yaml.load(nodeYamlString) as any

		// Convert to Flow node
		return convertToFlowNode(dslNode)
	} catch (error) {
		console.error("Failed to convert node YAML string to node object:", error)
		throw new Error(`Failed to convert node YAML string to node object: ${error.message}`)
	}
}

/**
 * Convert node YAML string to node JSON string
 * @param nodeYamlString Node's YAML string
 * @returns Node's JSON string
 */
export const nodeYamlString2jsonString = (nodeYamlString: string): string => {
	try {
		const node = nodeYamlString2json(nodeYamlString)
		return JSON.stringify(node, null, 2)
	} catch (error) {
		console.error("Failed to convert node YAML string to node JSON string:", error)
		throw new Error(`Failed to convert node YAML string to node JSON string: ${error.message}`)
	}
}

// Update default export
export default {
	yaml2json,
	yamlString2json,
	yamlString2jsonString,
	nodeYamlString2json,
	nodeYamlString2jsonString,
}
