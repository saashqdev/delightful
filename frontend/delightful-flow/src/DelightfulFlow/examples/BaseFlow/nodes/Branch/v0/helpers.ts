import { Expression } from "@/DelightfulConditionEdit/types/expression"
import { WidgetValue } from "../../../common/Output"


export enum BranchType {
	If = "if",
	Else = "else",
}

// Single branch of If node
export interface IfBranch {
	branch_id: string
	next_nodes: string[]
	parameters: {
		id: string
		version: string
		type: string
		structure: Expression.Condition | undefined
	}
}

export type Branch = {
	branch_id: string
	next_nodes: string[]
	config?: Record<string, any>
	input?: WidgetValue["value"]
	output?: WidgetValue["value"]
	branch_type?: BranchType
}

// Used to handle old data - previous branches may not have branch_type, add default value here
export default function addBranchTypeIfWithout(branches: Branch[]) {
	return branches?.map((branch, branchIndex) => {
		if (!branch?.branch_type) {
			branch.branch_type =
				branchIndex === branches.length - 1 ? BranchType.Else : BranchType.If
		}
		return branch
	})
}

