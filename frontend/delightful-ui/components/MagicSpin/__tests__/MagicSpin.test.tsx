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

		it("加载状态快照", () => {
			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSpin spinning>
						<div>内容区域</div>
					</DelightfulSpin>
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带提示文字快照", () => {
			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSpin tip="加载中...">
						<div>内容区域</div>
					</DelightfulSpin>
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("小尺寸加载器快照", () => {
			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSpin size="small">
						<div>内容区域</div>
					</DelightfulSpin>
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("大尺寸加载器快照", () => {
			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSpin size="large">
						<div>内容区域</div>
					</DelightfulSpin>
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("延迟加载快照", () => {
			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSpin delay={500}>
						<div>内容区域</div>
					</DelightfulSpin>
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("独立加载器快照", () => {
			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSpin />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
