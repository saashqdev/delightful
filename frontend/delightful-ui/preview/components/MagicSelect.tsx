import React from "react"
import { Space } from "antd"
import DelightfulSelect from "../../components/DelightfulSelect"
import ComponentDemo from "./Container"

const SelectDemo: React.FC = () => {
	const options = [
		{ value: "option1", label: "选项1" },
		{ value: "option2", label: "选项2" },
		{ value: "option3", label: "选项3" },
		{ value: "option4", label: "选项4" },
		{ value: "option5", label: "选项5" },
	]

	return (
		<div>
			<ComponentDemo
				title="基础选择器"
				description="最基本的选择器组件"
				code="<DelightfulSelect placeholder='请选择' options={options} />"
			>
				<Space>
					<DelightfulSelect placeholder="请选择" style={{ width: 200 }} options={options} />
				</Space>
			</ComponentDemo>

			<ComponentDemo title="多选选择器" description="支持多选的选择器" code="mode='multiple'">
				<Space>
					<DelightfulSelect
						mode="multiple"
						placeholder="请选择多个选项"
						style={{ width: 300 }}
						options={options}
					/>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="不同尺寸"
				description="支持不同尺寸的选择器"
				code="size: 'large' | 'middle' | 'small'"
			>
				<Space direction="vertical">
					<DelightfulSelect
						size="large"
						placeholder="大尺寸选择器"
						style={{ width: 200 }}
						options={options}
					/>
					<DelightfulSelect
						placeholder="默认尺寸选择器"
						style={{ width: 200 }}
						options={options}
					/>
					<DelightfulSelect
						size="small"
						placeholder="小尺寸选择器"
						style={{ width: 200 }}
						options={options}
					/>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="可搜索选择器"
				description="支持搜索功能的选择器"
				code="showSearch"
			>
				<Space>
					<DelightfulSelect
						showSearch
						placeholder="可搜索的选择器"
						style={{ width: 200 }}
						options={options}
						filterOption={(input, option) =>
							!!option?.value?.toString().includes(input)
						}
					/>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="事件处理"
				description="监听选择变化事件"
				code="onChange | onSelect | onDeselect"
			>
				<Space direction="vertical">
					<DelightfulSelect
						placeholder="监听选择变化"
						style={{ width: 200 }}
						options={options}
						onChange={(value) => console.log("选择的值:", value)}
					/>
					<DelightfulSelect
						mode="multiple"
						placeholder="监听多选变化"
						style={{ width: 300 }}
						options={options}
						onChange={(value) => console.log("多选的值:", value)}
					/>
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default SelectDemo
