import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulTable from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulTable", () => {
	it("Should render normally", () => {
		renderWithTheme(
			<DelightfulTable
				columns={[
					{ title: "Name", dataIndex: "name", key: "name" },
					{ title: "Age", dataIndex: "age", key: "age" },
				]}
				dataSource={[
					{ key: "1", name: "Zhang San", age: 25 },
					{ key: "2", name: "Li Si", age: 30 },
				]}
			/>,
		)
		expect(screen.getByText("Name")).toBeInTheDocument()
		expect(screen.getByText("Age")).toBeInTheDocument()
		expect(screen.getByText("Zhang San")).toBeInTheDocument()
		expect(screen.getByText("Li Si")).toBeInTheDocument()
	})

	// Snapshot test
	describe("Snapshot Test", () => {
		it("Basic Table Snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulTable
					columns={[
						{ title: "Name", dataIndex: "name", key: "name" },
						{ title: "Age", dataIndex: "age", key: "age" },
					]}
					dataSource={[
						{ key: "1", name: "Zhang San", age: 25 },
						{ key: "2", name: "Li Si", age: 30 },
					]}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Empty Data Table Snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulTable
					columns={[
						{ title: "Name", dataIndex: "name", key: "name" },
						{ title: "Age", dataIndex: "age", key: "age" },
					]}
					dataSource={[]}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Table Snapshot with Pagination", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulTable
					columns={[
						{ title: "Name", dataIndex: "name", key: "name" },
						{ title: "Age", dataIndex: "age", key: "age" },
					]}
					dataSource={[
						{ key: "1", name: "Zhang San", age: 25 },
						{ key: "2", name: "Li Si", age: 30 },
					]}
					pagination={{ current: 1, pageSize: 10, total: 2 }}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Table Snapshot with Sorting", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulTable
					columns={[
						{ title: "Name", dataIndex: "name", key: "name", sorter: true },
						{ title: "Age", dataIndex: "age", key: "age", sorter: true },
					]}
					dataSource={[
						{ key: "1", name: "Zhang San", age: 25 },
						{ key: "2", name: "Li Si", age: 30 },
					]}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Table Snapshot with Selection", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulTable
					rowSelection={{}}
					columns={[
						{ title: "Name", dataIndex: "name", key: "name" },
						{ title: "Age", dataIndex: "age", key: "age" },
					]}
					dataSource={[
						{ key: "1", name: "Zhang San", age: 25 },
						{ key: "2", name: "Li Si", age: 30 },
					]}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Compact Table Snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulTable
					size="small"
					columns={[
						{ title: "Name", dataIndex: "name", key: "name" },
						{ title: "Age", dataIndex: "age", key: "age" },
					]}
					dataSource={[
						{ key: "1", name: "Zhang San", age: 25 },
						{ key: "2", name: "Li Si", age: 30 },
					]}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
