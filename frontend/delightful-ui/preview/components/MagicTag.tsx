import React from "react"
import { Space } from "antd"
import DelightfulTag from "../../components/DelightfulTag"
import ComponentDemo from "./Container"

const TagDemo: React.FC = () => {
	return (
		<div>
			<ComponentDemo
				title="Basic Tag"
				description="Most basic tag component"
				code="<DelightfulTag>Default Tag</DelightfulTag>"
			>
				<Space wrap>
					<DelightfulTag>Default Tag</DelightfulTag>
					<DelightfulTag color="blue">Blue Tag</DelightfulTag>
					<DelightfulTag color="green">Green Tag</DelightfulTag>
					<DelightfulTag color="red">Red Tag</DelightfulTag>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Tag Colors"
				description="Supports multiple preset colors"
				code="color: 'blue' | 'green' | 'red' | 'orange' | 'purple'"
			>
				<Space wrap>
					<DelightfulTag color="blue">Blue</DelightfulTag>
					<DelightfulTag color="green">Green</DelightfulTag>
					<DelightfulTag color="red">Red</DelightfulTag>
					<DelightfulTag color="orange">Orange</DelightfulTag>
					<DelightfulTag color="purple">Purple</DelightfulTag>
				</Space>
			</ComponentDemo>

			<ComponentDemo title="Custom Style" description="Customize style through style prop" code="style">
				<Space wrap>
					<DelightfulTag style={{ backgroundColor: "#f0f0f0", color: "#333" }}>
						Custom Style
					</DelightfulTag>
					<DelightfulTag
						style={{
							background: "linear-gradient(45deg, #ff6b6b, #4ecdc4)",
							color: "white",
						}}
					>
						Gradient Background
					</DelightfulTag>
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default TagDemo
