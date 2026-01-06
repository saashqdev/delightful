import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulStreamContent from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulStreamContent", () => {
	it("Should render normally", () => {
		renderWithTheme(<DelightfulStreamContent content="Streaming Content" />)
		expect(screen.getByText("Streaming Content")).toBeInTheDocument()
	})

	// Snapshot test
	describe("Snapshot Test", () => {
		it("Basic Streaming Content Snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulStreamContent content="Streaming Content" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Long Text Streaming Content Snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulStreamContent content="This is a long streaming content for testing component display" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Snapshot with Custom Style", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulStreamContent
					content="Streaming Content"
					style={{ fontSize: "16px", color: "blue" }}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Snapshot with Class Name", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulStreamContent content="Streaming Content" className="custom-stream" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Empty Content Snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulStreamContent content="" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Snapshot with HTML Tag Content", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulStreamContent content="<strong>Bold Text</strong>" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
