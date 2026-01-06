import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulTag from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulTag", () => {
	it("应该正常渲染", () => {
		renderWithTheme(<DelightfulTag>标签内容</DelightfulTag>)
		expect(screen.getByText("标签内容")).toBeInTheDocument()
	})

	// Snapshot test
	describe("快照测试", () => {
		it("默认标签快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulTag>默认标签</DelightfulTag>)
			expect(asFragment()).toMatchSnapshot()
		})

		it("不同颜色标签快照", () => {
			const { asFragment: blueFragment } = renderWithTheme(
				<DelightfulTag color="blue">蓝色标签</DelightfulTag>,
			)
			expect(blueFragment()).toMatchSnapshot()

			const { asFragment: redFragment } = renderWithTheme(
				<DelightfulTag color="red">红色标签</DelightfulTag>,
			)
			expect(redFragment()).toMatchSnapshot()

			const { asFragment: greenFragment } = renderWithTheme(
				<DelightfulTag color="green">绿色标签</DelightfulTag>,
			)
			expect(greenFragment()).toMatchSnapshot()
		})

		it("可关闭标签快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulTag closable>可关闭标签</DelightfulTag>)
			expect(asFragment()).toMatchSnapshot()
		})

		it("不同样式标签快照", () => {
			const { asFragment: successFragment } = renderWithTheme(
				<DelightfulTag color="success">成功标签</DelightfulTag>,
			)
			expect(successFragment()).toMatchSnapshot()

			const { asFragment: warningFragment } = renderWithTheme(
				<DelightfulTag color="warning">警告标签</DelightfulTag>,
			)
			expect(warningFragment()).toMatchSnapshot()
		})

		it("边框样式标签快照", () => {
			const { asFragment } = renderWithTheme(<DelightfulTag bordered>边框标签</DelightfulTag>)
			expect(asFragment()).toMatchSnapshot()
		})

		it("图标标签快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulTag icon={<span>🏷️</span>}>带图标标签</DelightfulTag>,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
