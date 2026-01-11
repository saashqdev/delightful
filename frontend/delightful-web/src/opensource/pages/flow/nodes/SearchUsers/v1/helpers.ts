import { FilterTargetTypes, operator2Label, operatorMap } from "./constants"

// Get default filter options
export const getDefaultFilter = () => {
	return {
		left: FilterTargetTypes.Username,
		operator: operatorMap[FilterTargetTypes.Username][0],
		right: {
			id: "component-663c6d3ed0aa4",
			version: "1",
			type: "value",
			structure: {
				type: "const",
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
	}
}

export const getFilterOption = (left: FilterTargetTypes) => {
	return (
		operatorMap[left]?.map?.((operator) => {
			return {
				label: operator2Label?.[operator],
				value: operator,
			}
		}) || []
	)
}





