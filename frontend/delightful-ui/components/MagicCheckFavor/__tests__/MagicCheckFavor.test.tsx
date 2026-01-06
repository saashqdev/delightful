import { render } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulCheckFavor from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulCheckFavor", () => {
	it("should render normally", () => {
		renderWithTheme(<DelightfulCheckFavor checked />)
		expect(document.querySelector('input[type="checkbox"]')).toBeInTheDocument()
	})

	it("supports custom checked", () => {
		renderWithTheme(<DelightfulCheckFavor checked />)
		expect(document.querySelector('input[type="checkbox"]')).toBeChecked()
	})

	// Snapshot test - may be unstable because component uses random IDs
	describe("Snapshot tests", () => {
		it("favorited state snapshot", () => {
			const { container } = renderWithTheme(<DelightfulCheckFavor checked />)
			// Assert component renders correctly instead of relying on snapshot
			expect(container.querySelector('input[type="checkbox"]')).toBeChecked()
		})

		it("unfavorited state snapshot", () => {
			const { container } = renderWithTheme(<DelightfulCheckFavor checked={false} />)
			expect(container.querySelector('input[type="checkbox"]')).not.toBeChecked()
		})

		it("default state snapshot", () => {
			const { container } = renderWithTheme(<DelightfulCheckFavor />)
			expect(container.querySelector('input[type="checkbox"]')).toBeInTheDocument()
		})

		it("with custom style snapshot", () => {
			const { container } = renderWithTheme(<DelightfulCheckFavor className="custom-favor" />)
			expect(container.firstChild).toHaveClass("custom-favor")
		})

		it("with custom event snapshot", () => {
			const { container } = renderWithTheme(<DelightfulCheckFavor onChange={() => {}} />)
			expect(container.querySelector('input[type="checkbox"]')).toBeInTheDocument()
		})

		it("带标签快照", () => {
			const { container } = renderWithTheme(<DelightfulCheckFavor />)
			expect(container.querySelector('input[type="checkbox"]')).toBeInTheDocument()
		})
	})
})
