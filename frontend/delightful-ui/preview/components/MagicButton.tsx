import React from "react"
import { Space } from "antd"
import DelightfulButton from "../../components/DelightfulButton"
import ComponentDemo from "./Container"

const ButtonDemo: React.FC = () => {
	return (
		<div>
			<ComponentDemo
				title="按钮类型"
				description="支持多种按钮类型：默认、主要、虚线、链接、文本"
				code="type: 'default' | 'primary' | 'dashed' | 'link' | 'text'"
			>
				<Space wrap>
					<DelightfulButton>默认按钮</DelightfulButton>
					<DelightfulButton type="primary">主要按钮</DelightfulButton>
					<DelightfulButton type="dashed">虚线按钮</DelightfulButton>
					<DelightfulButton type="link">链接按钮</DelightfulButton>
					<DelightfulButton type="text">文本按钮</DelightfulButton>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="按钮尺寸"
				description="支持三种尺寸：大、默认、小"
				code="size: 'large' | 'default' | 'small'"
			>
				<Space wrap>
					<DelightfulButton size="large">大按钮</DelightfulButton>
					<DelightfulButton>默认按钮</DelightfulButton>
					<DelightfulButton size="small">小按钮</DelightfulButton>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="按钮状态"
				description="支持加载、禁用等状态"
				code="loading | disabled"
			>
				<Space wrap>
					<DelightfulButton loading>加载中</DelightfulButton>
					<DelightfulButton disabled>禁用按钮</DelightfulButton>
					<DelightfulButton type="primary" loading>
						主要加载
					</DelightfulButton>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="按钮对齐"
				description="支持自定义内容对齐方式"
				code="justify: 'flex-start' | 'center' | 'flex-end'"
			>
				<Space direction="vertical" style={{ width: "100%" }}>
					<DelightfulButton justify="flex-start" style={{ width: 200 }}>
						左对齐按钮
					</DelightfulButton>
					<DelightfulButton justify="center" style={{ width: 200 }}>
						居中对齐按钮
					</DelightfulButton>
					<DelightfulButton justify="flex-end" style={{ width: 200 }}>
						右对齐按钮
					</DelightfulButton>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="带图标的按钮"
				description="支持在按钮中添加图标"
				code="icon: ReactNode"
			>
				<Space wrap>
					<DelightfulButton icon="🔍">搜索</DelightfulButton>
					<DelightfulButton type="primary" icon="➕">
						添加
					</DelightfulButton>
					<DelightfulButton type="dashed" icon="📁">
						文件夹
					</DelightfulButton>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="带提示的按钮"
				description="支持添加工具提示"
				code="tip: ReactNode"
			>
				<Space wrap>
					<DelightfulButton tip="这是一个提示">悬停查看提示</DelightfulButton>
					<DelightfulButton type="primary" tip="主要按钮提示">
						主要按钮
					</DelightfulButton>
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default ButtonDemo
