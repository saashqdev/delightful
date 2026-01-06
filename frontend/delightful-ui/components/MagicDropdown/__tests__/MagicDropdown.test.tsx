import { render } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulDropdown from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulDropdown", () => {
	it("Should render normally", () => {
		renderWithTheme(<DelightfulDropdown menu={{ items: [] }}>Dropdown</DelightfulDropdown>)
		// Pass if no errors are thrown
		expect(true).toBe(true)
	})

	// Snapshot test
	describe("Snapshot test", () => {
		it("Basic dropdown snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulDropdown menu={{ items: [] }}>Dropdown menu</DelightfulDropdown>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Dropdown with menu items snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulDropdown
					menu={{
						items: [
							{ key: "1", label: "Menu item 1" },
							{ key: "2", label: "Menu item 2" },
						],
					}}
				>
					Dropdown menu
				</DelightfulDropdown>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Dropdown with icon snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulDropdown
					menu={{
						items: [
							{ key: "1", label: "Menu item 1", icon: <span>🏠</span> },
							{ key: "2", label: "Menu item 2", icon: <span>⚙️</span> },
						],
					}}
				>
					<div>🏠 Dropdown menu</div>
				</DelightfulDropdown>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Disabled dropdown snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulDropdown disabled menu={{ items: [] }}>
					Dropdown menu
				</DelightfulDropdown>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Dropdown with custom attributes snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulDropdown data-testid="custom-dropdown" menu={{ items: [] }}>
					Dropdown menu
				</DelightfulDropdown>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Dropdown with class name snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulDropdown className="custom-dropdown" menu={{ items: [] }}>
					Dropdown menu
				</DelightfulDropdown>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Button style dropdown snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulDropdown menu={{ items: [] }}>
					<button>Button dropdown</button>
				</DelightfulDropdown>,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
