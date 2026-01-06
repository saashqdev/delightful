import { render } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulFileIcon from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulFileIcon", () => {
	it("Should render normally", () => {
		renderWithTheme(<DelightfulFileIcon type="pdf" />)
		expect(true).toBe(true)
	})

	// Snapshot test
	describe("Snapshot Test", () => {
		it("PDF File Icon Snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulFileIcon type="pdf" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Word File Icon Snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulFileIcon type="word" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Excel File Icon Snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulFileIcon type="excel" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Image File Icon Snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulFileIcon type="image" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Video File Icon Snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulFileIcon type="video" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("File Icon Snapshot with Custom Style", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulFileIcon type="pdf" style={{ width: "32px", height: "32px" }} />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("File Icon Snapshot with Class Name", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulFileIcon type="pdf" className="custom-file-icon" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
