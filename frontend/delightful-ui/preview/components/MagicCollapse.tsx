import React from "react"
import { Space } from "antd"
import DelightfulCollapse from "../../components/DelightfulCollapse"
import ComponentDemo from "./Container"

const CollapseDemo: React.FC = () => {
	const items = [
		{
			key: "1",
			label: "Panel 1",
			children: "This is the content of panel 1, can contain any React component.",
		},
		{
			key: "2",
			label: "Panel 2",
			children: "This is the content of panel 2, supports HTML tags and complex components.",
		},
		{
			key: "3",
			label: "Panel 3",
			children: (
				<div>
					<p>This is the content of panel 3, supports complex JSX structures.</p>
					<ul>
						<li>List item 1</li>
						<li>List item 2</li>
						<li>List item 3</li>
					</ul>
				</div>
			),
		},
	]

	return (
		<div>
			<ComponentDemo
				title="Basic Collapse Panel"
				description="Most basic collapse panel component"
				code="<DelightfulCollapse items={items} />"
			>
				<DelightfulCollapse items={items} />
			</ComponentDemo>

			<ComponentDemo title="Accordion Mode" description="Only one panel can be expanded at a time" code="accordion">
				<DelightfulCollapse items={items} accordion />
			</ComponentDemo>

			<ComponentDemo
				title="Default Expanded"
				description="Set default expanded panels"
				code="defaultActiveKey"
			>
				<DelightfulCollapse items={items} defaultActiveKey={["1", "2"]} />
			</ComponentDemo>

			<ComponentDemo
				title="Controlled Expansion"
				description="Control expansion state through activeKey"
				code="activeKey | onChange"
			>
				<DelightfulCollapse
					items={items}
					activeKey={["1"]}
					onChange={(keys) => console.log("Expanded panels:", keys)}
				/>
			</ComponentDemo>

			<ComponentDemo
				title="Different Sizes"
				description="Supports different sizes of collapse panels"
				code="size: 'large' | 'default' | 'small'"
			>
				<Space direction="vertical" style={{ width: "100%" }}>
					<DelightfulCollapse items={items} size="large" />
					<DelightfulCollapse items={items} />
					<DelightfulCollapse items={items} size="small" />
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default CollapseDemo
