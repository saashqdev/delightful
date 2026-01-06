import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulImage from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulImage", () => {
	it("Should render image normally", () => {
		renderWithTheme(<DelightfulImage src="test.jpg" alt="Test image" />
		expect(screen.getByAltText("Test image")).toBeInTheDocument()
	})

	// Snapshot test
	describe("Snapshot test", () => {
		it("Basic image snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulImage src="test.jpg" alt="Test image" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Image with class name snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulImage src="test.jpg" alt="Test image" className="custom-image" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Image with click event snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulImage src="test.jpg" alt="Test image" onClick={() => {}} />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Custom width image snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulImage src="test.jpg" alt="Test image" width={200} />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Custom height image snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulImage src="test.jpg" alt="Test image" height={150} />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Image with styles snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulImage src="test.jpg" alt="Test image" style={{ borderRadius: "8px" }} />,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
