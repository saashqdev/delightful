import React from "react"
import { Space } from "antd"
import DelightfulTag from "../../components/DelightfulTag"
import ComponentDemo from "./Container"

const TagDemo: React.FC = () => {
	return (
		<div>
			<ComponentDemo
				title="基础标签"
				description="最基本的标签组件"
				code="<DelightfulTag>默认标签</DelightfulTag>"
			>
				<Space wrap>
					<DelightfulTag>默认标签</DelightfulTag>
					<DelightfulTag color="blue">蓝色标签</DelightfulTag>
					<DelightfulTag color="green">绿色标签</DelightfulTag>
					<DelightfulTag color="red">红色标签</DelightfulTag>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="标签颜色"
				description="支持多种预设颜色"
				code="color: 'blue' | 'green' | 'red' | 'orange' | 'purple'"
			>
				<Space wrap>
					<DelightfulTag color="blue">蓝色</DelightfulTag>
					<DelightfulTag color="green">绿色</DelightfulTag>
					<DelightfulTag color="red">红色</DelightfulTag>
					<DelightfulTag color="orange">橙色</DelightfulTag>
					<DelightfulTag color="purple">紫色</DelightfulTag>
				</Space>
			</ComponentDemo>

			<ComponentDemo title="自定义样式" description="通过style自定义样式" code="style">
				<Space wrap>
					<DelightfulTag style={{ backgroundColor: "#f0f0f0", color: "#333" }}>
						自定义样式
					</DelightfulTag>
					<DelightfulTag
						style={{
							background: "linear-gradient(45deg, #ff6b6b, #4ecdc4)",
							color: "white",
						}}
					>
						渐变背景
					</DelightfulTag>
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default TagDemo
