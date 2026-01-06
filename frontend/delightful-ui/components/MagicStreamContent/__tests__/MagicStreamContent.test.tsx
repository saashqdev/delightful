import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulStreamContent from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulStreamContent", () => {
	it("应该正常渲染", () => {
		renderWithTheme(<DelightfulStreamContent content="流式内容" />)
		expect(screen.getByText("流式内容")).toBeInTheDocument()
	})

	// Snapshot test
	describe("快照测试", () => {
		it("基础流式内容快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulStreamContent content="流式内容" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("长文本流式内容快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulStreamContent content="这是一个很长的流式内容，用于测试组件的显示效果" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带自定义样式快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulStreamContent
					content="流式内容"
					style={{ fontSize: "16px", color: "blue" }}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带类名快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulStreamContent content="流式内容" className="custom-stream" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("空内容快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulStreamContent content="" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带HTML标签内容快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulStreamContent content="<strong>粗体文本</strong>" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
