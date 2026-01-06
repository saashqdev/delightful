import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulSwitch from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulSwitch", () => {
	it("应该正常渲染", () => {
		renderWithTheme(<DelightfulSwitch />)
		expect(screen.getByRole("switch")).toBeInTheDocument()
	})

	// Snapshot test
	describe("快照测试", () => {
		it("默认开关快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulSwitch />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("开启状态开关快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulSwitch defaultChecked />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("禁用状态开关快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulSwitch disabled />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("禁用且开启状态开关快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulSwitch disabled defaultChecked />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("小尺寸开关快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulSwitch size="small" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带标签开关快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulSwitch checkedChildren="开" unCheckedChildren="关" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
