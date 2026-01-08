export const v1Template = {
	node_id: "DELIGHTFUL-FLOW-NODE-67cfd534c86796-80270007",
	debug: false,
	name: "Image Generation",
	description: "",
	node_type: 53,
	node_version: "v1",
	meta: [],
	params: {
		height: {
			id: "component-67cfd534c86a0",
			version: "1",
			type: "value",
			structure: {
				type: "const",
				const_value: [
					{
						type: "input",
						value: "",
						name: "",
						args: null,
					},
				],
				expression_value: null,
			},
		},
		width: {
			id: "component-67cfd534c86cf",
			version: "1",
			type: "value",
			structure: {
				type: "const",
				const_value: [
					{
						type: "input",
						value: "",
						name: "",
						args: null,
					},
				],
				expression_value: null,
			},
		},
		user_prompt: {
			id: "component-67cfd534c86df",
			version: "1",
			type: "value",
			structure: {
				type: "const",
				const_value: [
					{
						type: "input",
						value: "",
						name: "",
						args: null,
					},
				],
				expression_value: null,
			},
		},
		negative_prompt: {
			id: "component-67cfd534c86eb",
			version: "1",
			type: "value",
			structure: {
				type: "const",
				const_value: [
					{
						type: "input",
						value: "",
						name: "",
						args: null,
					},
				],
				expression_value: null,
			},
		},
		ratio: {
			id: "component-67cfd534c86f4",
			version: "1",
			type: "value",
			structure: {
				type: "const",
				const_value: [
					{
						type: "input",
						value: "",
						name: "",
						args: null,
					},
				],
				expression_value: null,
			},
		},
		use_sr: {
			id: "component-67cfd534c86fd",
			version: "1",
			type: "value",
			structure: {
				type: "const",
				const_value: [
					{
						type: "input",
						value: "",
						name: "",
						args: null,
					},
				],
				expression_value: null,
			},
		},
		reference_images: {
			id: "component-67cfd534c8706",
			version: "1",
			type: "value",
			structure: {
				type: "expression",
				const_value: null,
				expression_value: [
					{
						type: "input",
						value: "",
						name: "",
						args: null,
					},
				],
			},
		},
		model_id: "",
	},
	next_nodes: [],
	input: null,
	output: {
		widget: null,
		form: {
			id: "component-67cfd534c8717",
			version: "1",
			type: "form",
			structure: {
				type: "object",
				key: "root",
				sort: 0,
				title: "root node",
				description: "",
				required: ["image_urls"],
				value: null,
				encryption: false,
				encryption_value: null,
				items: null,
				properties: {
					image_urls: {
						type: "array",
						key: "image_urls",
						sort: 0,
						title: "Image Data",
						description: "",
						required: null,
						value: null,
						encryption: false,
						encryption_value: null,
						items: {
							type: "string",
							key: "",
							sort: 0,
							title: "Image URL",
							description: "",
							required: null,
							value: null,
							encryption: false,
							encryption_value: null,
							items: null,
							properties: null,
						},
						properties: null,
					},
				},
			},
		},
	},
	system_output: null,
}
