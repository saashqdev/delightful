import React from "react"
import { Space } from "antd"
import DelightfulSwitch from "../../components/DelightfulSwitch"
import ComponentDemo from "./Container"

const SwitchDemo: React.FC = () => {
	return (
		<div>
			<ComponentDemo title="Basic Switch" description="Most basic switch component" code="<DelightfulSwitch />">
				<Space>
					<DelightfulSwitch />
					<DelightfulSwitch defaultChecked />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Switch Sizes"
				description="Supports different sized switches"
				code="size: 'default' | 'small'"
			>
				<Space>
					<DelightfulSwitch size="default" />
					<DelightfulSwitch size="small" />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Switch States"
				description="Supports loading and disabled states"
				code="loading | disabled"
			>
				<Space>
					<DelightfulSwitch loading />
					<DelightfulSwitch size="small" loading />
					<DelightfulSwitch disabled />
					<DelightfulSwitch defaultChecked disabled />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Switch with Text"
				description="Can customize text on both ends of the switch"
				code="checkedChildren | unCheckedChildren"
			>
				<Space direction="vertical">
					<DelightfulSwitch checkedChildren="On" unCheckedChildren="Off" />
					<DelightfulSwitch checkedChildren="ON" unCheckedChildren="OFF" />
					<DelightfulSwitch checkedChildren="✅" unCheckedChildren="❌" />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Controlled Switch"
				description="Control switch state through checked and onChange"
				code="checked | onChange"
			>
				<Space>
					<DelightfulSwitch
						checked={true}
						onChange={(checked) => console.log("Switch state:", checked)}
					/>
					<DelightfulSwitch
						checked={false}
						onChange={(checked) => console.log("Switch state:", checked)}
					/>
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default SwitchDemo
