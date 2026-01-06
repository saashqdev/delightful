import React from "react"
import { Space } from "antd"
import DelightfulSearch from "../../components/DelightfulSearch"
import ComponentDemo from "./Container"

const SearchDemo: React.FC = () => {
	return (
		<div>
			<ComponentDemo
				title="Basic Search"
				description="Most basic search component"
				code="<DelightfulSearch placeholder='Please enter search content' />"
			>
				<Space>
					<DelightfulSearch placeholder="Please enter search content" style={{ width: 300 }} />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Different Sizes"
				description="Supports different sized search boxes"
				code="size: 'large' | 'middle' | 'small'"
			>
				<Space direction="vertical">
					<DelightfulSearch placeholder="Large Size Search" size="large" style={{ width: 300 }} />
					<DelightfulSearch placeholder="Default Size Search" style={{ width: 300 }} />
					<DelightfulSearch placeholder="Small Size Search" size="small" style={{ width: 300 }} />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Event Handling"
				description="Listen to search and input events"
				code="onSearch | onChange"
			>
				<Space direction="vertical">
					<DelightfulSearch
						placeholder="Trigger onChange on input"
						style={{ width: 300 }}
						onChange={(e) => console.log("Input content:", e.target.value)}
					/>
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default SearchDemo
