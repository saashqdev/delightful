import i18next from "i18next"

export const v0Template = {
	node_id: "DELIGHTFUL-FLOW-NODE-671a09090ab463-93507489",
	debug: false,
	name: "History Message Query",
	description: "",
	node_type: 13,
	meta: [],
	params: {
		max_record: 10,
		start_time: "",
		end_time: "",
	},
	next_nodes: [],
	input: null,
	output: {
		widget: null,
		form: {
			id: "component-671a09090ab68",
			version: "1",
			type: "form",
			structure: {
				type: "object",
				key: "root",
				sort: 0,
				title: "root node",
				description: "",
				required: ["history_messages"],
				value: null,
				items: null,
				properties: {
					history_messages: {
						type: "array",
						key: "history_messages",
						sort: 0,
						title: "History Messages",
						description: "",
						required: null,
						value: null,
						items: {
							type: "object",
							key: "history_messages",
							sort: 0,
							title: "History Message",
							description: "",
							required: ["role", "content"],
							value: null,
							items: null,
							properties: {
								role: {
									type: "string",
									key: "role",
									sort: 0,
									title: "Role",
									description: "",
									required: null,
									value: null,
									items: null,
									properties: null,
								},
								content: {
									type: "string",
									key: "content",
									sort: 1,
									title: "Content",
									description: "",
									required: null,
									value: null,
									items: null,
									properties: null,
								},
							},
						},
						properties: null,
					},
				},
			},
		},
	},
	system_output: null,
}





