import React from "react"
import { Space } from "antd"
import DelightfulDropdown from "../../components/DelightfulDropdown"
import DelightfulButton from "../../components/DelightfulButton"
import ComponentDemo from "./Container"

const DropdownDemo: React.FC = () => {
	const menuItems = [
		{ key: "1", label: "菜单项1" },
		{ key: "2", label: "菜单项2" },
		{ key: "3", label: "菜单项3" },
	]

	const nestedMenuItems = [
		{ key: "1", label: "菜单项1" },
		{
			key: "2",
			label: "子菜单",
			children: [
				{ key: "2-1", label: "子菜单项1" },
				{ key: "2-2", label: "子菜单项2" },
			],
		},
		{ key: "3", label: "菜单项3" },
	]

	return (
		<div>
			<ComponentDemo
				title="基础下拉菜单"
				description="最基本的下拉菜单组件"
				code="<DelightfulDropdown menu={{ items }}><Button>下拉菜单</Button></DelightfulDropdown>"
			>
				<Space>
					<DelightfulDropdown
						menu={{
							items: menuItems,
						}}
					>
						<DelightfulButton>下拉菜单</DelightfulButton>
					</DelightfulDropdown>
				</Space>
			</ComponentDemo>

			<ComponentDemo title="嵌套菜单" description="支持嵌套子菜单" code="children">
				<Space>
					<DelightfulDropdown
						menu={{
							items: nestedMenuItems,
						}}
					>
						<DelightfulButton>嵌套菜单</DelightfulButton>
					</DelightfulDropdown>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="不同触发方式"
				description="支持不同的触发方式"
				code="trigger: 'click' | 'hover' | 'contextMenu'"
			>
				<Space>
					<DelightfulDropdown
						trigger={["click"]}
						menu={{
							items: menuItems,
						}}
					>
						<DelightfulButton>点击触发</DelightfulButton>
					</DelightfulDropdown>
					<DelightfulDropdown
						trigger={["hover"]}
						menu={{
							items: menuItems,
						}}
					>
						<DelightfulButton>悬停触发</DelightfulButton>
					</DelightfulDropdown>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="不同位置"
				description="支持不同的显示位置"
				code="placement: 'top' | 'bottom' | 'left' | 'right'"
			>
				<Space>
					<DelightfulDropdown
						placement="top"
						menu={{
							items: menuItems,
						}}
					>
						<DelightfulButton>顶部显示</DelightfulButton>
					</DelightfulDropdown>
					<DelightfulDropdown
						placement="bottom"
						menu={{
							items: menuItems,
						}}
					>
						<DelightfulButton>底部显示</DelightfulButton>
					</DelightfulDropdown>
				</Space>
			</ComponentDemo>

			<ComponentDemo title="事件处理" description="监听菜单点击事件" code="onClick">
				<Space>
					<DelightfulDropdown
						menu={{
							items: menuItems,
							onClick: ({ key }) => {
								console.log("点击的菜单项:", key)
							},
						}}
					>
						<DelightfulButton>事件处理</DelightfulButton>
					</DelightfulDropdown>
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default DropdownDemo
