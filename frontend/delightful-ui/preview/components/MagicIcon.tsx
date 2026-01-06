import React from "react"
import { Space } from "antd"
import DelightfulIcon from "../../components/DelightfulIcon"
import ComponentDemo from "./Container"
import { IconSearch, IconFolder, IconSettings, IconUser, IconHome } from "@tabler/icons-react"

const IconDemo: React.FC = () => {
	return (
		<div>
			<ComponentDemo
				title="基础图标"
				description="最基本的图标组件"
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
				title="不同尺寸"
				description="支持不同尺寸的图标"
				code="size: number | string"
			>
				<Space>
					<DelightfulIcon component={IconHome} size={16} />
					<DelightfulIcon component={IconHome} size={24} />
					<DelightfulIcon component={IconHome} size={32} />
					<DelightfulIcon component={IconHome} size={48} />
				</Space>
			</ComponentDemo>

			<ComponentDemo title="不同颜色" description="支持不同颜色的图标" code="color: string">
				<Space>
					<DelightfulIcon component={IconHome} color="#1890ff" />
					<DelightfulIcon component={IconHome} color="#52c41a" />
					<DelightfulIcon component={IconHome} color="#faad14" />
					<DelightfulIcon component={IconHome} color="#f5222d" />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="描边宽度"
				description="自定义图标的描边宽度"
				code="stroke: number"
			>
				<Space>
					<DelightfulIcon component={IconHome} stroke={1} />
					<DelightfulIcon component={IconHome} stroke={2} />
					<DelightfulIcon component={IconHome} stroke={3} />
				</Space>
			</ComponentDemo>

			<ComponentDemo title="自定义组件" description="使用自定义的图标组件" code="component">
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
