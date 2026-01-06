import React, { useState } from "react"
import { Space } from "antd"
import DelightfulCheckFavor from "../../components/DelightfulCheckFavor"
import ComponentDemo from "./Container"

const CheckFavorDemo: React.FC = () => {
	const [checked1, setChecked1] = useState(false)
	const [checked2, setChecked2] = useState(true)

	return (
		<div>
			<ComponentDemo
				title="Basic Favor"
				description="Most basic favor component"
				code="<DelightfulCheckFavor />"
			>
				<Space>
					<DelightfulCheckFavor />
					<DelightfulCheckFavor defaultChecked />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Controlled Favor"
				description="Control favor state through checked and onChange"
				code="checked | onChange"
			>
				<Space>
					<DelightfulCheckFavor
						checked={checked1}
						onChange={(checked) => setChecked1(checked as boolean)}
					/>
					<span>Status: {checked1 ? "Favored" : "Not Favored"}</span>
				</Space>
			</ComponentDemo>

			<ComponentDemo title="Default State" description="Set default favor state" code="defaultChecked">
				<Space>
					<DelightfulCheckFavor defaultChecked={false} />
					<DelightfulCheckFavor defaultChecked={true} />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Event Handling"
				description="Listen to favor state changes"
				code="onChange: (checked: boolean) => void"
			>
				<Space direction="vertical">
					<DelightfulCheckFavor
						checked={checked2}
						onChange={(checked) => {
							setChecked2(checked as boolean)
							console.log("Favor state changed:", checked)
						}}
					/>
					<span>Current state: {checked2 ? "Favored" : "Not Favored"}</span>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Custom Style"
				description="Customize style through className and style"
				code="className | style"
			>
				<Space>
					<DelightfulCheckFavor className="custom-favor" style={{ transform: "scale(1.5)" }} />
					<DelightfulCheckFavor
						defaultChecked
						style={{
							transform: "scale(1.2)",
							filter: "hue-rotate(90deg)",
						}}
					/>
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default CheckFavorDemo
