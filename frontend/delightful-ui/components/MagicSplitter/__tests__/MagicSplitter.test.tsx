import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulSplitter from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulSplitter", () => {
	it("Should render normally", () => {
		renderWithTheme(
			<DelightfulSplitter>
				<div>Left side</div>
				<div>Right side</div>
			</DelightfulSplitter>,
		)
		expect(screen.getByText("Left side")).toBeInTheDocument()
		expect(screen.getByText("Right side")).toBeInTheDocument()
	})

	// Snapshot test
	describe("Snapshot test", () => {
		it("Basic splitter snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulSplitter>
					<div>Left side content</div>
					<div>Right side content</div>
				</DelightfulSplitter>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("splitter with custom style snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulSplitter style={{ height: "400px" }}>
					<div>Left side content</div>
					<div>Right side content</div>
				</DelightfulSplitter>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("splitter with custom class snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulSplitter className="custom-splitter">
					<div>Left side content</div>
					<div>Right side content</div>
				</DelightfulSplitter>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带自定义样式分割器快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulSplitter style={{ height: "400px" }}>
					<div>左侧内容</div>
					<div>右侧内容</div>
				</DelightfulSplitter>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("splitter with class name snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulSplitter className="custom-splitter">
					<div>Left content</div>
					<div>Right content</div>
			const { asFragment } = renderWithTheme(
				<DelightfulSplitter>
					<div>
						<h3>Left side title</h3>
						<p>Left side detailed content</p>
					</div>
					<div>
						<h3>Right side title</h3>
						<p>Right side detailed content</p>
					</div>
				</DelightfulSplitter>,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
