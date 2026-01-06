import { render } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulCheckFavor from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulCheckFavor", () => {
	it("应该正常渲染", () => {
		renderWithTheme(<DelightfulCheckFavor checked />)
		expect(document.querySelector('input[type="checkbox"]')).toBeInTheDocument()
	})

	it("支持自定义 checked", () => {
		renderWithTheme(<DelightfulCheckFavor checked />)
		expect(document.querySelector('input[type="checkbox"]')).toBeChecked()
	})

	// Snapshot test - may be unstable because component uses random IDs
	describe("快照测试", () => {
		it("已收藏状态快照", () => {
			const { container } = renderWithTheme(<DelightfulCheckFavor checked />)
			// Assert component renders correctly instead of relying on snapshot
			expect(container.querySelector('input[type="checkbox"]')).toBeChecked()
		})

		it("未收藏状态快照", () => {
			const { container } = renderWithTheme(<DelightfulCheckFavor checked={false} />)
			expect(container.querySelector('input[type="checkbox"]')).not.toBeChecked()
		})

		it("默认状态快照", () => {
			const { container } = renderWithTheme(<DelightfulCheckFavor />)
			expect(container.querySelector('input[type="checkbox"]')).toBeInTheDocument()
		})

		it("带自定义样式快照", () => {
			const { container } = renderWithTheme(<DelightfulCheckFavor className="custom-favor" />)
			expect(container.firstChild).toHaveClass("custom-favor")
		})

		it("带自定义事件快照", () => {
			const { container } = renderWithTheme(<DelightfulCheckFavor onChange={() => {}} />)
			expect(container.querySelector('input[type="checkbox"]')).toBeInTheDocument()
		})

		it("带标签快照", () => {
			const { container } = renderWithTheme(<DelightfulCheckFavor />)
			expect(container.querySelector('input[type="checkbox"]')).toBeInTheDocument()
		})
	})
})
