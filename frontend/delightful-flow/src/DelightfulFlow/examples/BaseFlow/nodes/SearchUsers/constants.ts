export enum FilterTargetTypes {
	Username = "username",
	WorkNumber = "work_number",
	Position = "position",
	Phone = "phone",
	DepartmentName = "department_name",
	GroupName = "group_name",
}

export const filterTargetOptions = [
	{
		label: "User Name",
		value: FilterTargetTypes.Username,
	},
	{
		label: "Work Number",
		value: FilterTargetTypes.WorkNumber,
	},
	{
		label: "Position",
		value: FilterTargetTypes.Position,
	},
	{
		label: "Phone Number",
		value: FilterTargetTypes.Phone,
	},
	{
		label: "Department Name",
		value: FilterTargetTypes.DepartmentName,
	},
	{
		label: "Group Name",
		value: FilterTargetTypes.GroupName,
	},
]

export const operatorMap = {
	[FilterTargetTypes.Username]: ["equals", "no_equals", "contains", "no_contains"],
	[FilterTargetTypes.WorkNumber]: [
		"equals",
		"no_equals",
		"contains",
		"no_contains",
		"empty",
		"not_empty",
	],
	[FilterTargetTypes.Position]: [
		"equals",
		"no_equals",
		"contains",
		"no_contains",
		"empty",
		"not_empty",
	],
	[FilterTargetTypes.Phone]: ["equals", "no_equals", "contains", "no_contains"],
	[FilterTargetTypes.DepartmentName]: ["equals", "no_equals", "contains", "no_contains"],
	[FilterTargetTypes.GroupName]: ["equals", "no_equals", "contains", "no_contains"],
}

// Value mapping
export const operator2Label: Record<string, string> = {
	equals: "Equals",
	no_equals: "Not Equal",
	contains: "Contains",
	no_contains: "Does Not Contain",
	empty: "Is Empty",
	not_empty: "Is Not Empty",
}

