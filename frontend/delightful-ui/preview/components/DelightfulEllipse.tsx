import React from "react"
import { Space } from "antd"
import DelightfulEllipseWithTooltip from "../../components/DelightfulEllipseWithTooltip"
import ComponentDemo from "./Container"

const EllipseDemo: React.FC = () => {
	return (
		<div>
			<ComponentDemo
				title="Basic Ellipsis"
				description="Show ellipsis when text exceeds max width, hover to display full content"
				code="<DelightfulEllipseWithTooltip text='...' maxWidth='200px' />"
			>
				<Space direction="vertical">
					<DelightfulEllipseWithTooltip
						text="This is a very long text that will be truncated with ellipsis and displayed in a tooltip"
						maxWidth="200px"
					/>
					<DelightfulEllipseWithTooltip text="Short text will not show ellipsis" maxWidth="200px" />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Custom Max Width"
				description="Can customize ellipsis width through maxWidth property"
				code="maxWidth: string"
			>
				<Space direction="vertical">
					<DelightfulEllipseWithTooltip text="Ellipsis effect with 100px width" maxWidth="100px" />
					<DelightfulEllipseWithTooltip
						text="Ellipsis effect with 300px width, longer content won't be truncated"
						maxWidth="300px"
					/>
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default EllipseDemo
