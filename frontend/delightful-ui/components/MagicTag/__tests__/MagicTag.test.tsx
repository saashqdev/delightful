import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulTag from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulTag", () => {
	it("Should render tag normally", () => {
		renderWithTheme(<DelightfulTag>Tag content</DelightfulTag>)
		expect(screen.getByText("Tag content")).toBeInTheDocument()
	})

	// Snapshot test
	describe("Snapshot test", () => {
		it("Default tag snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulTag>Default tag</DelightfulTag>)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Different colors tag snapshot", () => {
			const { asFragment: blueFragment } = renderWithTheme(
				<DelightfulTag color="blue">Blue tag</DelightfulTag>,
			)
			expect(blueFragment()).toMatchSnapshot()

			const { asFragment: redFragment } = renderWithTheme(
				<DelightfulTag color="red">Red tag</DelightfulTag>,
			)
			expect(redFragment()).toMatchSnapshot()

			const { asFragment: greenFragment } = renderWithTheme(
				<DelightfulTag color="green">Green tag</DelightfulTag>,
			)
			expect(greenFragment()).toMatchSnapshot()
		})

	it("Closable tag snapshot", () => {
		const { asFragment } = renderWithTheme(<DelightfulTag closable>Closable tag</DelightfulTag>)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Different styles tag snapshot", () => {
			const { asFragment: successFragment } = renderWithTheme(
				<DelightfulTag color="success">Success tag</DelightfulTag>,
			)
			expect(successFragment()).toMatchSnapshot()

			const { asFragment: warningFragment } = renderWithTheme(
				<DelightfulTag color="warning">Warning tag</DelightfulTag>,
			)
			expect(warningFragment()).toMatchSnapshot()
		})

		it("Bordered tag snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulTag bordered>Bordered tag</DelightfulTag>)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Icon tag snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulTag icon={<span>🏷️</span>}>Tag with icon</DelightfulTag>,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
