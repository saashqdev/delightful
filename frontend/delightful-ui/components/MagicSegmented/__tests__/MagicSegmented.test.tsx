import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect, vi } from "vitest"
import DelightfulSegmented from "../index"

describe("DelightfulSegmented", () => {
	it("should render without crashing", () => {
		const options = [
			{ label: "选项1", value: "1" },
			{ label: "选项2", value: "2" },
			{ label: "选项3", value: "3" },
		]

		render(
			<DelightfulThemeProvider theme="light">
				<DelightfulSegmented options={options} />
			</DelightfulThemeProvider>,
		)

		// Verify options render
		expect(screen.getByText("选项1")).toBeInTheDocument()
		expect(screen.getByText("选项2")).toBeInTheDocument()
		expect(screen.getByText("选项3")).toBeInTheDocument()
	})

	it("should handle value change", () => {
		const options = [
			{ label: "选项1", value: "1" },
			{ label: "选项2", value: "2" },
		]
		const onChange = vi.fn()

		render(
			<DelightfulThemeProvider theme="light">
				<DelightfulSegmented options={options} onChange={onChange} />
			</DelightfulThemeProvider>,
		)

		const option2 = screen.getByText("选项2")
		option2.click()

		expect(onChange).toHaveBeenCalledWith("2")
	})

	// Snapshot test
	describe("快照测试", () => {
		it("基础分段控制器快照", () => {
			const options = [
				{ label: "选项1", value: "1" },
				{ label: "选项2", value: "2" },
				{ label: "选项3", value: "3" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSegmented options={options} />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带默认值分段控制器快照", () => {
			const options = [
				{ label: "选项1", value: "1" },
				{ label: "选项2", value: "2" },
				{ label: "选项3", value: "3" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSegmented options={options} defaultValue="2" />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("禁用状态分段控制器快照", () => {
			const options = [
				{ label: "选项1", value: "1" },
				{ label: "选项2", value: "2" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSegmented options={options} disabled />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("大尺寸分段控制器快照", () => {
			const options = [
				{ label: "选项1", value: "1" },
				{ label: "选项2", value: "2" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSegmented options={options} size="large" />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("小尺寸分段控制器快照", () => {
			const options = [
				{ label: "选项1", value: "1" },
				{ label: "选项2", value: "2" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSegmented options={options} size="small" />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("带图标分段控制器快照", () => {
			const options = [
				{ label: "选项1", value: "1", icon: <span>🚀</span> },
				{ label: "选项2", value: "2", icon: <span>⭐</span> },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSegmented options={options} />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("块级分段控制器快照", () => {
			const options = [
				{ label: "选项1", value: "1" },
				{ label: "选项2", value: "2" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSegmented options={options} block />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
