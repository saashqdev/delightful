import { render } from "@testing-library/react"
import DelightfulIcon from "../index"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulIcon", () => {
	it("should render normally", () => {
		renderWithTheme(<DelightfulIcon name="user" />)
		expect(true).toBe(true)
	})

	// Snapshot test
	describe("Snapshot tests", () => {
		it("basic icon snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulIcon name="user" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("different icon snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulIcon name="home" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("icon with custom style snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulIcon name="user" style={{ color: "red", fontSize: "20px" }} />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("icon with custom class snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulIcon name="user" className="custom-icon" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("large size icon snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulIcon name="user" size="large" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("small size icon snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulIcon name="user" size="small" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("icon with click event snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulIcon name="user" onClick={() => {}} />)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
