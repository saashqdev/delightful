import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulMenu from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulMenu", () => {
	it("should render normally", () => {
		renderWithTheme(
			<DelightfulMenu
				items={[
					{
						key: "1",
						label: "Menu Item 1",
					},
				]}
			/>,
		)
		expect(screen.getByText("Menu Item 1")).toBeInTheDocument()
	})

	// Snapshot test
	describe("Snapshot tests", () => {
		it("basic menu snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulMenu
					items={[
						{ key: "1", label: "Menu Item 1" },
						{ key: "2", label: "Menu Item 2" },
					]}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("menu with icon snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulMenu
					items={[
						{ key: "1", label: "Menu Item 1", icon: <span>🏠</span> },
						{ key: "2", label: "Menu Item 2", icon: <span>⚙️</span> },
					]}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("menu with submenu snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulMenu
					items={[
						{
							key: "1",
							label: "Parent Menu",
							children: [
								{ key: "1-1", label: "Submenu 1" },
								{ key: "1-2", label: "Submenu 2" },
							],
						},
					]}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("menu with selected state snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulMenu
					selectedKeys={["1"]}
					items={[
						{ key: "1", label: "Menu Item 1" },
						{ key: "2", label: "Menu Item 2" },
					]}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("vertical menu snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulMenu
					mode="vertical"
					items={[
						{ key: "1", label: "Menu Item 1" },
						{ key: "2", label: "Menu Item 2" },
					]}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("menu with custom style snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulMenu
					style={{ width: "200px" }}
					items={[
						{ key: "1", label: "Menu Item 1" },
						{ key: "2", label: "Menu Item 2" },
					]}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("menu with custom class snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulMenu
					className="custom-menu"
					items={[
						{ key: "1", label: "Menu Item 1" },
						{ key: "2", label: "Menu Item 2" },
					]}
				/>,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
