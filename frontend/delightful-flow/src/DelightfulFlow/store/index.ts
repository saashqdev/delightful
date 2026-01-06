


import { createWithEqualityFn } from "zustand/traditional"
import { getInitialStates } from "./initialStates"
import { BaseNodeType, NodeVersionWidget } from "../register/node"
import { GlobalFlowStore } from "./types"


export type GlobalFlowStoreProps = {
	// Default list of node material types that can be added to the canvas
	defaultDisplayMaterialTypes: BaseNodeType[],
    // Node version schema
    nodeVersionSchema: Record<string, NodeVersionWidget>
}

const createStore = ({
	defaultDisplayMaterialTypes,
    nodeVersionSchema
}: GlobalFlowStoreProps) => createWithEqualityFn<GlobalFlowStore>((set, get) => ({
	...getInitialStates({defaultDisplayMaterialTypes, nodeVersionSchema}),
	
	updateDisplayMaterialType: (nodeTypes: BaseNodeType[]) => {
		set(state => ({ ...state, displayMaterialTypes: [...nodeTypes] }))
	},

    updateNodeVersionSchema: (nodeVersionSchema: Record<string, NodeVersionWidget>) => {
		set((state) => ({ ...state, nodeVersionSchema }))
    }
}), Object.is)


export { createStore }

export const flowStore = createStore({
    defaultDisplayMaterialTypes: [],
    nodeVersionSchema: {}
})

