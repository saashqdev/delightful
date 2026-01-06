import { render, screen } from "@testing-library/react"
import DelightfulThemeProvider from "../../ThemeProvider"
import { describe, it, expect } from "vitest"
import DelightfulSelect from "../index"

describe("DelightfulSelect", () => {
	it("should render without crashing", () => {
		const options = [
			{ label: "Option 1", value: "1" },
			{ label: "Option 2", value: "2" },
			{ label: "Option 3", value: "3" },
		]

		render(
			<DelightfulThemeProvider theme="light">
				<DelightfulSelect options={options} placeholder="Please Select" />
			</DelightfulThemeProvider>,
		)

		// Verify placeholder renders
		expect(screen.getByText("Please Select")).toBeInTheDocument()
	})

	it("should display selected value", () => {
		const options = [
			{ label: "Option 1", value: "1" },
			{ label: "Option 2", value: "2" },
		]

		render(
			<DelightfulThemeProvider theme="light">
				<DelightfulSelect options={options} value="1" />
			</DelightfulThemeProvider>,
		)

		// Verify selected value displays
		expect(screen.getByText("Option 1")).toBeInTheDocument()
	})

	// Snapshot test
	describe("Snapshot Test", () => {
		it("Basic Selector Snapshot", () => {
			const options = [
				{ label: "Option 1", value: "1" },
				{ label: "Option 2", value: "2" },
				{ label: "Option 3", value: "3" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSelect options={options} placeholder="Please Select" />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Selector Snapshot with Default Value", () => {
			const options = [
				{ label: "Option 1", value: "1" },
				{ label: "Option 2", value: "2" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSelect options={options} defaultValue="1" />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Disabled Selector Snapshot", () => {
			const options = [
				{ label: "Option 1", value: "1" },
				{ label: "Option 2", value: "2" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSelect options={options} disabled />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Multi-select Selector Snapshot", () => {
			const options = [
				{ label: "Option 1", value: "1" },
				{ label: "Option 2", value: "2" },
				{ label: "Option 3", value: "3" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSelect options={options} mode="multiple" />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Large Size Selector Snapshot", () => {
			const options = [
				{ label: "Option 1", value: "1" },
				{ label: "Option 2", value: "2" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSelect options={options} size="large" />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Small Size Selector Snapshot", () => {
			const options = [
				{ label: "Option 1", value: "1" },
				{ label: "Option 2", value: "2" },
			]

			const { asFragment } = render(
				<DelightfulThemeProvider theme="light">
					<DelightfulSelect options={options} size="small" />
				</DelightfulThemeProvider>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Selector Snapshot with Clear Button", () => {
			const options = [
				{ label: "Option 1", value: "1" },
				{ label: "Option 2", value: "2" },
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
