import React from "react"
import DelightfulMenu from "../../components/DelightfulMenu"
import ComponentDemo from "./Container"

const MenuDemo: React.FC = () => {
	const horizontalItems = [
		{ key: "1", label: "Menu Item 1" },
		{ key: "2", label: "Menu Item 2" },
		{ key: "3", label: "Menu Item 3" },
	]

	const verticalItems = [
		{ key: "1", label: "Menu Item 1" },
		{ key: "2", label: "Menu Item 2" },
		{ key: "3", label: "Menu Item 3" },
	]

	const nestedItems = [
		{ key: "1", label: "Menu Item 1" },
		{
			key: "2",
			label: "Submenu",
			children: [
				{ key: "2-1", label: "Submenu Item 1" },
				{ key: "2-2", label: "Submenu Item 2" },
			],
		},
		{ key: "3", label: "Menu Item 3" },
	]

	return (
		<div>
			<ComponentDemo title="Horizontal Menu" description="Horizontal direction menu" code="mode='horizontal'">
				<DelightfulMenu
					mode="horizontal"
					items={horizontalItems}
					style={{ borderBottom: "1px solid #f0f0f0" }}
				/>
			</ComponentDemo>

			<ComponentDemo title="Vertical Menu" description="Vertical direction menu" code="mode='vertical'">
				<div style={{ width: 200, border: "1px solid #f0f0f0" }}>
					<DelightfulMenu mode="vertical" items={verticalItems} />
				</div>
			</ComponentDemo>

			<ComponentDemo title="Nested Menu" description="Supports nested submenus" code="children">
				<div style={{ width: 200, border: "1px solid #f0f0f0" }}>
					<DelightfulMenu mode="vertical" items={nestedItems} />
				</div>
			</ComponentDemo>

			<ComponentDemo title="Event Handling" description="Listen to menu click events" code="onClick">
				<DelightfulMenu
					mode="horizontal"
					items={horizontalItems}
					onClick={({ key }) => {
						console.log("Clicked menu item:", key)
					}}
					style={{ borderBottom: "1px solid #f0f0f0" }}
				/>
			</ComponentDemo>

			<ComponentDemo
				title="Default Selected"
				description="Set default selected menu item"
				code="defaultSelectedKeys"
			>
				<DelightfulMenu
					mode="horizontal"
					items={horizontalItems}
					defaultSelectedKeys={["1"]}
					style={{ borderBottom: "1px solid #f0f0f0" }}
				/>
			</ComponentDemo>
		</div>
	)
}

export default MenuDemo
