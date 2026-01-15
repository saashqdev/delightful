import type { WidgetValue } from "@bedelightful/delightful-flow/dist/DelightfulFlow/examples/BaseFlow/common/Output"

export enum BranchType {
	If = "if",
	Else = "else",
}

export type Branch = {
	branch_id: string
	next_nodes: string[]
	config?: Record<string, any>
	input?: WidgetValue["value"]
	output?: WidgetValue["value"]
	branch_type?: BranchType
}

// Used to handle legacy data where branches may not have branch_type, add default values here
export default function addBranchTypeIfWithout(branches: Branch[]) {
	return branches?.map((branch, branchIndex) => {
		if (!branch?.branch_type) {
			branch.branch_type =
				branchIndex === branches.length - 1 ? BranchType.Else : BranchType.If
		}
		return branch
	})
}
