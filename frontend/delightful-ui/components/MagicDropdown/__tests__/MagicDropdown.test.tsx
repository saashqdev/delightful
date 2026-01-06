import { render } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulDropdown from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulDropdown", () => {
	it("应该正常渲染", () => {
		renderWithTheme(<DelightfulDropdown menu={{ items: [] }}>下拉</DelightfulDropdown>)
		// Pass if no errors are thrown
		expect(true).toBe(true)
	})

	// Snapshot test
	describe("快照测试", () => {
		it("基础下拉菜单快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulDropdown menu={{ items: [] }}>下拉菜单</DelightfulDropdown>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带菜单项下拉菜单快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulDropdown
					menu={{
						items: [
							{ key: "1", label: "菜单项1" },
							{ key: "2", label: "菜单项2" },
						],
					}}
				>
					下拉菜单
				</DelightfulDropdown>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带图标下拉菜单快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulDropdown
					menu={{
						items: [
							{ key: "1", label: "菜单项1", icon: <span>🏠</span> },
							{ key: "2", label: "菜单项2", icon: <span>⚙️</span> },
						],
					}}
				>
					<div>🏠 下拉菜单</div>
				</DelightfulDropdown>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("禁用状态下拉菜单快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulDropdown disabled menu={{ items: [] }}>
					下拉菜单
				</DelightfulDropdown>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带自定义属性下拉菜单快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulDropdown data-testid="custom-dropdown" menu={{ items: [] }}>
					下拉菜单
				</DelightfulDropdown>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带类名下拉菜单快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulDropdown className="custom-dropdown" menu={{ items: [] }}>
					下拉菜单
				</DelightfulDropdown>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带按钮样式下拉菜单快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulDropdown menu={{ items: [] }}>
					<button>按钮下拉</button>
				</DelightfulDropdown>,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
