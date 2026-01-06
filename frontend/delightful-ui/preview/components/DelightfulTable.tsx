import React from "react"
import DelightfulTable from "../../components/DelightfulTable"
import ComponentDemo from "./Container"

const TableDemo: React.FC = () => {
	const dataSource = [
		{
			key: "1",
			name: "Zhang San",
			age: 32,
			address: "Beijing Chaoyang District",
			tags: ["Development", "Frontend"],
		},
		{
			key: "2",
			name: "Li Si",
			age: 42,
			address: "Shanghai Pudong New Area",
			tags: ["Design", "UI"],
		},
		{
			key: "3",
			name: "Wang Wu",
			age: 28,
			address: "Guangzhou Tianhe District",
			tags: ["Testing", "QA"],
		},
	]

	const columns = [
		{
			title: "Name",
			dataIndex: "name",
			key: "name",
		},
		{
			title: "Age",
			dataIndex: "age",
			key: "age",
		},
		{
			title: "Address",
			dataIndex: "address",
			key: "address",
		},
		{
			title: "Tags",
			key: "tags",
			dataIndex: "tags",
			render: (tags: string[]) => (
				<>
					{tags.map((tag) => (
						<span key={tag} style={{ marginRight: 8, color: "#1890ff" }}>
							{tag}
						</span>
					))}
				</>
			),
		},
	]

	return (
		<div>
			<ComponentDemo
				title="Basic Table"
				description="Most basic table component"
				code="<DelightfulTable columns={columns} dataSource={dataSource} />"
			>
				<DelightfulTable columns={columns} dataSource={dataSource} />
			</ComponentDemo>

			<ComponentDemo
				title="Table with Pagination"
				description="Table with pagination support"
				code="pagination: { pageSize: 2 }"
			>
				<DelightfulTable
					columns={columns}
					dataSource={dataSource}
					pagination={{ pageSize: 2 }}
				/>
			</ComponentDemo>

			<ComponentDemo title="Selectable Table" description="Table with row selection support" code="rowSelection">
				<DelightfulTable
					columns={columns}
					dataSource={dataSource}
					rowSelection={{
						onChange: (selectedRowKeys, selectedRows) => {
							console.log("Selected rows:", selectedRowKeys, selectedRows)
						},
					}}
				/>
			</ComponentDemo>

			<ComponentDemo
				title="Sortable Table"
				description="Table with column sorting support"
				code="sorter: (a, b) => a.age - b.age"
			>
				<DelightfulTable
					columns={[
						...columns.slice(0, 2),
						{
							title: "Age",
							dataIndex: "age",
							key: "age",
							sorter: (a, b) => a.age - b.age,
						},
						...columns.slice(2),
					]}
					dataSource={dataSource}
				/>
			</ComponentDemo>

			<ComponentDemo
				title="Table with Loading State"
				description="Table with loading state support"
				code="loading: true"
			>
				<DelightfulTable columns={columns} dataSource={dataSource} loading={true} />
			</ComponentDemo>
		</div>
	)
}

export default TableDemo
