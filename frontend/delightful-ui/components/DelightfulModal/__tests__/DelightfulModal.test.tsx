import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulModal from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulModal", () => {
	it("should render normally", () => {
		renderWithTheme(
			<DelightfulModal open title="Test Title">
				Test Content
			</DelightfulModal>,
		)
		expect(screen.getByText("Test Title")).toBeInTheDocument()
		expect(screen.getByText("Test Content")).toBeInTheDocument()
	})

	// Snapshot test
	describe("Snapshot tests", () => {
		it("basic modal snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulModal open title="Basic Modal">
					This is modal content
				</DelightfulModal>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("closed state modal snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulModal open={false} title="Closed Modal">
					This is modal content
				</DelightfulModal>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("modal with confirm button snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulModal open title="Confirm Modal" okText="Confirm" cancelText="Cancel">
					Are you sure you want to perform this action?
				</DelightfulModal>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("custom width modal snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulModal open title="Wide Modal" width={800}>
					This is a wider modal
				</DelightfulModal>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("modal without title snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulModal open>Modal without title</DelightfulModal>)
			expect(asFragment()).toMatchSnapshot()
		})

		it("centered modal snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulModal open title="Centered Modal" centered>
					This is a centered modal
				</DelightfulModal>,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
