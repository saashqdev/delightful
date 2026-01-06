import React, { useState } from "react"
import { Space } from "antd"
import DelightfulSegmented from "../../components/DelightfulSegmented"
import ComponentDemo from "./Container"

const SegmentedDemo: React.FC = () => {
	const [value1, setValue1] = useState<string>("Option 1")
	const [value2, setValue2] = useState<string>("Option 1")

	return (
		<div>
			<ComponentDemo
				title="Basic Segmented"
				description="Most basic segmented control component"
				code="<DelightfulSegmented options={['Option 1', 'Option 2', 'Option 3']} />"
			>
				<Space>
					<DelightfulSegmented options={["Option 1", "Option 2", "Option 3"]} />
					<DelightfulSegmented circle={false} options={["Option 1", "Option 2", "Option 3"]} />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Controlled Segmented"
				description="Control selected state through value and onChange"
				code="value | onChange"
			>
				<Space direction="vertical">
					<DelightfulSegmented
						options={["Option 1", "Option 2", "Option 3"]}
						value={value1}
						onChange={setValue1}
					/>
					<span>Currently selected: {value1}</span>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Different Sizes"
				description="Supports different sized segmented controls"
				code="size: 'large' | 'default' | 'small'"
			>
				<Space direction="vertical">
					<DelightfulSegmented options={["Option 1", "Option 2", "Option 3"]} size="large" />
					<DelightfulSegmented options={["Option 1", "Option 2", "Option 3"]} />
					<DelightfulSegmented options={["Option 1", "Option 2", "Option 3"]} size="small" />
				</Space>
			</ComponentDemo>

			<ComponentDemo title="Disabled State" description="Supports disabling certain options" code="disabled">
				<Space>
					<DelightfulSegmented
						options={[
							{ label: "Option 1", value: "Option 1" },
							{ label: "Option 2", value: "Option 2", disabled: true },
							{ label: "Option 3", value: "Option 3" },
						]}
					/>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Event Handling"
				description="Listen to selection change events"
				code="onChange: (value) => void"
			>
				<Space direction="vertical">
					<DelightfulSegmented
						options={["Option 1", "Option 2", "Option 3"]}
						value={value2}
						onChange={(value) => {
							setValue2(value)
							console.log("Selection changed:", value)
						}}
					/>
					<span>Currently selected: {value2}</span>
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default SegmentedDemo
