import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { Collapse } from "antd"
import { describe, it, expect } from "vitest"
import DelightfulCollapse from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulCollapse", () => {
	it("should render normally", () => {
		renderWithTheme(
			<DelightfulCollapse>
				<Collapse.Panel key="1" header="Title 1">
					Content 1
				</Collapse.Panel>
			</DelightfulCollapse>,
		)
		expect(screen.getByText("Title 1")).toBeInTheDocument()
	})

	// Snapshot test
	describe("Snapshot tests", () => {
		it("basic collapse panel snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulCollapse>
					<Collapse.Panel key="1" header="Title 1">
						Content 1
					</Collapse.Panel>
				</DelightfulCollapse>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("multiple panels collapse snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulCollapse>
					<Collapse.Panel key="1" header="Title 1">
						Content 1
					</Collapse.Panel>
					<Collapse.Panel key="2" header="Title 2">
						Content 2
					</Collapse.Panel>
					<Collapse.Panel key="3" header="Title 3">
						Content 3
					</Collapse.Panel>
				</DelightfulCollapse>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("default expanded panel snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulCollapse defaultActiveKey={["1"]}>
					<Collapse.Panel key="1" header="Title 1">
						Content 1
					</Collapse.Panel>
					<Collapse.Panel key="2" header="Title 2">
						Content 2
					</Collapse.Panel>
				</DelightfulCollapse>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("accordion mode snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulCollapse accordion>
					<Collapse.Panel key="1" header="Title 1">
						Content 1
					</Collapse.Panel>
					<Collapse.Panel key="2" header="Title 2">
						Content 2
					</Collapse.Panel>
				</DelightfulCollapse>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("panel with icon snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulCollapse>
					<Collapse.Panel key="1" header="Title 1" extra={<span>📝</span>}>
						Content 1
					</Collapse.Panel>
				</DelightfulCollapse>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("disabled panel snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulCollapse>
					<Collapse.Panel key="1" header="Title 1" disabled>
						Content 1
					</Collapse.Panel>
				</DelightfulCollapse>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("small size collapse panel snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulCollapse size="small">
					<Collapse.Panel key="1" header="Title 1">
						Content 1
					</Collapse.Panel>
				</DelightfulCollapse>,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
