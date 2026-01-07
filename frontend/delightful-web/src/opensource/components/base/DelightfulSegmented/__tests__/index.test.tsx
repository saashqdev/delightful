import { render, screen, fireEvent } from "@testing-library/react"
import { describe, it, expect, vi } from "vitest"
import "@testing-library/jest-dom"
import DelightfulSegmented from "../index"
import type { ReactNode } from "react"

// Mock antd Segmented component
vi.mock("antd", () => {
	const Segmented = ({
		className,
		options,
		defaultValue,
		value,
		onChange,
		...props
	}: {
		className?: string
		options?: Array<{ label: ReactNode; value: string | number } | string | number>
		defaultValue?: string | number
		value?: string | number
		onChange?: (value: string | number) => void
	}) => {
		const handleChange = (newValue: string | number) => {
			onChange?.(newValue)
		}

		return (
			<div data-testid="antd-segmented" className={className} {...props}>
				{Array.isArray(options) &&
					options.map((option, index) => {
						const optionValue = typeof option === "object" ? option.value : option
						const optionLabel = typeof option === "object" ? option.label : option
						const isSelected = value
							? value === optionValue
							: defaultValue === optionValue

						return (
							<div
								key={index}
								data-testid={`segmented-item-${index}`}
								className={`segmented-item ${isSelected ? "segmented-item-selected" : ""}`}
								onClick={() => handleChange(optionValue)}
							>
								{optionLabel}
							</div>
						)
					})}
			</div>
		)
	}

	return {
		Segmented,
	}
})

// Mock styles
vi.mock("../styles", () => ({
	default: () => ({
		styles: {
			segmented: "mock-segmented-styles",
		},
	}),
}))

describe("DelightfulSegmented component", () => {
	it("should render basic segmented control", () => {
		render(
			<DelightfulSegmented options={["Daily", "Weekly", "Monthly"]} defaultValue="Daily" />,
		)

		expect(screen.getByTestId("antd-segmented")).toBeInTheDocument()
		expect(screen.getByText("Daily")).toBeInTheDocument()
		expect(screen.getByText("Weekly")).toBeInTheDocument()
		expect(screen.getByText("Monthly")).toBeInTheDocument()
	})

	it("should support complex option format", () => {
		render(
			<DelightfulSegmented
				options=[
					{ label: "Option One", value: "option1" },
					{ label: "Option Two", value: "option2" },
					{ label: "Option Three", value: "option3" },
				]
				defaultValue="option1"
			/>,
		)

		expect(screen.getByTestId("antd-segmented")).toBeInTheDocument()
		expect(screen.getByText("Option One")).toBeInTheDocument()
		expect(screen.getByText("Option Two")).toBeInTheDocument()
		expect(screen.getByText("Option Three")).toBeInTheDocument()
	})

	it("should respond to option click", () => {
		const handleChange = vi.fn()
		render(
			<DelightfulSegmented
				options={["Option One", "Option Two", "Option Three"]}
				defaultValue="Option One"
				onChange={handleChange}
			/>,
		)

		fireEvent.click(screen.getByText("Option Two"))
		expect(handleChange).toHaveBeenCalledWith("Option Two")
	})

	it("should apply circle style class", () => {
		render(<DelightfulSegmented options={["Option One", "Option Two"]} circle={true} />)

		expect(screen.getByTestId("antd-segmented")).toHaveClass("mock-segmented-styles")
	})

	it("should apply non-circle style class", () => {
		render(<DelightfulSegmented options={["Option One", "Option Two"]} circle={false} />)

		expect(screen.getByTestId("antd-segmented")).toHaveClass("mock-segmented-styles")
	})

	it("should support custom className", () => {
		render(<DelightfulSegmented options={["Option One", "Option Two"]} className="custom-class" />)

		const segmentedElement = screen.getByTestId("antd-segmented")
		expect(segmentedElement.className).toContain("mock-segmented-styles")
		expect(segmentedElement.className).toContain("custom-class")
	})
})
