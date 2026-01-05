import CommonNode from "./BaseNode"
import IfElseNode from "./BranchNode"
import GroupNode from "./GroupNode"

export const NodeModelType = {
	// Standard node
	CommonNode: "common",

	// Condition node
	IfElseNode: "ifElse",

	// Group node
	Group: "Group"
}

export default {
	[NodeModelType.CommonNode]: CommonNode,
	[NodeModelType.IfElseNode]: IfElseNode,
	[NodeModelType.Group]: GroupNode
}


export const InnerHandleType = {
	// Loop endpoint
	LoopHandle : "LoopBodyHandle",
	// Endpoint for the next node after a loop node
	LoopNext : "LoopNextHandle"
}