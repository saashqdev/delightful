import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulSelect from "../index"

describe("DelightfulSelect", () => {
	it("should render without crashing", () => {
		const options = [
			{ label: "选项1", value: "1" },
			{ label: "选项2", value: "2" },
			{ label: "选项3", value: "3" },
		]

		render(
			<DelightfulThemeProvider theme="light">
				<DelightfulSelect options={options} placeholder="请选择" />
			</DelightfulThemeProvider>,
		)

		// Verify placeholder renders
		expect(screen.getByText("请选择")).toBeInTheDocument()
	})

	it("should display selected value", () => {
		const options = [
			{ label: "选项1", value: "1" },
			{ label: "选项2", value: "2" },
		]

		render(
			<DelightfulThemeProvider theme="light">
				<DelightfulSelect options={options} value="1" />
			</DelightfulThemeProvider>,
		)

		// Verify selected value displays
		expect(screen.getByText("选项1")).toBeInTheDocument()
	})

	// Snapshot test
	describe("快照测试", () => {
		it("基础选择器快照", () => {
			const options = [
				{ label: "选项1", value: "1" },
				{ label: "选项2", value: "2" },
				{ label: "选项3", value: "3" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSelect options={options} placeholder="请选择" />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带默认值选择器快照", () => {
			const options = [
				{ label: "选项1", value: "1" },
				{ label: "选项2", value: "2" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSelect options={options} defaultValue="1" />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("禁用状态选择器快照", () => {
			const options = [
				{ label: "选项1", value: "1" },
				{ label: "选项2", value: "2" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSelect options={options} disabled />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("多选选择器快照", () => {
			const options = [
				{ label: "选项1", value: "1" },
				{ label: "选项2", value: "2" },
				{ label: "选项3", value: "3" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSelect options={options} mode="multiple" />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("大尺寸选择器快照", () => {
			const options = [
				{ label: "选项1", value: "1" },
				{ label: "选项2", value: "2" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSelect options={options} size="large" />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("小尺寸选择器快照", () => {
			const options = [
				{ label: "选项1", value: "1" },
				{ label: "选项2", value: "2" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSelect options={options} size="small" />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带清除按钮选择器快照", () => {
			const options = [
				{ label: "选项1", value: "1" },
				{ label: "选项2", value: "2" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSelect options={options} allowClear />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
