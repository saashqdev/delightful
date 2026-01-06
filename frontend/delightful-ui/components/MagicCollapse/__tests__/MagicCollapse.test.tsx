import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { Collapse } from "antd"
import { describe, it, expect } from "vitest"
import DelightfulCollapse from "../index"

const renderWithTheme = (component: React.ReactElement) =>
	render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)

describe("DelightfulCollapse", () => {
	it("应该正常渲染", () => {
		renderWithTheme(
			<DelightfulCollapse>
				<Collapse.Panel key="1" header="标题1">
					内容1
				</Collapse.Panel>
			</DelightfulCollapse>,
		)
		expect(screen.getByText("标题1")).toBeInTheDocument()
	})

	// Snapshot test
	describe("快照测试", () => {
		it("基础折叠面板快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulCollapse>
					<Collapse.Panel key="1" header="标题1">
						内容1
					</Collapse.Panel>
				</DelightfulCollapse>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("多个面板折叠快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulCollapse>
					<Collapse.Panel key="1" header="标题1">
						内容1
					</Collapse.Panel>
					<Collapse.Panel key="2" header="标题2">
						内容2
					</Collapse.Panel>
					<Collapse.Panel key="3" header="标题3">
						内容3
					</Collapse.Panel>
				</DelightfulCollapse>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("默认展开面板快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulCollapse defaultActiveKey={["1"]}>
					<Collapse.Panel key="1" header="标题1">
						内容1
					</Collapse.Panel>
					<Collapse.Panel key="2" header="标题2">
						内容2
					</Collapse.Panel>
				</DelightfulCollapse>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("手风琴模式快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulCollapse accordion>
					<Collapse.Panel key="1" header="标题1">
						内容1
					</Collapse.Panel>
					<Collapse.Panel key="2" header="标题2">
						内容2
					</Collapse.Panel>
				</DelightfulCollapse>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带图标面板快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulCollapse>
					<Collapse.Panel key="1" header="标题1" extra={<span>📝</span>}>
						内容1
					</Collapse.Panel>
				</DelightfulCollapse>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("禁用面板快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulCollapse>
					<Collapse.Panel key="1" header="标题1" disabled>
						内容1
					</Collapse.Panel>
				</DelightfulCollapse>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("小尺寸折叠面板快照", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulCollapse size="small">
					<Collapse.Panel key="1" header="标题1">
						内容1
					</Collapse.Panel>
				</DelightfulCollapse>,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
