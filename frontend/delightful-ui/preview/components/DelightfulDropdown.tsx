import React from "react"
import { Space } from "antd"
import DelightfulDropdown from "../../components/DelightfulDropdown"
import DelightfulButton from "../../components/DelightfulButton"
import ComponentDemo from "./Container"

const DropdownDemo: React.FC = () => {
	const menuItems = [
		{ key: "1", label: "Menu Item 1" },
		{ key: "2", label: "Menu Item 2" },
		{ key: "3", label: "Menu Item 3" },
	]

	const nestedMenuItems = [
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
			<ComponentDemo
				title="Basic Dropdown"
				description="Most basic dropdown menu component"
				code="<DelightfulDropdown menu={{ items }}><Button>Dropdown</Button></DelightfulDropdown>"
			>
				<Space>
					<DelightfulDropdown
						menu={{
							items: menuItems,
						}}
					>
						<DelightfulButton>Dropdown Menu</DelightfulButton>
					</DelightfulDropdown>
				</Space>
			</ComponentDemo>

			<ComponentDemo title="Nested Menu" description="Supports nested submenus" code="children">
				<Space>
					<DelightfulDropdown
						menu={{
							items: nestedMenuItems,
						}}
					>
						<DelightfulButton>Nested Menu</DelightfulButton>
					</DelightfulDropdown>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Different Trigger Methods"
				description="Supports different trigger methods"
				code="trigger: 'click' | 'hover' | 'contextMenu'"
			>
				<Space>
					<DelightfulDropdown
						trigger={["click"]}
						menu={{
							items: menuItems,
						}}
					>
						<DelightfulButton>Click Trigger</DelightfulButton>
					</DelightfulDropdown>
					<DelightfulDropdown
						trigger={["hover"]}
						menu={{
							items: menuItems,
						}}
					>
						<DelightfulButton>Hover Trigger</DelightfulButton>
					</DelightfulDropdown>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Different Positions"
				description="Supports different display positions"
				code="placement: 'top' | 'bottom' | 'left' | 'right'"
			>
				<Space>
					<DelightfulDropdown
						placement="top"
						menu={{
							items: menuItems,
						}}
					>
						<DelightfulButton>Display at Top</DelightfulButton>
					</DelightfulDropdown>
					<DelightfulDropdown
						placement="bottom"
						menu={{
							items: menuItems,
						}}
					>
						<DelightfulButton>Display at Bottom</DelightfulButton>
					</DelightfulDropdown>
				</Space>
			</ComponentDemo>

			<ComponentDemo title="Event Handling" description="Listen to menu click events" code="onClick">
				<Space>
					<DelightfulDropdown
						menu={{
							items: menuItems,
							onClick: ({ key }) => {
								console.log("Clicked menu item:", key)
							},
						}}
					>
						<DelightfulButton>Event Handling</DelightfulButton>
					</DelightfulDropdown>
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default DropdownDemo
