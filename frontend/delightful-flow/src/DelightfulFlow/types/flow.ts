import { Edge, Node as BaseNode } from "reactflow"
import { FlowDesignerEvents, FlowStatus } from "../constants"
import { BaseNodeType } from "../register/node"
import { WidgetValue } from "../examples/BaseFlow/common/Output"

/** Flow type */
export enum FlowType {
    /** Main flow */
    Main= 1,
    /** Subflow */
    Sub = 2
}

export namespace MagicFlow {

    // Node data structure
    export interface Node extends BaseNode {
        id: string
        node_id: string
        // node_type: BaseNodeType  // Renamed to a custom parameter; may differ from node_type
        meta: { 
			position: { x: number, y: number } 
			[key: string] : any
		}
        name?: string
        next_nodes: string[]
        step: number
        params?: {
            branches?: Array<{
                branch_id: string
                next_nodes: string[]
                config?: Record<string, any>
                input?: WidgetValue['value']
                output?: WidgetValue['value']
            }>
            [key:string]: any
        }
		content?: {
			[key: string]: any
		}
        // Whether the node is in debug mode
		debug?: boolean
        
        // Generated on the frontend
        index?: number
        output?: WidgetValue['value'] | null
        input?: WidgetValue['value'] | null
		children?: MagicFlow.Node[]
        // Node version
        node_version: string
		[key: string]: any
    }


    // Flow data structure
    export interface Flow {
        id?: string
        name: string
        description: string
        enabled: boolean
        edges: Edge[]
        nodes: Node[]
        modified_at?: string
        type: FlowType
		icon?: string
		[key: string]: any
    }


	export interface ParamsName {
		params: string
		nodeType: string
        nextNodes: string
	}


	export interface FlowEventListener {
		type: FlowDesignerEvents
		data: any
	}

}