import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulImage from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulImage", () => {
	it("应该正常渲染", () => {
		renderWithTheme(<DelightfulImage src="test.jpg" alt="测试图片" />)
		expect(screen.getByAltText("测试图片")).toBeInTheDocument()
	})

	// Snapshot test
	describe("快照测试", () => {
		it("基础图片快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulImage src="test.jpg" alt="测试图片" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带类名图片快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulImage src="test.jpg" alt="测试图片" className="custom-image" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带点击事件图片快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulImage src="test.jpg" alt="测试图片" onClick={() => {}} />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("自定义宽度图片快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulImage src="test.jpg" alt="测试图片" width={200} />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("自定义高度图片快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulImage src="test.jpg" alt="测试图片" height={150} />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带样式图片快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulImage src="test.jpg" alt="测试图片" style={{ borderRadius: "8px" }} />,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
