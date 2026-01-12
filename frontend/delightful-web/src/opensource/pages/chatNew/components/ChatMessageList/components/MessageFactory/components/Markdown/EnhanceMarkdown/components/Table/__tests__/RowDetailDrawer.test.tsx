import { render, screen, fireEvent } from "@testing-library/react"
import { describe, expect, it, vi } from "vitest"
import RowDetailDrawer from "../RowDetailDrawer"

// Mock antd components
vi.mock("antd", () => ({
	Drawer: ({ children, title, open, onClose }: any) => {
		return open ? (
			<div data-testid="drawer">
				<div data-testid="drawer-title">{title}</div>
				<button data-testid="drawer-close" onClick={onClose}>
					Close
				</button>
				{children}
			</div>
		) : null
	},
	Form: {
		Item: ({ children, label }: any) => (
			<div data-testid="form-item">
				<div data-testid="form-label">{label}</div>
				<div data-testid="form-content">{children}</div>
			</div>
		),
	},
}))

// Mock styles
vi.mock("../styles", () => ({
	useTableStyles: () => ({
		styles: {
			detailForm: "detail-form-class",
			formValueContent: "form-value-content-class",
		},
	}),
}))

describe("RowDetailDrawer", () => {
	const mockRowData = {
		0: "First column data",
		1: "Second column data",
		2: "Third column data",
		名称: "First column data",
		描述: "Second column data",
		status: "Third column data",
	}

	const mockHeaders = ["名称", "描述", "status"]

	it("should render drawer when visible is true", () => {
		render(
			<RowDetailDrawer
				visible={true}
				onClose={vi.fn()}
				rowData={mockRowData}
				headers={mockHeaders}
				title="Row Details"
			/>,
		)

		expect(screen.getByTestId("drawer")).toBeDefined()
		expect(screen.getByTestId("drawer-title")).toBeDefined()
		expect(screen.getByTestId("drawer-title").textContent).toBe("Row Details")
	})

	it("should not render drawer when visible is false", () => {
		render(
			<RowDetailDrawer
				visible={false}
				onClose={vi.fn()}
				rowData={mockRowData}
				headers={mockHeaders}
			/>,
		)

		expect(screen.queryByTestId("drawer")).toBeNull()
	})

	it("should use default title", () => {
		render(
			<RowDetailDrawer
				visible={true}
				onClose={vi.fn()}
				rowData={mockRowData}
				headers={mockHeaders}
			/>,
		)

		expect(screen.getByTestId("drawer-title").textContent).toBe("Details")
	})

	it("should correctly render form items", () => {
		render(
			<RowDetailDrawer
				visible={true}
				onClose={vi.fn()}
				rowData={mockRowData}
				headers={mockHeaders}
			/>,
		)

		const formItems = screen.getAllByTestId("form-item")
		expect(formItems).toHaveLength(3)

		const labels = screen.getAllByTestId("form-label")
		expect(labels[0].textContent).toBe("名称")
		expect(labels[1].textContent).toBe("描述")
		expect(labels[2].textContent).toBe("status")

		const contents = screen.getAllByTestId("form-content")
		expect(contents[0].textContent).toBe("First column data")
		expect(contents[1].textContent).toBe("Second column data")
		expect(contents[2].textContent).toBe("Third column data")
	})

	it("should handle missing data", () => {
		const incompleteRowData = {
			0: "First column data",
			名称: "First column data",
		}

		render(
			<RowDetailDrawer
				visible={true}
				onClose={vi.fn()}
				rowData={incompleteRowData}
				headers={["名称", "描述", "status"]}
			/>,
		)

		const contents = screen.getAllByTestId("form-content")
		expect(contents[0].textContent).toBe("First column data")
		expect(contents[1].textContent).toBe("") // Missing data displays as empty
		expect(contents[2].textContent).toBe("") // Missing data displays as empty
	})

	it("should correctly call onClose callback", () => {
		const mockOnClose = vi.fn()

		render(
			<RowDetailDrawer
				visible={true}
				onClose={mockOnClose}
				rowData={mockRowData}
				headers={mockHeaders}
			/>,
		)

		const closeButton = screen.getByTestId("drawer-close")
		fireEvent.click(closeButton)

		expect(mockOnClose).toHaveBeenCalledTimes(1)
	})

	it("should handle empty headers array", () => {
		render(
			<RowDetailDrawer visible={true} onClose={vi.fn()} rowData={mockRowData} headers={[]} />,
		)

		const formItems = screen.queryAllByTestId("form-item")
		expect(formItems).toHaveLength(0)
	})

	it("should handle React nodes as values", () => {
		const rowDataWithJSX = {
			0: <span>JSX Content</span>,
			名称: <span>JSX Content</span>,
		}

		render(
			<RowDetailDrawer
				visible={true}
				onClose={vi.fn()}
				rowData={rowDataWithJSX}
				headers={["名称"]}
			/>,
		)

		expect(screen.getByText("JSX Content")).toBeDefined()
	})

	it("should prioritize index keys for data retrieval", () => {
		const conflictRowData = {
			0: "Index data",
			名称: "Name data",
		}

		render(
			<RowDetailDrawer
				visible={true}
				onClose={vi.fn()}
				rowData={conflictRowData}
				headers={["名称"]}
			/>,
		)

		const content = screen.getByTestId("form-content")
		expect(content.textContent).toBe("Index data") // Should prioritize index 0 value
	})
})
