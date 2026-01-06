import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulPageContainer from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulPageContainer", () => {
	it("should render normally", () => {
		renderWithTheme(<DelightfulPageContainer title="Page Title">Page content</DelightfulPageContainer>)
		expect(screen.getByText("Page Title")).toBeInTheDocument()
		expect(screen.getByText("Page content")).toBeInTheDocument()
	})

	// Snapshot test
	describe("Snapshot tests", () => {
		it("basic page container snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulPageContainer title="Page Title">Page content</DelightfulPageContainer>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("page container with class name snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulPageContainer title="Page Title" className="custom-page">
					Page content
				</DelightfulPageContainer>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("page container with custom attributes snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulPageContainer title="Page Title" data-testid="page-container">
					Page content
				</DelightfulPageContainer>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("page container with action button snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulPageContainer title="Page Title" extra={<button>Action Button</button>}>
					Page content
				</DelightfulPageContainer>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("page container with tabs snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulPageContainer
					title="Page Title"
					tabList={[
						{ key: "tab1", tab: "Tab 1" },
						{ key: "tab2", tab: "Tab 2" },
					]}
				>
					Page content
				</DelightfulPageContainer>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("page container with custom styles snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulPageContainer title="Page Title" style={{ backgroundColor: "#f0f0f0" }}>
					Page content
				</DelightfulPageContainer>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("page container without title snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulPageContainer>Page content</DelightfulPageContainer>,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
