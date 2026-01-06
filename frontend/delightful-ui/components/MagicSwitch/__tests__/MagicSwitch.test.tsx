import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulSwitch from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulSwitch", () => {
	it("Should render normally", () => {
		renderWithTheme(<DelightfulSwitch />)
		expect(screen.getByRole("switch")).toBeInTheDocument()
	})

	// Snapshot test
	describe("Snapshot Test", () => {
		it("Default Switch Snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulSwitch />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Enabled Switch Snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulSwitch defaultChecked />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Disabled Switch Snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulSwitch disabled />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Disabled and Enabled Switch Snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulSwitch disabled defaultChecked />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Small Size Switch Snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulSwitch size="small" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Switch Snapshot with Labels", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulSwitch checkedChildren="On" unCheckedChildren="Off" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
