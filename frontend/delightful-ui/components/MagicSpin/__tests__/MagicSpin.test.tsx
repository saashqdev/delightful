import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulSpin from "../index"

describe("DelightfulSpin", () => {
	it("should render with children", () => {
		render(
			<DelightfulThemeProvider theme="light">
				<DelightfulSpin>
					<div>Content area</div>
				</DelightfulSpin>
			</DelightfulThemeProvider>,
		)

		// Verify children render
		expect(screen.getByText("Content area")).toBeInTheDocument()
	})

	// Snapshot test
	describe("Snapshot tests", () => {
		it("basic loader snapshot", () => {
			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSpin>
						<div>Content area</div>
					</DelightfulSpin>
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("loading state snapshot", () => {
			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSpin spinning>
						<div>Content area</div>
					</DelightfulSpin>
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("with tip text snapshot", () => {
			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSpin tip="Loading...">
						<div>Content area</div>
					</DelightfulSpin>
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("small size loader snapshot", () => {
			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSpin size="small">
						<div>Content area</div>
					</DelightfulSpin>
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("large size loader snapshot", () => {
			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSpin size="large">
						<div>Content area</div>
					</DelightfulSpin>
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("delayed loading snapshot", () => {
			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSpin delay={500}>
						<div>Content area</div>
					</DelightfulSpin>
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("standalone loader snapshot", () => {
			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSpin />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
