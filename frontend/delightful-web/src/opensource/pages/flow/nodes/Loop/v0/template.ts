export const v0Template = {
	node_id: "DELIGHTFUL-FLOW-NODE-66a70a8da8ed48-26322039",
	name: "Loop",
	description: "",
	node_type: 22,
	meta: [],
	params: {
		type: "array", // Count type: count, Array loop: array, Conditional loop: condition,
		condition: {
			// Enabled for conditional loops, used to provide a loop termination condition
			id: "component-66da72180d2e1",
			version: "1",
			type: "condition",
			structure: undefined,
		},
		count: {
			// Enabled for count loops
			id: "component-66da73d27fea0",
			version: "1",
			type: "value",
			structure: null,
		},
		array: {
			// Enabled for traversing loop arrays
			id: "component-66da73d27fea0",
			version: "1",
			type: "value",
			structure: null,
		},
		max_loop_count: {
			// Maximum traversal count limit, enabled for conditional loops, can be empty
			id: "component-66da73d27fea0",
			version: "1",
			type: "value",
			structure: null,
		},
	},
	next_nodes: [],
	input: null,
	output: null,
}
