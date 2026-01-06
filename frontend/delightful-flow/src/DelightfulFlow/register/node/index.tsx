import { TabObject } from "@/DelightfulFlow/components/FlowMaterialPanel/constants"
import { WidgetValue } from "@/DelightfulFlow/examples/BaseFlow/common/Output"
import { CSSProperties, ComponentType, ReactNode } from "react"

export type BaseNodeType = string | number

export type LoopStartConfig = { height: number; [key: string]: any }

export type NodeSchema = {
	// Node name
	label: string
	// Node icon
	icon: ReactNode | null
	// Node icon background color
	color: string
	// Node type
	id: BaseNodeType
	// Node description
	desc: string
	// Node group name
	groupName?: string
	// Node custom styles
	style?: CSSProperties
	// Endpoint handlers for the node
	handle?: {
		// Includes target handles (incoming links)
		withSourceHandle: boolean
		// Includes source handles
		withTargetHandle: boolean
	}
	// Whether the current node can change its Node type
	changeable?: {
		// Whether delete/copy is allowed
		operation: boolean
		// Whether the Node type can be switched
		nodeType: boolean
	}
	// Default config
	params?: Record<string, any>
	output?: WidgetValue["value"] | null
	input?: WidgetValue["value"] | null
	// Custom component on node header right
	headerRight?: React.ReactElement
	// Whether addable by default
	addable?: boolean
	// Avatar path (set icon to null and pass avatarPath to use an avatar instead of an icon)
	avatarPath?: string[]
	// Avatar accessor function
	[key: string]: any
}

export type NodeVersion = string

export type NodeWidget = {
	// DSL used to register node
	schema: NodeSchema
	// Node custom params component
	component: ComponentType<any>
}

export type NodeVersionWidget = Record<NodeVersion, NodeWidget>

interface RegisterNodeProps {
	nodeType: BaseNodeType
	nodeVersionWidget: NodeVersionWidget
}

export type NodeGroup = {
	groupName: string
	nodeTypes: BaseNodeType[]
	desc?: string
	children?: NodeGroup[]
	icon?: ReactNode
	color?: string
	avatar?: string
}

// NodeTypes should be enum values
class NodeManager {
	// Map of registered node types
	nodesMap = {} as Record<BaseNodeType, NodeVersionWidget>

	// Node types that have multiple outgoing handles
	branchNodeIds = [] as BaseNodeType[]

	// Node types that can act as reference nodes
	canReferenceNodeTypes = [] as BaseNodeType[]

	// Node types that should be hidden from the material panel
	notMaterialNodeTypes = [] as BaseNodeType[]

	// Mapping of prefixes required when referencing upstream nodes
	nodeType2ReferenceKey = {} as Record<string, BaseNodeType>

	// Variable node types; registering them changes reference lookup to group all variable data under "Variables"
	variableNodeTypes = [] as BaseNodeType[]

	// Grouping relationships for the current node
	nodeGroups = [] as NodeGroup[]

	// Start node type
	startNodeType = "startNodeType" as BaseNodeType

	// Loop node type
	loopNodeType = "loopNode" as BaseNodeType

	// Loop-body node type
	loopBodyType = "loopBody" as BaseNodeType

	// Loop-start node type
	loopStartType = "loopStart" as BaseNodeType

	// Special node types for the left material panel
	materialNodeTypeMap = {} as Partial<Record<TabObject, BaseNodeType>>

	// Node avatar storage path
	avatarPath = ["params", "avatar"]

	// Loop start node log
	loopStartConfig = {
		height: 0,
	} as LoopStartConfig

	constructor() {}

	registerNode({ nodeType, nodeVersionWidget }: RegisterNodeProps) {
		// if (Reflect.has(this.nodesMap, nodeType)) {
		// 	console.warn(`Type already exists ${nodeType}，cannot be registered again`)
		// 	return
		// }
		Reflect.set(this.nodesMap, nodeType, nodeVersionWidget)
	}

	registerBranchNodes(branchNodeTypes: BaseNodeType[]) {
		this.branchNodeIds = branchNodeTypes
	}

	registerCanReferenceNodeTypes(canReferenceNodeTypes: BaseNodeType[]) {
		this.canReferenceNodeTypes = canReferenceNodeTypes
	}

	registerNotMaterialNodeTypes(notMaterialNodeTypes: BaseNodeType[]) {
		this.notMaterialNodeTypes = notMaterialNodeTypes
	}

	registerNodeType2ReferenceKey(nodeType2ReferenceKey: Record<BaseNodeType, string>) {
		this.nodeType2ReferenceKey = nodeType2ReferenceKey
	}

	registerVariableNodeTypes(nodeTypes: BaseNodeType[]) {
		this.variableNodeTypes = nodeTypes
	}

	registerStartNodeType(nodeType: BaseNodeType) {
		this.startNodeType = nodeType
	}

	registerLoopNodeType(nodeType: BaseNodeType) {
		this.loopNodeType = nodeType
	}

	registerLoopBodyType(nodeType: BaseNodeType) {
		this.loopBodyType = nodeType
	}

	registerLoopStartType(nodeType: BaseNodeType) {
		this.loopStartType = nodeType
	}

	registerLoopStartConfig(config: LoopStartConfig) {
		this.loopStartConfig = config
	}

	registerNodeGroups(groups: NodeGroup[]) {
		this.nodeGroups = groups
	}

	registerMaterialNodeTypeMap(tab2NodeType: Partial<Record<TabObject, BaseNodeType>>) {
		this.materialNodeTypeMap = tab2NodeType
	}

	registerAvatarPath(avatarPath: string[]) {
		this.avatarPath = avatarPath
	}
}

export const nodeManager = new NodeManager()

/**
 * Expose node registration methods
 */
export const installNodes = (nodesMap: Record<BaseNodeType, NodeVersionWidget>) => {
	console.log(nodesMap)
	Object.entries(nodesMap).forEach(([nodeType, nodeVersionWidget]) => {
		nodeManager.registerNode({
			nodeType,
			nodeVersionWidget,
		})
	})
	return nodeManager
}

/**
 * Expose registration for branch nodes
 */
export const registerBranchNodes = (nodeTypes: BaseNodeType[]) => {
	nodeManager.registerBranchNodes(nodeTypes)
}

/**
 * Expose registration for reference nodes
 */
export const registerCanReferenceNodeTypes = (nodeTypes: BaseNodeType[]) => {
	nodeManager.registerCanReferenceNodeTypes(nodeTypes)
}

/**
 * Expose registration for nodes hidden from the palette
 */
export const registerNotMaterialNodeTypes = (nodeTypes: BaseNodeType[]) => {
	nodeManager.registerNotMaterialNodeTypes(nodeTypes)
}

/**
 * Expose registration for fixed prefixes when referencing upstream nodes
 */
export const registerNodeType2ReferenceKey = (nodeTypes: Record<BaseNodeType, string>) => {
	nodeManager.registerNodeType2ReferenceKey(nodeTypes)
}

/**
 * Expose registration for variable node types
 */
export const registerVariableNodeTypes = (nodeTypes: BaseNodeType[]) => {
	nodeManager.registerVariableNodeTypes(nodeTypes)
}

/**
 * Expose registration for trigger/start node type
 */
export const registerStartNodeType = (nodeType: BaseNodeType) => {
	nodeManager.registerStartNodeType(nodeType)
}

/**
 * Expose registration for loop node type
 */
export const registerLoopNodeType = (nodeType: BaseNodeType) => {
	nodeManager.registerLoopNodeType(nodeType)
}

/**
 * Expose registration for loop-body node type
 */
export const registerLoopBodyType = (nodeType: BaseNodeType) => {
	nodeManager.registerLoopBodyType(nodeType)
}

/**
 * Expose registration for loop body start types
 */
export const registerLoopStartType = (nodeType: BaseNodeType) => {
	nodeManager.registerLoopStartType(nodeType)
}

export const registerLoopStartConfig = (config: LoopStartConfig) => {
	nodeManager.registerLoopStartConfig(config)
}

/**
 * Expose registration for node group info
 */
export const registerNodeGroups = (nodeGroups: NodeGroup[]) => {
	nodeManager.registerNodeGroups(nodeGroups)
}

/**
 * Expose registration for special material-panel node types (left panel)
 */
export const registerMaterialNodeTypeMap = (
	tab2NodeType: Partial<Record<TabObject, BaseNodeType>>,
) => {
	nodeManager.registerMaterialNodeTypeMap(tab2NodeType)
}

/**
 * Expose registration for node avatar path
 */
export const registerAvatarPath = (avatarPath: string[]) => {
	nodeManager.registerAvatarPath(avatarPath)
}

