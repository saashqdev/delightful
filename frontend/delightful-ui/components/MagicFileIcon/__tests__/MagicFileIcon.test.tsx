import { render } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulFileIcon from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulFileIcon", () => {
	it("应该正常渲染", () => {
		renderWithTheme(<DelightfulFileIcon type="pdf" />)
		expect(true).toBe(true)
	})

	// Snapshot test
	describe("快照测试", () => {
		it("PDF文件图标快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulFileIcon type="pdf" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Word文件图标快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulFileIcon type="word" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Excel文件图标快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulFileIcon type="excel" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("图片文件图标快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulFileIcon type="image" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("视频文件图标快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulFileIcon type="video" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带自定义样式文件图标快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulFileIcon type="pdf" style={{ width: "32px", height: "32px" }} />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带类名文件图标快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulFileIcon type="pdf" className="custom-file-icon" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
