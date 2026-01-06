import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulEllipseWithTooltip from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulEllipseWithTooltip", () => {
	it("should render normally", () => {
		renderWithTheme(<DelightfulEllipseWithTooltip text="Test content" maxWidth="100px" />)
		expect(screen.getByText("Test content")).toBeInTheDocument()
	})

	// Snapshot test
	describe("Snapshot tests", () => {
		it("basic ellipsis text snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulEllipseWithTooltip text="Test content" maxWidth="100px" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("long text ellipsis snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulEllipseWithTooltip
					text="This is a very long text content that should be displayed with ellipsis"
					maxWidth="100px"
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("custom max width snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulEllipseWithTooltip text="Test content" maxWidth="200px" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("with custom style snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulEllipseWithTooltip
					text="Test content"
					maxWidth="100px"
					style={{ color: "red" }}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("with custom class snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulEllipseWithTooltip
					text="Test content"
					maxWidth="100px"
					className="custom-ellipse"
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("empty text snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulEllipseWithTooltip text="" maxWidth="100px" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
