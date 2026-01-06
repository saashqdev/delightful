import React from "react"
import { Space, Card } from "antd"
import DelightfulSpin from "../../components/DelightfulSpin"
import ComponentDemo from "./Container"

const SpinDemo: React.FC = () => {
	return (
		<div>
			<ComponentDemo title="基础加载" description="最基本的加载组件" code="<DelightfulSpin />">
				<Space size={100}>
					<DelightfulSpin />
					<DelightfulSpin size="large" />
					<DelightfulSpin size="small" />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="包裹内容的加载"
				description="将加载状态包裹在内容周围"
				code="<DelightfulSpin><div>内容</div></DelightfulSpin>"
			>
				<DelightfulSpin tip="加载中...">
					<Card title="内容卡片" style={{ width: 300 }}>
						<p>这是被加载组件包裹的内容</p>
						<p>当loading为true时会显示加载动画</p>
					</Card>
				</DelightfulSpin>
			</ComponentDemo>

			<ComponentDemo
				title="不同尺寸"
				description="支持不同尺寸的加载动画"
				code="size: 'small' | 'default' | 'large'"
			>
				<Space size={100}>
					<DelightfulSpin size="small" tip="小尺寸" />
					<DelightfulSpin tip="默认尺寸" />
					<DelightfulSpin size="large" tip="大尺寸" />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="延迟显示"
				description="延迟显示加载动画，避免闪烁"
				code="delay: 500"
			>
				<Space size={100}>
					<DelightfulSpin delay={500} tip="延迟500ms显示" />
					<DelightfulSpin delay={1000} tip="延迟1秒显示" />
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default SpinDemo
