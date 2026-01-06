import { render } from "@testing-library/react"
import DelightfulIcon from "../index"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulIcon", () => {
	it("应该正常渲染", () => {
		renderWithTheme(<DelightfulIcon name="user" />)
		expect(true).toBe(true)
	})

	// Snapshot test
	describe("快照测试", () => {
		it("基础图标快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulIcon name="user" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("不同图标快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulIcon name="home" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带自定义样式图标快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulIcon name="user" style={{ color: "red", fontSize: "20px" }} />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带类名图标快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulIcon name="user" className="custom-icon" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("大尺寸图标快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulIcon name="user" size="large" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("小尺寸图标快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulIcon name="user" size="small" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带点击事件图标快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulIcon name="user" onClick={() => {}} />)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
