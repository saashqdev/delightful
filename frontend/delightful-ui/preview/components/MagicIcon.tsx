import React from "react"
import { Space } from "antd"
import DelightfulIcon from "../../components/DelightfulIcon"
import ComponentDemo from "./Container"
import { IconSearch, IconFolder, IconSettings, IconUser, IconHome } from "@tabler/icons-react"

const IconDemo: React.FC = () => {
	return (
		<div>
			<ComponentDemo
				title="Basic Icon"
				description="Most basic icon component"
				code="<DelightfulIcon name='IconHome' />"
			>
				<Space>
					<DelightfulIcon component={IconHome} />
					<DelightfulIcon component={IconUser} />
					<DelightfulIcon component={IconSettings} />
					<DelightfulIcon component={IconSearch} />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Different Sizes"
				description="Supports different sized icons"
				code="size: number | string"
			>
				<Space>
					<DelightfulIcon component={IconHome} size={16} />
					<DelightfulIcon component={IconHome} size={24} />
					<DelightfulIcon component={IconHome} size={32} />
					<DelightfulIcon component={IconHome} size={48} />
				</Space>
			</ComponentDemo>

			<ComponentDemo title="Different Colors" description="Supports different colored icons" code="color: string">
				<Space>
					<DelightfulIcon component={IconHome} color="#1890ff" />
					<DelightfulIcon component={IconHome} color="#52c41a" />
					<DelightfulIcon component={IconHome} color="#faad14" />
					<DelightfulIcon component={IconHome} color="#f5222d" />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Stroke Width"
				description="Customize icon stroke width"
				code="stroke: number"
			>
				<Space>
					<DelightfulIcon component={IconHome} stroke={1} />
					<DelightfulIcon component={IconHome} stroke={2} />
					<DelightfulIcon component={IconHome} stroke={3} />
				</Space>
			</ComponentDemo>

			<ComponentDemo title="Custom Component" description="Use custom icon component" code="component">
				<Space>
					<DelightfulIcon component={IconSearch} />
					<DelightfulIcon component={IconFolder} />
					<DelightfulIcon component={IconSettings} />
					<DelightfulIcon component={IconUser} />
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default IconDemo
