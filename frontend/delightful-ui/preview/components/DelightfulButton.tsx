import React from "react"
import { Space } from "antd"
import DelightfulButton from "../../components/DelightfulButton"
import ComponentDemo from "./Container"

const ButtonDemo: React.FC = () => {
	return (
		<div>
			<ComponentDemo
			title="Button Types"
			description="Supports multiple button types: default, primary, dashed, link, text"
			code="type: 'default' | 'primary' | 'dashed' | 'link' | 'text'"
		>
			<Space wrap>
				<DelightfulButton>Default Button</DelightfulButton>
				<DelightfulButton type="primary">Primary Button</DelightfulButton>
				<DelightfulButton type="dashed">Dashed Button</DelightfulButton>
				<DelightfulButton type="link">Link Button</DelightfulButton>
				<DelightfulButton type="text">Text Button</DelightfulButton>
			</ComponentDemo>

			<ComponentDemo
			title="Button Sizes"
			description="Supports three sizes: large, default, small"
			code="size: 'large' | 'default' | 'small'"
		>
			<Space wrap>
				<DelightfulButton size="large">Large Button</DelightfulButton>
				<DelightfulButton>Default Button</DelightfulButton>
				<DelightfulButton size="small">Small Button</DelightfulButton>
			</ComponentDemo>

			<ComponentDemo
			title="Button States"
			description="Supports loading, disabled states"
			code="loading | disabled"
		>
			<Space wrap>
				<DelightfulButton loading>Loading</DelightfulButton>
				<DelightfulButton disabled>Disabled Button</DelightfulButton>
				<DelightfulButton type="primary" loading>
					Primary Loading
				</Space>
			</ComponentDemo>

			<ComponentDemo
			title="Button Alignment"
			description="Supports custom content alignment"
			code="justify: 'flex-start' | 'center' | 'flex-end'"
		>
			<Space direction="vertical" style={{ width: "100%" }}>
				<DelightfulButton justify="flex-start" style={{ width: 200 }}>
					Left Aligned Button
				</DelightfulButton>
				<DelightfulButton justify="center" style={{ width: 200 }}>
					Center Aligned Button
				</DelightfulButton>
				<DelightfulButton justify="flex-end" style={{ width: 200 }}>
					Right Aligned Button
				</Space>
			</ComponentDemo>

			<ComponentDemo
			title="Button with Icon"
			description="Supports adding icons to buttons"
			code="icon: ReactNode"
		>
			<Space wrap>
				<DelightfulButton icon="🔍">Search</DelightfulButton>
				<DelightfulButton type="primary" icon="➕">
					Add
				</DelightfulButton>
				<DelightfulButton type="dashed" icon="📁">
					Folder
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Button with Tooltip"
				description="Supports adding tooltips"
				code="tip: ReactNode"
			>
				<Space wrap>
					<DelightfulButton tip="This is a tooltip">Hover to View Tooltip</DelightfulButton>
					<DelightfulButton type="primary" tip="Primary button tooltip">
						Primary Button
					</DelightfulButton>
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default ButtonDemo
