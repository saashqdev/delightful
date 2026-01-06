import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulMenu from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulMenu", () => {
	it("应该正常渲染", () => {
		renderWithTheme(
			<DelightfulMenu
				items={[
					{
						key: "1",
						label: "菜单项1",
					},
				]}
			/>,
		)
		expect(screen.getByText("菜单项1")).toBeInTheDocument()
	})

	// Snapshot test
	describe("快照测试", () => {
		it("基础菜单快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulMenu
					items={[
						{ key: "1", label: "菜单项1" },
						{ key: "2", label: "菜单项2" },
					]}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带图标菜单快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulMenu
					items={[
						{ key: "1", label: "菜单项1", icon: <span>🏠</span> },
						{ key: "2", label: "菜单项2", icon: <span>⚙️</span> },
					]}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带子菜单快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulMenu
					items={[
						{
							key: "1",
							label: "父菜单",
							children: [
								{ key: "1-1", label: "子菜单1" },
								{ key: "1-2", label: "子菜单2" },
							],
						},
					]}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("选中状态菜单快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulMenu
					selectedKeys={["1"]}
					items={[
						{ key: "1", label: "菜单项1" },
						{ key: "2", label: "菜单项2" },
					]}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("垂直菜单快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulMenu
					mode="vertical"
					items={[
						{ key: "1", label: "菜单项1" },
						{ key: "2", label: "菜单项2" },
					]}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带自定义样式菜单快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulMenu
					style={{ width: "200px" }}
					items={[
						{ key: "1", label: "菜单项1" },
						{ key: "2", label: "菜单项2" },
					]}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带类名菜单快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulMenu
					className="custom-menu"
					items={[
						{ key: "1", label: "菜单项1" },
						{ key: "2", label: "菜单项2" },
					]}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
