import React from "react"
import { Space } from "antd"
import DelightfulAvatar from "../../components/DelightfulAvatar"
import ComponentDemo from "./Container"

const AvatarDemo: React.FC = () => {
	return (
		<div>
			<ComponentDemo
				title="基础用法"
				description="支持文字头像和图片头像，文字头像会自动生成颜色"
				code="children: string | ReactNode"
			>
				<Space wrap>
					<DelightfulAvatar>张三</DelightfulAvatar>
					<DelightfulAvatar>李四</DelightfulAvatar>
					<DelightfulAvatar>王五</DelightfulAvatar>
					<DelightfulAvatar>John</DelightfulAvatar>
					<DelightfulAvatar>Alice</DelightfulAvatar>
					<DelightfulAvatar src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix" />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="不同尺寸"
				description="支持自定义尺寸，默认为 40px"
				code="size: 'small' | 'default' | 'large' | number"
			>
				<Space wrap align="center">
					<DelightfulAvatar size="small">小</DelightfulAvatar>
					<DelightfulAvatar>默认</DelightfulAvatar>
					<DelightfulAvatar size="large">大</DelightfulAvatar>
					<DelightfulAvatar size={96}>超大</DelightfulAvatar>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="带徽章的头像"
				description="支持在头像上显示徽章，用于显示状态或通知数量"
				code="badgeProps: BadgeProps"
			>
				<Space wrap>
					<DelightfulAvatar
						badgeProps={{
							count: 5,
						}}
					>
						张三
					</DelightfulAvatar>
					<DelightfulAvatar
						badgeProps={{
							dot: true,
						}}
					>
						李四
					</DelightfulAvatar>
					<DelightfulAvatar
						badgeProps={{
							status: "success",
						}}
					>
						王五
					</DelightfulAvatar>
					<DelightfulAvatar
						badgeProps={{
							status: "error",
						}}
					>
						赵六
					</DelightfulAvatar>
					<DelightfulAvatar
						badgeProps={{
							status: "processing",
						}}
					>
						钱七
					</DelightfulAvatar>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="自定义样式"
				description="支持自定义背景色、文字颜色等样式"
				code="style: CSSProperties"
			>
				<Space wrap>
					<DelightfulAvatar style={{ backgroundColor: "#f50", color: "#fff" }}>
						红色
					</DelightfulAvatar>
					<DelightfulAvatar style={{ backgroundColor: "#52c41a", color: "#fff" }}>
						绿色
					</DelightfulAvatar>
					<DelightfulAvatar style={{ backgroundColor: "#1890ff", color: "#fff" }}>
						蓝色
					</DelightfulAvatar>
					<DelightfulAvatar style={{ backgroundColor: "#722ed1", color: "#fff" }}>
						紫色
					</DelightfulAvatar>
					<DelightfulAvatar style={{ border: "2px solid #f50" }}>边框</DelightfulAvatar>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="图片头像"
				description="支持使用图片作为头像，加载失败时会自动回退到文字头像"
				code="src: string"
			>
				<Space wrap>
					<DelightfulAvatar src="https://api.dicebear.com/7.x/avataaars/svg?seed=John" />
					<DelightfulAvatar src="https://api.dicebear.com/7.x/avataaars/svg?seed=Alice" />
					<DelightfulAvatar src="https://api.dicebear.com/7.x/avataaars/svg?seed=Bob" />
					<DelightfulAvatar src="invalid-url">回退</DelightfulAvatar>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="复杂场景"
				description="在实际应用中的使用场景"
				code="badgeProps + size + style"
			>
				<Space direction="vertical" style={{ width: "100%" }}>
					<div>
						<strong>在线用户列表：</strong>
						<Space wrap style={{ marginLeft: 16 }}>
							<DelightfulAvatar size={32} badgeProps={{ status: "success" }}>
								张三
							</DelightfulAvatar>
							<DelightfulAvatar size={32} badgeProps={{ status: "success" }}>
								李四
							</DelightfulAvatar>
							<DelightfulAvatar size={32} badgeProps={{ status: "default" }}>
								王五
							</DelightfulAvatar>
						</Space>
					</div>
					<div style={{ marginTop: 16 }}>
						<strong>消息通知：</strong>
						<Space wrap style={{ marginLeft: 16 }}>
							<DelightfulAvatar size={40} badgeProps={{ count: 3 }}>
								系统
							</DelightfulAvatar>
							<DelightfulAvatar size={40} badgeProps={{ count: 12 }}>
								团队
							</DelightfulAvatar>
							<DelightfulAvatar size={40} badgeProps={{ dot: true }}>
								私信
							</DelightfulAvatar>
						</Space>
					</div>
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default AvatarDemo
