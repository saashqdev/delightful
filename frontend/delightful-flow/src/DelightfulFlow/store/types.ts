import { BaseNodeType, NodeVersionWidget } from "../register/node"

export type GlobalFlowStore = {
	// Material types allowed to be added to the canvas
	displayMaterialTypes: BaseNodeType[]
	updateDisplayMaterialType: (nodeTypes: BaseNodeType[]) => void
    // Node version schema
    nodeVersionSchema: Record<string, NodeVersionWidget>
    updateNodeVersionSchema: (nodeVersionSchema: Record<string, NodeVersionWidget>) => void
}
