import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulPageContainer from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulPageContainer", () => {
	it("应该正常渲染", () => {
		renderWithTheme(<DelightfulPageContainer title="页面标题">页面内容</DelightfulPageContainer>)
		expect(screen.getByText("页面标题")).toBeInTheDocument()
		expect(screen.getByText("页面内容")).toBeInTheDocument()
	})

	// Snapshot test
	describe("快照测试", () => {
		it("基础页面容器快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulPageContainer title="页面标题">页面内容</DelightfulPageContainer>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带类名页面容器快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulPageContainer title="页面标题" className="custom-page">
					页面内容
				</DelightfulPageContainer>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带自定义属性页面容器快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulPageContainer title="页面标题" data-testid="page-container">
					页面内容
				</DelightfulPageContainer>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带操作按钮页面容器快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulPageContainer title="页面标题" extra={<button>操作按钮</button>}>
					页面内容
				</DelightfulPageContainer>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带标签页页面容器快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulPageContainer
					title="页面标题"
					tabList={[
						{ key: "tab1", tab: "标签1" },
						{ key: "tab2", tab: "标签2" },
					]}
				>
					页面内容
				</DelightfulPageContainer>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带自定义样式页面容器快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulPageContainer title="页面标题" style={{ backgroundColor: "#f0f0f0" }}>
					页面内容
				</DelightfulPageContainer>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("无标题页面容器快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulPageContainer>页面内容</DelightfulPageContainer>,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
