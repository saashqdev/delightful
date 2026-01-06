import { render, screen, fireEvent } from "@testing-library/react"
import { describe, it, expect, vi } from "vitest"
import DelightfulButton from "../index"
import DelightfulThemeProvider from "../../ThemeProvider"

const renderWithTheme = (component: React.ReactElement) => {
	return render(<DelightfulThemeProvider theme="light">{component}</DelightfulThemeProvider>)
}

describe("DelightfulButton", () => {
	it("Should render button correctly", () => {
		renderWithTheme(<DelightfulButton>Test Button</DelightfulButton>)
		expect(screen.getByRole("button", { name: "Test Button" })).toBeInTheDocument()
	})

	it("Should support different button types", () => {
		const { rerender } = renderWithTheme(<DelightfulButton type="primary">Primary Button</DelightfulButton>)
		expect(screen.getByRole("button")).toHaveClass("delightful-btn-primary")

		rerender(
			<DelightfulThemeProvider theme="light">
			<DelightfulButton type="dashed">Dashed Button</DelightfulButton>
			</DelightfulThemeProvider>,
		)
		expect(screen.getByRole("button")).toHaveClass("delightful-btn-dashed")
	})

	it("Should support click events", () => {
		const handleClick = vi.fn()
		renderWithTheme(<DelightfulButton onClick={handleClick}>Click Button</DelightfulButton>)

		fireEvent.click(screen.getByRole("button"))
		expect(handleClick).toHaveBeenCalledTimes(1)
	})

	it("Should support disabled state", () => {
		renderWithTheme(<DelightfulButton disabled>Disabled Button</DelightfulButton>)
		expect(screen.getByRole("button")).toBeDisabled()
	})

	it("Should support loading state", () => {
		renderWithTheme(<DelightfulButton loading>Loading Button</DelightfulButton>)
		expect(screen.getByRole("button")).toHaveClass("delightful-btn-loading")
	})

	it("Should support custom class name", () => {
		renderWithTheme(<DelightfulButton className="custom-class">Custom Button</DelightfulButton>)
		expect(screen.getByRole("button")).toHaveClass("custom-class")
	})

	it("Should support icon", () => {
		renderWithTheme(
			<DelightfulButton icon={<span data-testid="icon">🚀</span>}>Button with Icon</DelightfulButton>,
		)
		expect(screen.getByTestId("icon")).toBeInTheDocument()
	})

	it("Should support tooltip", () => {
		renderWithTheme(<DelightfulButton tip="This is a tooltip">Button with Tooltip</DelightfulButton>)
		expect(screen.getByRole("button")).toBeInTheDocument()
	})

	it("Should not render when hidden is true", () => {
		const { container } = renderWithTheme(<DelightfulButton hidden>Hidden Button</DelightfulButton>)
		expect(container.firstChild).toBeNull()
	})

	it("Should support different justify property", () => {
		renderWithTheme(<DelightfulButton justify="flex-start">Left-aligned Button</DelightfulButton>)
		const button = screen.getByRole("button")
		expect(button).toBeInTheDocument()
	})

	it("Should support ref forwarding", () => {
		const ref = vi.fn()
		renderWithTheme(<DelightfulButton ref={ref}>Ref Button</DelightfulButton>)
		expect(ref).toHaveBeenCalled()
	})

	// Snapshot test
	describe("Snapshot test", () => {
		it("Default button snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulButton>Default Button</DelightfulButton>)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Primary button snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulButton type="primary">Primary Button</DelightfulButton>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Dashed button snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulButton type="dashed">Dashed Button</DelightfulButton>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Text button snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulButton type="text">Text Button</DelightfulButton>)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Link button snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulButton type="link">Link Button</DelightfulButton>)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Disabled button snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulButton disabled>Disabled Button</DelightfulButton>)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Loading button snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulButton loading>Loading Button</DelightfulButton>)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Button with icon snapshot", () => {
			const { asFragment } = renderWithTheme(
				<DelightfulButton icon={<span>🚀</span>}>Button with Icon</DelightfulButton>,
			)
			expect(asFragment()).toMatchSnapshot()
		})

		it("Different button size snapshot", () => {
			const { asFragment: smallFragment } = renderWithTheme(
				<DelightfulButton size="small">Small Button</DelightfulButton>,
			)
			expect(smallFragment()).toMatchSnapshot()

			const { asFragment: largeFragment } = renderWithTheme(
				<DelightfulButton size="large">Large Button</DelightfulButton>,
			)
			expect(largeFragment()).toMatchSnapshot()
		})

		it("Hidden button snapshot", () => {
			const { asFragment } = renderWithTheme(<DelightfulButton hidden>Hidden Button</DelightfulButton>)
			expect(asFragment()).toMatchSnapshot()
		})
	})
})
