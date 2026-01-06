import React from "react"
import { Space } from "antd"
import DelightfulAvatar from "../../components/DelightfulAvatar"
import ComponentDemo from "./Container"

const AvatarDemo: React.FC = () => {
	return (
		<div>
			<ComponentDemo
				title="Basic usage"
				description="Supports text and image avatars; text avatars auto-generate colors"
				code="children: string | ReactNode"
			>
				<Space wrap>
					<DelightfulAvatar>Alice</DelightfulAvatar>
					<DelightfulAvatar>Bob</DelightfulAvatar>
					<DelightfulAvatar>Carol</DelightfulAvatar>
					<DelightfulAvatar>John</DelightfulAvatar>
					<DelightfulAvatar>Eve</DelightfulAvatar>
					<DelightfulAvatar src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix" />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Sizes"
				description="Supports custom sizes; default is 40px"
				code="size: 'small' | 'default' | 'large' | number"
			>
				<Space wrap align="center">
					<DelightfulAvatar size="small">Small</DelightfulAvatar>
					<DelightfulAvatar>Default</DelightfulAvatar>
					<DelightfulAvatar size="large">Large</DelightfulAvatar>
					<DelightfulAvatar size={96}>XL</DelightfulAvatar>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Avatars with badges"
				description="Show badges on avatars for status or notification counts"
				code="badgeProps: BadgeProps"
			>
				<Space wrap>
					<DelightfulAvatar
						badgeProps={{
							count: 5,
						}}
					>
						Alice
					</DelightfulAvatar>
					<DelightfulAvatar
						badgeProps={{
							dot: true,
						}}
					>
						Bob
					</DelightfulAvatar>
					<DelightfulAvatar
						badgeProps={{
							status: "success",
						}}
					>
						Carol
					</DelightfulAvatar>
					<DelightfulAvatar
						badgeProps={{
							status: "error",
						}}
					>
						Dave
					</DelightfulAvatar>
					<DelightfulAvatar
						badgeProps={{
							status: "processing",
						}}
					>
						Eve
					</DelightfulAvatar>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Custom styles"
				description="Customize background and text colors"
				code="style: CSSProperties"
			>
				<Space wrap>
					<DelightfulAvatar style={{ backgroundColor: "#f50", color: "#fff" }}>
						Red
					</DelightfulAvatar>
					<DelightfulAvatar style={{ backgroundColor: "#52c41a", color: "#fff" }}>
						Green
					</DelightfulAvatar>
					<DelightfulAvatar style={{ backgroundColor: "#1890ff", color: "#fff" }}>
						Blue
					</DelightfulAvatar>
					<DelightfulAvatar style={{ backgroundColor: "#722ed1", color: "#fff" }}>
						Purple
					</DelightfulAvatar>
					<DelightfulAvatar style={{ border: "2px solid #f50" }}>Border</DelightfulAvatar>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Image avatars"
				description="Supports image avatars and falls back to text when loading fails"
				code="src: string"
			>
				<Space wrap>
					<DelightfulAvatar src="https://api.dicebear.com/7.x/avataaars/svg?seed=John" />
					<DelightfulAvatar src="https://api.dicebear.com/7.x/avataaars/svg?seed=Alice" />
					<DelightfulAvatar src="https://api.dicebear.com/7.x/avataaars/svg?seed=Bob" />
					<DelightfulAvatar src="invalid-url">Fallback</DelightfulAvatar>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Complex scenarios"
				description="Real-world usage examples"
				code="badgeProps + size + style"
			>
				<Space direction="vertical" style={{ width: "100%" }}>
					<div>
						<strong>Online users:</strong>
						<Space wrap style={{ marginLeft: 16 }}>
							<DelightfulAvatar size={32} badgeProps={{ status: "success" }}>
								Alice
							</DelightfulAvatar>
							<DelightfulAvatar size={32} badgeProps={{ status: "success" }}>
								Bob
							</DelightfulAvatar>
							<DelightfulAvatar size={32} badgeProps={{ status: "default" }}>
								Carol
							</DelightfulAvatar>
						</Space>
					</div>
					<div style={{ marginTop: 16 }}>
						<strong>Message notifications:</strong>
						<Space wrap style={{ marginLeft: 16 }}>
							<DelightfulAvatar size={40} badgeProps={{ count: 3 }}>
								System
							</DelightfulAvatar>
							<DelightfulAvatar size={40} badgeProps={{ count: 12 }}>
								Team
							</DelightfulAvatar>
							<DelightfulAvatar size={40} badgeProps={{ dot: true }}>
								DM
							</DelightfulAvatar>
						</Space>
					</div>
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default AvatarDemo
