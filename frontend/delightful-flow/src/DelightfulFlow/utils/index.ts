import { DataSourceOption } from "@/common/BaseUI/DropdownRenderer/Reference"
import { BaseNodeType, nodeManager, NodeSchema, NodeVersion, NodeVersionWidget, NodeWidget } from "../register/node"
import { FormItemType } from "@/DelightfulExpressionWidget/types"
import Schema from "@/DelightfulJsonSchemaEditor/types/Schema"
import _ from "lodash"
import { Edge, XYPosition } from "reactflow"
import { DelightfulFlow } from "../types/flow"
import { generateSnowFlake } from "@/common/utils/snowflake"
import { pasteTranslateSize } from "../nodes/common/toolbar/useToolbar"
import { SchemaValueSplitor } from "@/DelightfulJsonSchemaEditor/constants"
import { InnerHandleType, NodeModelType } from "../nodes"
import { defaultEdgeConfig } from "../edges"
import { DefaultNodeVersion, GROUP_MIN_DISTANCE, GROUP_TOP_GAP, NodeType } from "../constants"
import { Ranksep } from "./reactflowUtils"
import { flowStore } from "../store"
import i18next from "i18next"

export const useQuery = () => {
	return new URLSearchParams(window.location.search)
}

/** Handle render props for the base node model
 * DelightfulFlow.Node -> reactflow node
 */
export const handleRenderProps = (n: DelightfulFlow.Node, index: number, paramsName: DelightfulFlow.ParamsName) => {
	const nodeSchemaListMap = nodeManager.nodesMap
	const nodeType = n[paramsName.nodeType] as NodeType

	const isLoopBody = judgeIsLoopBody(nodeType)

	// Check whether node meta is empty (empty array or nullish)
	const isMetaEmpty = !n.meta || (Array.isArray(n.meta) && n.meta.length === 0)

	const nodeMeta = isMetaEmpty ? { position: { x: 0, y: 0 } } : n.meta

	// Current node properties such as icon and color
	let currentNodeProperties = _.get(nodeSchemaListMap, [nodeType, getNodeVersion(n)], {} as NodeWidget)

	// Add loop-specific properties to nodes inside loops
	addLoopProperties(n, paramsName)

	n.deletable = false
	n.id = n.node_id
	n.type = isLoopBody ? NodeModelType.Group : NodeModelType.CommonNode
	n.node_type = nodeType
	n.position = nodeMeta.position
	n.meta = nodeMeta
	n.name = n.name || currentNodeProperties?.schema?.label
	n.step = index
	n.data = {
		...n?.data,
		deletable: false,
		changeable: {
			// Allow switching node type by default
			nodeType: true,
			// Show node actions (copy/paste/delete) by default
			operation: true,
			...(currentNodeProperties?.schema?.changeable || {}),
		},
		handle: {
			// Include target handle by default
			withSourceHandle: true,
			// Include source handle by default
			operation: true,
			...(currentNodeProperties?.schema?.handle || {}),
		},
		style: {
			...(currentNodeProperties?.schema?.style || {}),
		},
		...currentNodeProperties?.schema,
        desc: n.remark || currentNodeProperties?.schema?.desc,
        icon: n?.data?.icon || currentNodeProperties?.schema?.icon,
		type: nodeType,
		index: index,
	}
}


export const getPxWidth = (text: string) => {
	// re-use canvas object for better performance
	const canvas = document.createElement("canvas")
	const context = canvas.getContext("2d")
	const fontFamily =
		"ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, \"Noto Sans\", sans-serif, \"Apple Color Emoji\", \"Segoe UI Emoji\", \"Segoe UI Symbol\", \"Noto Color Emoji\""
	const fontSize = 14
    if(!context) return 0
	context.font = `${fontSize}px ${fontFamily}`

	const metrics = context.measureText(text)

	return Math.abs(metrics.actualBoundingBoxLeft) + Math.abs(metrics.actualBoundingBoxRight)
}


/**
 * Copy a string to the clipboard
 */
export function copyToClipboard(text: string) {
	// Create a temporary textarea element
	const textarea = document.createElement("textarea")
	// Set its value to the text to copy
	textarea.value = text
	// Append to the DOM
	document.body.appendChild(textarea)
	// Select the textarea text
	textarea.select()
	// Copy to clipboard
	document.execCommand("copy")
	// Remove the temporary element
	document.body.removeChild(textarea)
}


// schema => single expression data source item
export function schemaToDataSource(currentNodeSchema: NodeSchema & {type: BaseNodeType}, schema: Schema, isRoot = true, isGlobal = false): DataSourceOption {
    let option = {} as DataSourceOption
	// Root node
    if(isRoot) {
        option = {
            title: currentNodeSchema.label,
            key: currentNodeSchema.id,
            nodeId: currentNodeSchema.id,
            nodeType: currentNodeSchema.type,
            type: FormItemType.Object,
            isRoot: true,
            children: [] as DataSourceOption[],
			rawSchema: schema,
			isGlobal: false,
            selectable: true
        }
    }else {
        option = {
            // @ts-ignore
            title: schema.title || schema.key || '',
            // @ts-ignore
            key: schema.key,
            nodeId: currentNodeSchema.id,
            nodeType: currentNodeSchema.type,
            type: schema.type,
            isRoot: false,
            children: [] as DataSourceOption[],
			rawSchema: schema,
			isGlobal,
            selectable: true
        }

    }
    

		// Array case: return directly
    if(schema?.type === FormItemType.Array) {
		// console.log("array schema", schema)
		const itemsType = schema?.items?.type
        option.type = `${FormItemType.Array}${SchemaValueSplitor}${itemsType}`
        return option
    }
		// Object case: convert each child field to a child option
    Object.entries(schema?.properties || {}).forEach(([key,field]) => {
        // @ts-ignore
        option.children?.push(schemaToDataSource(currentNodeSchema, {...field, key: isRoot ? key : `${option.key}.${key}`}, false, isGlobal))
    })

    return option
}

/** Get the prefix for referencing upstream values */
export const getReferencePrefix = (node: any) => {
	if(node?.isConstant) return null
	const { nodeType2ReferenceKey } = nodeManager
	/** Node types with hard-coded prefixes */
	let prefix = node.nodeId
	if (Reflect.has(nodeType2ReferenceKey, node.nodeType)) {
		prefix = Reflect.get(nodeType2ReferenceKey, node.nodeType)
	}
	return prefix
}

/** Determine whether the node type is a variable node */
export const judgeIsVariableNode = (nodeType: string | number) => {
	
	const variableNodeTypes = nodeManager.variableNodeTypes

	const isVariableNode = variableNodeTypes.includes(nodeType)

	return isVariableNode
}

/** Determine whether the node type is a loop body */
export const judgeIsLoopBody = (nodeType: string | number) => {
	
	const loopBodyType = nodeManager.loopBodyType
	return nodeType == loopBodyType
}
/** Get the schema for a given node type */
export const getNodeSchema = (nodeType: string | number, nodeVersion?: NodeVersion): NodeSchema => {
	
	const { nodeVersionSchema } = flowStore.getState()

    const version = nodeVersion || getLatestNodeVersion(nodeType) as string
	console.time("clone_schema")
	const result = _.get(nodeVersionSchema, [nodeType, version, 'schema'])
	console.timeEnd("clone_schema")
	return result

}


/** Get global node groups */
export const getNodeGroups = () => {
	
	const { nodeGroups  } = nodeManager
	const result = _.cloneDeep(nodeGroups)
	return result

}

/** Determine whether this is a loop node */
export const judgeLoopNode = (nodeType: string | number) => {
	
	const loopNodeType = nodeManager.loopNodeType

	return nodeType == loopNodeType
}



/**
 * Generate a node instance from a node schema
 * @param nodeSchema node schema
 * @param paramsName customized parameter names
 * @params nodeId generated node id
 * @params extraConfig additional node configuration
 */
export const generateNewNode = (nodeSchema: NodeSchema, paramsName: DelightfulFlow.ParamsName, nodeId: string, position: XYPosition, extraConfig?: Record<string, any>) => {
	const defaultParams = _.get(nodeSchema, [paramsName.params], null)

	console.time("clone_node")
	const newNode = {
		data: {},
		[paramsName.params]: _.cloneDeep(defaultParams),
		id: nodeId,
		node_id:  nodeId,
		remark: "",
		[paramsName.nodeType]: nodeSchema.id,
		next_nodes: [],
		meta: {
			position
		},
		position,
		output: nodeSchema.output,
		input: nodeSchema.input,
		system_output: nodeSchema.system_output,
		step: 0,
        node_version: getLatestNodeVersion(nodeSchema.id),
		...(extraConfig || {})
	}
	console.timeEnd("clone_node")
	return newNode as DelightfulFlow.Node
}

// Get the latest schema version for this node type, with a fallback
export const getLatestNodeVersion = (nodeType: BaseNodeType) => {
    const { nodeVersionSchema } = flowStore.getState()
    const latestNodeVersion = _.last(Object.keys(_.get(nodeVersionSchema, [nodeType], {})) || []) as string 
    return latestNodeVersion || DefaultNodeVersion
}

// Get the node version, defaulting to v0
export const getNodeVersion = (node: DelightfulFlow.Node) => {
    return node?.node_version ? node.node_version : DefaultNodeVersion
}


/** Check whether the start node is registered */
export const isRegisteredStartNode = () => {
	
	const startNodeSchema = getNodeSchema(nodeManager.startNodeType)
	return !!startNodeSchema
}

/** Generate a start node */
export const generateStartNode = (paramsName: DelightfulFlow.ParamsName) => {
	const startNodeSchema = getNodeSchema(nodeManager.startNodeType)
	
	const startNodeId = generateSnowFlake()
	const startNodePosition = {
		x: 100,
		y: 200,
	}
	const newNode = generateNewNode(
		startNodeSchema,
		paramsName,
		startNodeId,
		startNodePosition,
	)

	return newNode
}


/** Create a duplicated node from an existing one */
export const generatePasteNode = (node: DelightfulFlow.Node, paramsName: DelightfulFlow.ParamsName) => {
	const newId = generateSnowFlake()
	
	const pasteNode = {
		...node,
		data: {...node.data},
		id: newId,
		node_id: newId,
		[paramsName?.nextNodes!]: node?.[paramsName?.nextNodes!] || [],
		position: { x: node.position!.x + pasteTranslateSize, y: node.position!.y + pasteTranslateSize }
	} as DelightfulFlow.Node

    
	return {
		pasteNode,
	}
}

/**
 * Generate duplicated edges
 * @param rawNode mapping of original id to new id
 * @param oldRelationEdges edges related to the original nodes
 */
export const generatePasteEdges = (oldId2NewIdMap: Record<string,string>, oldRelationEdges: Edge[]) => {
	console.time("clone_edges")
	const cloneEdges = _.cloneDeep(oldRelationEdges)
	const newEdges = cloneEdges.reduce((acc, curEdge) => {
		curEdge.id = generateSnowFlake()
		if(Reflect.has(oldId2NewIdMap, curEdge.source)) {
			curEdge.source = Reflect.get(oldId2NewIdMap, curEdge.source)
		}
		if(Reflect.has(oldId2NewIdMap, curEdge.target)) {
			curEdge.target = Reflect.get(oldId2NewIdMap, curEdge.target)
		}
		return [...acc, curEdge ]
	}, [] as Edge[])
	console.timeEnd("clone_edges")
	return newEdges
}



/**
 * Generate a loop body and its related edges
 * @param loopNode loop node
 * @param paramsName customized parameter names
 * @param edges current edges
 * @param addPosition where the loop is inserted (canvas/edge/node)
 * @returns new nodes and edges for the loop body
 */
export const generateLoopBody = (loopNode: DelightfulFlow.Node, paramsName:DelightfulFlow.ParamsName, edges: Edge[]) => {
	const id = generateSnowFlake()
	const loopNodeSchema = getNodeSchema(loopNode[paramsName.nodeType])
	const loopNodeWidth = parseInt(loopNodeSchema?.style?.width as string, 10)
	const loopStartSchema = getNodeSchema(nodeManager.loopStartType)
	const loopStartWidth = parseInt(loopStartSchema?.style?.width as string, 10)
	const loopStartConfig = nodeManager.loopStartConfig

	const newNodes = [] as DelightfulFlow.Node[]
	const newEdges = [] as Edge[]
	// Default nodes within the loop body
	const defaultLoopBodyNodes = [] as DelightfulFlow.Node[]

	const nodePosition = {
		x: (loopNode?.position?.x || 0) + loopNodeWidth + Ranksep,
		y: loopNode?.position?.y || 0
	}

	const loopBodyType = nodeManager.loopBodyType

	if(!loopBodyType) {
		console.error("Loop body node type is not registered")
	}

	const loopBodyNode = {
		[paramsName.params]: null,
		id: id,
		node_id:  id,
		remark: "",
		name: i18next.t("flow.loopBody", { ns: "delightfulFlow" }),
		[paramsName.nodeType]: loopBodyType,
		next_nodes: [],
		type: NodeModelType.Group,
		meta: {
			position: nodePosition,
			// For backend persistence and lookup
			parent_id: loopNode.id
		},
		data: {
			description: i18next.t("flow.loopBodyDesc", { ns: "delightfulFlow" }),
		},
		position: nodePosition,
		deletable: false,
		output: null,
		input: null,
        node_version: getLatestNodeVersion(loopBodyType),
		step: 0,
		style: {
			width: loopStartWidth + GROUP_MIN_DISTANCE * 2,
			height: loopStartConfig.height +  GROUP_TOP_GAP + GROUP_MIN_DISTANCE * 2
		},
	}

	/** Generate the loop start node */
	const loopStartNodeSchema = getNodeSchema(nodeManager.loopStartType)
	const loopStartNodeId = generateSnowFlake()
	const loopStartNode = generateNewNode(loopStartNodeSchema, paramsName, loopStartNodeId, {
		x: GROUP_MIN_DISTANCE,
		y: GROUP_MIN_DISTANCE + GROUP_TOP_GAP
	})
	defaultLoopBodyNodes.push(loopStartNode)

	// Link loop node and loop body for backend lookup
	_.set(loopNode, ['meta', 'relation_id'], loopBodyNode.id)
	
	// Add the loop body first so expandParent takes effect
	newNodes.push(loopBodyNode)

	// Generate nodes inside the loop body
	defaultLoopBodyNodes.forEach((n, i) => {
		n.parentId = loopBodyNode.id
		n.expandParent = true
		n.extent = 'parent'
		n.meta.parent_id = loopBodyNode.id
		handleRenderProps(n, i, paramsName)
		newNodes.push(n)
	})

	const hasEdge = edges.find(edge => edge.source === loopNode.id && edge.target === loopBodyNode.id)
	if(!hasEdge) {
		newEdges.push({
			id: generateSnowFlake(),
			source: loopNode.id,
			target: loopBodyNode.id,
			sourceHandle: InnerHandleType.LoopHandle,
			...defaultEdgeConfig,
			deletable: false,
			data: {
				allowAddOnLine: false,
			}
		})
	}

	return {
		newEdges,
		newNodes
	}
}

// export const sortNodes = (a: DelightfulFlow.Node, b: DelightfulFlow.Node): number => {
// 	if (a.parentId && b.parentId) {
// 		return 0;
// 	}

// 	return a.parentId && !b.parentId ? 1 : -1;
// };

/** Get node types that are currently registered */
export const getRegisterNodeTypes = () => {
    const { nodeVersionSchema } = flowStore.getState();
	return Object.values(nodeVersionSchema).filter(nodeVersionWidget => {
        const addableVersionMap =  Object.entries(nodeVersionWidget).reduce((addableNodeVersionWidget, [nodeVersion, nodeWidget]) => {
            // console.log(nodeWidget.schema)
            if(nodeWidget.schema.addable === false) {
                return addableNodeVersionWidget
            }
            addableNodeVersionWidget[nodeVersion] = nodeWidget
            return addableNodeVersionWidget
        }, {} as NodeVersionWidget)
        return Object.keys(addableVersionMap).length > 0
	}).map(nodeVersionWidget => {
        const latestWidget = _.last(Object.values(nodeVersionWidget))
        return latestWidget?.schema?.id
    }).filter(nodeType => !!nodeType) as BaseNodeType[]
}


/** Derive extra edge config from the source node */
export const getExtraEdgeConfigBySourceNode = (sourceNode: DelightfulFlow.Node) => {
	return sourceNode?.parentId ? {zIndex: 1001} : {}
}


/** Apply loop body meta to nodes when rehydrating */
export const addLoopProperties = (node: DelightfulFlow.Node, paramsName: DelightfulFlow.ParamsName) => {
	// Node is inside a loop body but not the loop body itself
	const isLoopBody = judgeIsLoopBody(node[paramsName.nodeType])
	if(!isLoopBody && node?.meta?.parent_id) {
		Object.assign(node, {
			parentId : node.meta.parent_id,
			expandParent : true,
			extent : 'parent'
		})
	}
}


export const searchLoopRelationNodesAndEdges = (loopNode: DelightfulFlow.Node, nodes: DelightfulFlow.Node[], edges: Edge[]) => {

	// Loop body node id
	const loopBodyNodeId = nodes.find(_n => _n?.meta?.parent_id === loopNode.id)?.id
	// Edge from loop node to loop body
	const loopNode2LoopBodyEdgeId = edges.find(_e => _e.source === loopNode.id && _e.target === loopBodyNodeId)?.id

	// Node ids inside the loop body
	const childNodeIds = nodes.filter(_n => _n?.meta?.parent_id === loopBodyNodeId).map(_n => _n.id)
	// Edge ids inside the loop body
	const childEdgeIds = edges.filter(_e => childNodeIds.includes(_e.source) || childNodeIds.includes(_e.target)).map(_e => _e.id)


	return {
		nodeIds: [...childNodeIds, loopBodyNodeId!],
		edgeIds: [...childEdgeIds, loopNode2LoopBodyEdgeId!]
	}
}

/** 
 * Convert hex color to RGBA
 * @params hex hex string
 * @params alpha opacity, e.g., 0.05 means 5%
 * Example:
	const hexColor = "#FF7D00";
	const alpha = 0.05; // 5% opacity
	const rgbaColor = hexToRgba(hexColor, alpha);
	console.log(rgbaColor);  // Outputs: #FF7D000D
 */
export function hexToRgba(hex: string, alpha = 1) {
	if(!hex) return ""
	// Remove '#' symbol
    hex = hex.replace('#', '');

	// Split into RGB parts
    let r = parseInt(hex.substring(0, 2), 16);
    let g = parseInt(hex.substring(2, 4), 16);
    let b = parseInt(hex.substring(4, 6), 16);

	// Clamp alpha between 0 and 1 and convert to 0-255
    let a = Math.round(alpha * 255);

	// Return RGBA value with two-digit hex alpha
    return `#${hex}${a.toString(16).padStart(2, '0')}`;
}

// Check whether this is a loop start node
export const checkIsLoopStart = (node: DelightfulFlow.Node,paramsName: DelightfulFlow.ParamsName) => {
	return nodeManager.loopStartType == node?.[paramsName.nodeType] && node?.meta?.parent_id
}

/** Check whether a node is inside a loop body */
export const checkIsInGroup = (node: DelightfulFlow.Node) => {
	return node?.meta?.parent_id
}
