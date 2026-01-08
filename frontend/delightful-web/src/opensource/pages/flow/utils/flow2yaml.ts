//@ts-nocheck
/**
 * JSON to YAML Conversion Tool
 * Converts Flow format JSON to YAML DSL format
 */

import yaml from "js-yaml"
import { customNodeType } from "../constants"

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
 * Get readable node type name
 * @param flowNodeType Flow node type number
 * @returns Node type name
 */
const getNodeTypeName = (flowNodeType: string): string => {
	switch (flowNodeType) {
		case customNodeType.Start:
			return "start"
		case customNodeType.LLM:
			return "llm"
		case customNodeType.ReplyMessage:
			return "reply-message"
		case customNodeType.If:
			return "if-else"
		case customNodeType.Code:
			return "code"
		case customNodeType.Loader:
			return "loader"
		case customNodeType.HTTP:
			return "http-request"
		case customNodeType.Sub:
			return "sub-flow"
		case customNodeType.End:
			return "end"
		case customNodeType.MessageSearch:
			return "message-search"
		case customNodeType.TextSplit:
			return "text-split"
		case customNodeType.VectorStorage:
			return "vector-storage"
		case customNodeType.VectorSearch:
			return "vector-search"
		case customNodeType.VectorDelete:
			return "vector-delete"
		case customNodeType.CacheSetter:
			return "cache-setter"
		case customNodeType.CacheGetter:
			return "cache-getter"
		case customNodeType.MessageMemory:
			return "message-memory"
		case customNodeType.VariableSave:
			return "variable-save"
		case customNodeType.IntentionRecognition:
			return "intention-recognition"
		case customNodeType.Loop:
			return "loop"
		case customNodeType.LoopBody:
			return "loop-body"
		case customNodeType.LoopEnd:
			return "loop-end"
		case customNodeType.SearchUsers:
			return "search-users"
		case customNodeType.WaitForReply:
			return "wait-for-reply"
		case customNodeType.LLMCall:
			return "llm-call"
		case customNodeType.AddRecord:
			return "add-record"
		case customNodeType.UpdateRecord:
			return "update-record"
		case customNodeType.FindRecord:
			return "find-record"
		case customNodeType.DeleteRecord:
			return "delete-record"
		case customNodeType.DocumentResolve:
			return "document-resolve"
		case customNodeType.Agent:
			return "agent"
		case customNodeType.Excel:
			return "excel"
		case customNodeType.Tools:
			return "tool"
		case customNodeType.VectorDatabaseMatch:
			return "vector-database-match"
		case customNodeType.KnowledgeSearch:
			return "knowledge-search"
		case customNodeType.Text2Image:
			return "text-to-image"
		case customNodeType.GroupChat:
			return "group-chat"
		default:
			return "unknown"
	}
}

/**
 * Get flow type name
 * @param type Flow type number
 * @returns Flow type name
 */
const getFlowTypeName = (type: number): string => {
	switch (type) {
		case 1:
			return "workflow"
		case 2:
			return "chat"
		default:
			return "workflow"
	}
}

/**
 * Convert Flow global variables to DSL variable array
 * @param globalVariable Flow global variables
 * @returns DSL variable array
 */
const convertGlobalVariables = (globalVariable: any): any[] => {
	const variables = []

	if (!globalVariable) return variables

	// Handle global variables
	if (globalVariable.variables && Array.isArray(globalVariable.variables)) {
		globalVariable.variables.forEach((variable: any) => {
			variables.push({
				name: variable.name,
				type: variable.type,
				default: variable.default_value,
				description: variable.description || "",
			})
		})
	}

	return variables
}

/**
 * Simplify form structure, significantly reducing YAML data size
 * @param formData Original form data object
 * @returns Simplified form structure
 */
const simplifyFormStructure = (formData: any): any => {
	// If no form data or no structure, return directly
	if (!formData || !formData.structure) return formData

	const result = {
		id: formData.id,
		version: formData.version,
	}

	// Process structure
	if (formData.structure) {
		result["fields"] = simplifySchema(formData.structure)
	}

	return result
}

/**
 * Simplify Schema structure, recursively process objects and arrays
 * @param schema Schema object
 * @returns Simplified field list
 */
const simplifySchema = (schema: any): any => {
	if (!schema) return null

	// Handle object type
	if (schema.type === "object" && schema.properties) {
		const fields = []

		// Process all properties
		for (const [key, prop] of Object.entries(schema.properties)) {
			if (!prop) continue

			const field = {
				name: key,
				type: prop.type,
			}

			// Only keep fields with values
			if (prop.title && prop.title !== key) field["title"] = prop.title
			if (prop.description && prop.description.trim()) field["desc"] = prop.description

			// Handle required fields
			if (schema.required && schema.required.includes(key)) {
				field["required"] = true
			}

			// Handle default values
			if (prop.value !== null && prop.value !== undefined) {
				field["value"] = prop.value
			}

			// Handle child properties
			if (prop.type === "object" && prop.properties) {
				field["fields"] = simplifySchema(prop)
			}

			// Handle array items
			if (prop.type === "array" && prop.items) {
				if (prop.items.type === "object" && prop.items.properties) {
					field["type"] = `array:object`
					field["fields"] = simplifySchema(prop.items)
				} else {
					field["type"] = `array:${prop.items.type}`
					// If array items have extra properties
					if (prop.items.title) field["itemTitle"] = prop.items.title
				}
			}

			fields.push(field)
		}
		return fields
	}

	// Array type
	if (schema.type === "array" && schema.items) {
		if (schema.items.type === "object" && schema.items.properties) {
			return simplifySchema(schema.items)
		}
	}

	return []
}

/**
 * Convert Flow node to DSL node
 * @param flowNode Flow node
 * @returns DSL node
 */
const convertNode = (flowNode: Node): any => {
	const nodeType = getNodeTypeName(flowNode.node_type)
	const result = {
		id: flowNode.id,
		type: nodeType,
		name: flowNode.name || "",
		description: flowNode.description || "",
		position: flowNode.position,
		params: { ...flowNode.params },
		version: flowNode.node_version,
		next_nodes: flowNode.next_nodes,
	}

	// Process and simplify form structure
	if (flowNode.params) {
		// Simplify input form
		if (flowNode.params.input && flowNode.params.input.form) {
			result.params.input = {
				...flowNode.params.input,
				form: simplifyFormStructure(flowNode.params.input.form),
			}
		}

		// Simplify output form
		if (flowNode.params.output && flowNode.params.output.form) {
			result.params.output = {
				...flowNode.params.output,
				form: simplifyFormStructure(flowNode.params.output.form),
			}
		}

		// Simplify system output form
		if (flowNode.params.system_output && flowNode.params.system_output.form) {
			result.params.system_output = {
				...flowNode.params.system_output,
				form: simplifyFormStructure(flowNode.params.system_output.form),
			}
		}

		// Recursively process forms in branches
		if (flowNode.params.branches && Array.isArray(flowNode.params.branches)) {
			result.params.branches = flowNode.params.branches.map((branch) => {
				const newBranch = { ...branch }

				// Handle branch input
				if (branch.input && branch.input.form) {
					newBranch.input = {
						...branch.input,
						form: simplifyFormStructure(branch.input.form),
					}
				}

				// Handle branch output
				if (branch.output && branch.output.form) {
					newBranch.output = {
						...branch.output,
						form: simplifyFormStructure(branch.output.form),
					}
				}

				// Handle branch system output
				if (branch.system_output && branch.system_output.form) {
					newBranch.system_output = {
						...branch.system_output,
						form: simplifyFormStructure(branch.system_output.form),
					}
				}

				// Handle branch custom system output
				if (branch.custom_system_output && branch.custom_system_output.form) {
					newBranch.custom_system_output = {
						...branch.custom_system_output,
						form: simplifyFormStructure(branch.custom_system_output.form),
					}
				}

				return newBranch
			})
		}
	}

	return result
}

/**
 * Convert Flow edge to DSL edge
 * @param flowEdge Flow edge
 * @returns DSL edge
 */
const convertEdge = (flowEdge: Edge): any => {
	return {
		id: flowEdge.id,
		source: flowEdge.source,
		target: flowEdge.target,
		sourceHandle: flowEdge.sourceHandle,
		targetHandle: flowEdge.targetHandle,
	}
}

/**
 * Convert Flow JSON to YAML DSL
 * @param flow Flow JSON object
 * @returns FlowDSL object
 */
export const json2yaml = (flow: Flow): FlowDSL => {
	try {
		// Convert basic information
		const dsl: FlowDSL = {
			flow: {
				id: flow.id,
				name: flow.name,
				description: flow.description || "",
				version: flow.version_code || "1.0.0",
				type: getFlowTypeName(flow.type),
				icon: flow.icon,
				enabled: flow.enabled,
			},
			variables: convertGlobalVariables(flow.global_variable),
			nodes: flow.nodes.map((node) => convertNode(node)),
			edges: flow.edges.map((edge) => convertEdge(edge)),
		}

		return dsl
	} catch (error) {
		console.error("Failed to convert JSON to YAML:", error)
		throw new Error(`Failed to convert JSON to YAML: ${error.message}`)
	}
}

/**
 * Convert Flow JSON to YAML string
 * @param flow Flow JSON object
 * @returns YAML string
 */
export const json2yamlString = (flow: Flow): string => {
	try {
		const dsl = json2yaml(flow)
		return yaml.dump(dsl, { lineWidth: -1, noRefs: true })
	} catch (error) {
		console.error("Failed to convert JSON to YAML string:", error)
		throw new Error(`Failed to convert JSON to YAML string: ${error.message}`)
	}
}

/**
 * Convert from JSON string to YAML string
 * @param jsonString JSON string
 * @returns YAML string
 */
export const jsonStr2yamlString = (jsonString: string): string => {
	try {
		const flow = JSON.parse(jsonString) as Flow
		return json2yamlString(flow)
	} catch (error) {
		console.error("Failed to convert JSON string to YAML string:", error)
		throw new Error(`Failed to convert JSON string to YAML string: ${error.message}`)
	}
}

/**
 * Convert a single node from Flow format to DSL format
 * @param node Node in Flow format
 * @returns Node in DSL format
 */
export const nodeJson2yaml = (node: Node): any => {
	try {
		return convertNode(node)
	} catch (error) {
		console.error("Failed to convert node JSON to YAML:", error)
		throw new Error(`Failed to convert node JSON to YAML: ${error.message}`)
	}
}

/**
 * Convert a single node from Flow format to YAML string
 * @param node Node in Flow format
 * @returns YAML string
 */
export const nodeJson2yamlString = (node: Node): string => {
	try {
		const dslNode = nodeJson2yaml(node)
		return yaml.dump(dslNode, { lineWidth: -1, noRefs: true })
	} catch (error) {
		console.error("Failed to convert node JSON to YAML string:", error)
		throw new Error(`Failed to convert node JSON to YAML string: ${error.message}`)
	}
}

/**
 * Convert node JSON string to node YAML string
 * @param jsonString JSON string representing the node
 * @returns YAML string of the node
 */
export const nodeJsonStr2yamlString = (jsonString: string): string => {
	try {
		const node = JSON.parse(jsonString) as Node
		return nodeJson2yamlString(node)
	} catch (error) {
		console.error("Failed to convert node JSON string to YAML string:", error)
		throw new Error(`Failed to convert node JSON string to YAML string: ${error.message}`)
	}
}

export default {
	json2yaml,
	json2yamlString,
	jsonStr2yamlString,
	nodeJson2yaml,
	nodeJson2yamlString,
	nodeJsonStr2yamlString,
}
