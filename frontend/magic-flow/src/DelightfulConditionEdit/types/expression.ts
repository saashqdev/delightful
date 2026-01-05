/* eslint-disable @typescript-eslint/naming-convention */
import { Common } from "./common"
import { EXPRESSION_ITEM_TYPE } from "@/MagicJsonSchemaEditor/types/Schema"
import { RELATION_COMP_TYPE, RELATION_LOGICS_MAP } from "../constants"
import { InputExpressionValue } from "@/MagicExpressionWidget/types"

export namespace Expression {
	// Concrete value type for a single expression item
	export interface EXPRESSION_ITEM {
		type: EXPRESSION_ITEM_TYPE
		value: string
		uniqueId: string
		name?: string
	}

	// Condition editor compare node (type=compare)
	export interface CompareNode {
		type: RELATION_COMP_TYPE
		left_operands: InputExpressionValue
		condition: string
		right_operands: InputExpressionValue
	}

	// Condition editor operation node (type=operation)
	export interface OperationNode {
		type: RELATION_COMP_TYPE
		operands: InputExpressionValue
	}

	// Condition editor top-level logic node type
	export interface LogicNode {
		ops: RELATION_LOGICS_MAP
		children: Condition[]
	}

	// Condition editor single node type
	export type Condition = LogicNode | CompareNode

	export interface ConditionSourceItem {
		key: string
		text: string // Display label
		value: string // Actual value
		have_children: boolean
		children?: ConditionSourceItem[]
	}

	export type ConditionSource = ConditionSourceItem[]
}
