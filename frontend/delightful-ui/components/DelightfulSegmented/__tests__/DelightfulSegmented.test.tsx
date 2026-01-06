import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect, vi } from "vitest"
import DelightfulSegmented from "../index"

describe("DelightfulSegmented", () => {
	it("should render without crashing", () => {
		const options = [
			{ label: "Option 1", value: "1" },
			{ label: "Option 2", value: "2" },
			{ label: "Option 3", value: "3" },
		]

		render(
			<DelightfulThemeProvider theme="light">
				<DelightfulSegmented options={options} />
			</DelightfulThemeProvider>,
		)

		// Verify options render
		expect(screen.getByText("Option 1")).toBeInTheDocument()
		expect(screen.getByText("Option 2")).toBeInTheDocument()
		expect(screen.getByText("Option 3")).toBeInTheDocument()
	})

	it("should handle value change", () => {
		const options = [
			{ label: "Option 1", value: "1" },
			{ label: "Option 2", value: "2" },
		]
		const onChange = vi.fn()

		render(
			<DelightfulThemeProvider theme="light">
				<DelightfulSegmented options={options} onChange={onChange} />
			</DelightfulThemeProvider>,
		)

		const option2 = screen.getByText("Option 2")
		option2.click()

		expect(onChange).toHaveBeenCalledWith("2")
	})

	// Snapshot test
	describe("Snapshot tests", () => {
		it("basic segmented control snapshot", () => {
			const options = [
				{ label: "Option 1", value: "1" },
				{ label: "Option 2", value: "2" },
				{ label: "Option 3", value: "3" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSegmented options={options} />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("segmented control with default value snapshot", () => {
			const options = [
				{ label: "Option 1", value: "1" },
				{ label: "Option 2", value: "2" },
				{ label: "Option 3", value: "3" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSegmented options={options} defaultValue="2" />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("disabled state segmented control snapshot", () => {
			const options = [
				{ label: "Option 1", value: "1" },
				{ label: "Option 2", value: "2" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSegmented options={options} disabled />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("large size segmented control snapshot", () => {
			const options = [
				{ label: "Option 1", value: "1" },
				{ label: "Option 2", value: "2" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSegmented options={options} size="large" />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("small size segmented control snapshot", () => {
			const options = [
				{ label: "Option 1", value: "1" },
				{ label: "Option 2", value: "2" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSegmented options={options} size="small" />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("segmented control with icon snapshot", () => {
			const options = [
				{ label: "Option 1", value: "1", icon: <span>🚀</span> },
				{ label: "Option 2", value: "2", icon: <span>⭐</span> },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSegmented options={options} />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("block level segmented control snapshot", () => {
			const options = [
				{ label: "Option 1", value: "1" },
				{ label: "Option 2", value: "2" },
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
