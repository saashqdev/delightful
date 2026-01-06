import React from "react"
import { Space, Card } from "antd"
import DelightfulSpin from "../../components/DelightfulSpin"
import ComponentDemo from "./Container"

const SpinDemo: React.FC = () => {
	return (
		<div>
			<ComponentDemo title="Basic Loading" description="Most basic loading component" code="<DelightfulSpin />">
				<Space size={100}>
					<DelightfulSpin />
					<DelightfulSpin size="large" />
					<DelightfulSpin size="small" />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Loading with Content Wrapper"
				description="Wrap loading state around content"
				code="<DelightfulSpin><div>Content</div></DelightfulSpin>"
			>
				<DelightfulSpin tip="Loading...">
					<Card title="Content Card" style={{ width: 300 }}>
						<p>This is content wrapped by the loading component</p>
						<p>When loading is true, the loading animation will be displayed</p>
					</Card>
				</DelightfulSpin>
			</ComponentDemo>

			<ComponentDemo
				title="Different Sizes"
				description="Supports different sized loading animations"
				code="size: 'small' | 'default' | 'large'"
			>
				<Space size={100}>
					<DelightfulSpin size="small" tip="Small Size" />
					<DelightfulSpin tip="Default Size" />
					<DelightfulSpin size="large" tip="Large Size" />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Delayed Display"
				description="Delay showing loading animation to avoid flickering"
				code="delay: 500"
			>
				<Space size={100}>
					<DelightfulSpin delay={500} tip="Delay 500ms to display" />
					<DelightfulSpin delay={1000} tip="Delay 1 second to display" />
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default SpinDemo
