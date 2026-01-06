import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulSearch from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulSearch", () => {
	it("should render normally", () => {
		renderWithTheme(<DelightfulSearch placeholder="Search..." />)
		expect(screen.getByPlaceholderText("Search...")).toBeInTheDocument()
	})

	// Snapshot test
	describe("Snapshot tests", () => {
		it("basic search box snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulSearch placeholder="Search..." />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("search box with default value snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulSearch defaultValue="default search term" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("search box with prefix icon snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulSearch placeholder="Search..." prefix={<span>🔍</span>} />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("search box with suffix icon snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulSearch placeholder="Search..." suffix={<span>📝</span>} />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("disabled state search box snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulSearch placeholder="Search..." disabled />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("大尺寸搜索框快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulSearch placeholder="搜索..." size="large" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("小尺寸搜索框快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulSearch placeholder="搜索..." size="small" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带边框搜索框快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulSearch placeholder="搜索..." bordered />)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
