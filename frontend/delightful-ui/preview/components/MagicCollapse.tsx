import React from "react"
import { Space } from "antd"
import DelightfulCollapse from "../../components/DelightfulCollapse"
import ComponentDemo from "./Container"

const CollapseDemo: React.FC = () => {
	const items = [
		{
			key: "1",
			label: "面板1",
			children: "这是面板1的内容，可以包含任何React组件。",
		},
		{
			key: "2",
			label: "面板2",
			children: "这是面板2的内容，支持HTML标签和复杂组件。",
		},
		{
			key: "3",
			label: "面板3",
			children: (
				<div>
					<p>这是面板3的内容，支持复杂的JSX结构。</p>
					<ul>
						<li>列表项1</li>
						<li>列表项2</li>
						<li>列表项3</li>
					</ul>
				</div>
			),
		},
	]

	return (
		<div>
			<ComponentDemo
				title="基础折叠面板"
				description="最基本的折叠面板组件"
				code="<DelightfulCollapse items={items} />"
			>
				<DelightfulCollapse items={items} />
			</ComponentDemo>

			<ComponentDemo title="手风琴模式" description="同时只能展开一个面板" code="accordion">
				<DelightfulCollapse items={items} accordion />
			</ComponentDemo>

			<ComponentDemo
				title="默认展开"
				description="设置默认展开的面板"
				code="defaultActiveKey"
			>
				<DelightfulCollapse items={items} defaultActiveKey={["1", "2"]} />
			</ComponentDemo>

			<ComponentDemo
				title="受控展开"
				description="通过activeKey控制展开状态"
				code="activeKey | onChange"
			>
				<DelightfulCollapse
					items={items}
					activeKey={["1"]}
					onChange={(keys) => console.log("展开的面板:", keys)}
				/>
			</ComponentDemo>

			<ComponentDemo
				title="不同尺寸"
				description="支持不同尺寸的折叠面板"
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
