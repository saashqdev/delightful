import React from "react"
import { Space } from "antd"
import DelightfulSelect from "../../components/DelightfulSelect"
import ComponentDemo from "./Container"

const SelectDemo: React.FC = () => {
	const options = [
		{ value: "option1", label: "Option 1" },
		{ value: "option2", label: "Option 2" },
		{ value: "option3", label: "Option 3" },
		{ value: "option4", label: "Option 4" },
		{ value: "option5", label: "Option 5" },
	]

	return (
		<div>
			<ComponentDemo
				title="Basic Select"
				description="Most basic select component"
				code="<DelightfulSelect placeholder='Please select' options={options} />"
			>
				<Space>
					<DelightfulSelect placeholder="Please select" style={{ width: 200 }} options={options} />
				</Space>
			</ComponentDemo>

			<ComponentDemo title="Multiple Select" description="Select that supports multiple selections" code="mode='multiple'">
				<Space>
					<DelightfulSelect
						mode="multiple"
						placeholder="Please select multiple options"
						style={{ width: 300 }}
						options={options}
					/>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Different Sizes"
				description="Supports different sized selects"
				code="size: 'large' | 'middle' | 'small'"
			>
				<Space direction="vertical">
					<DelightfulSelect
						size="large"
						placeholder="Large Size Select"
						style={{ width: 200 }}
						options={options}
					/>
					<DelightfulSelect
						placeholder="Default Size Select"
						style={{ width: 200 }}
						options={options}
					/>
					<DelightfulSelect
						size="small"
						placeholder="Small Size Select"
						style={{ width: 200 }}
						options={options}
					/>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Searchable Select"
				description="Select with search functionality"
				code="showSearch"
			>
				<Space>
					<DelightfulSelect
						showSearch
						placeholder="Searchable Select"
						style={{ width: 200 }}
						options={options}
						filterOption={(input, option) =>
							!!option?.value?.toString().includes(input)
						}
					/>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Event Handling"
				description="Listen to selection change events"
				code="onChange | onSelect | onDeselect"
			>
				<Space direction="vertical">
					<DelightfulSelect
						placeholder="Listen to selection change"
						style={{ width: 200 }}
						options={options}
						onChange={(value) => console.log("Selected value:", value)}
					/>
					<DelightfulSelect
						mode="multiple"
						placeholder="Listen to multiple selection change"
						style={{ width: 300 }}
						options={options}
						onChange={(value) => console.log("Multiple selected values:", value)}
					/>
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default SelectDemo
