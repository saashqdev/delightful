import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulSearch from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulSearch", () => {
	it("应该正常渲染", () => {
		renderWithTheme(<DelightfulSearch placeholder="搜索..." />)
		expect(screen.getByPlaceholderText("搜索...")).toBeInTheDocument()
	})

	// Snapshot test
	describe("快照测试", () => {
		it("基础搜索框快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulSearch placeholder="搜索..." />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带默认值搜索框快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulSearch defaultValue="默认搜索词" />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带前缀图标搜索框快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulSearch placeholder="搜索..." prefix={<span>🔍</span>} />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带后缀图标搜索框快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulSearch placeholder="搜索..." suffix={<span>📝</span>} />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("禁用状态搜索框快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulSearch placeholder="搜索..." disabled />)
			expect(asFragment()).toMatchSnapshot()
		})

		it("大尺寸搜索框快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulSearch placeholder="搜索..." size="large" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("小尺寸搜索框快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulSearch placeholder="搜索..." size="small" />,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带边框搜索框快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulSearch placeholder="搜索..." bordered />)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
