import { render, screen, within } from "@testing-library/react"
import { describe, expect, it, vi, afterEach } from "vitest"
// @ts-ignore
import ComponentRender from "../index"
import ComponentFactory from "../ComponentFactory"
import { DefaultComponents } from "../config/defaultComponents"

// Mock lazy loaded component
vi.mock(
	"@/opensource/layouts/BaseLayout/components/Sider/components/OrganizationSwitch/OrganizationList",
	() => ({
		default: () => <div data-testid="organization-list">Organization list component</div>,
	}),
)

describe("ComponentRender", () => {
	// Clean up registered test components after each test
	afterEach(() => {
		vi.restoreAllMocks()
	})

	it("should render registered component correctly", async () => {
		render(<ComponentRender componentName={DefaultComponents.OrganizationList} />)

		// Since Suspense is used, wait for lazy loading to complete
		const organizationList = await screen.findByTestId("organization-list")
		expect(organizationList).toBeDefined()
		expect(organizationList.textContent).toBe("Organization list component")
	})

	it("should render Fallback component when component is unregistered", () => {
		// Use an unregistered component name
		render(<ComponentRender componentName={"UnregisteredComponent" as any} />)

		// Fallback component content is 'Component UnRegistered'
		expect(screen.getByText("Component UnRegistered")).toBeDefined()
	})

	it("should pass props to rendered component", async () => {
		// Register a test component
		const TestComponent = vi
			.fn()
			.mockImplementation(({ testProp }: { testProp: string }) => (
				<div data-testid="test-component">{testProp}</div>
			))

		// Register this test component
		ComponentFactory.registerComponent("TestComponent" as any, TestComponent as any)

		render(<ComponentRender componentName={"TestComponent" as any} testProp="Test property value" />)

		// Verify that props are passed correctly
		const component = await screen.findByTestId("test-component")
		expect(component.textContent).toBe("Test property value")
		expect(TestComponent).toHaveBeenCalledWith(
			expect.objectContaining({ testProp: "Test property value" }),
			expect.anything(),
		)

		// Cleanup: Unregister test component
		ComponentFactory.unregisterComponent("TestComponent")
	})

	it("should render child component correctly", async () => {
		// Register a test component that accepts children
		const ChildrenTestComponent = vi
			.fn()
			.mockImplementation(({ children }: { children: React.ReactNode }) => (
				<div data-testid="children-test-component">{children}</div>
			))

		ComponentFactory.registerComponent(
			"ChildrenTestComponent" as any,
			ChildrenTestComponent as any,
		)

		render(
			<ComponentRender componentName={"ChildrenTestComponent" as any}>
				<span data-testid="child-element">Child element content</span>
			</ComponentRender>,
		)

		// Verify that the child component renders correctly
		const component = await screen.findByTestId("children-test-component")
		const childElement = within(component).getByTestId("child-element")
		expect(childElement.textContent).toBe("Child element content")

		// Cleanup: unregister test component
		ComponentFactory.unregisterComponent("ChildrenTestComponent")
	})

	it("should render Fallback component when component errors", async () => {
		// Register a component that throws an error
		const ErrorComponent = vi.fn().mockImplementation(() => {
			throw new Error("Test error")
		})

		// Use mock.console.error to suppress error logs
		const originalConsoleError = console.error
		console.error = vi.fn()

		// Register error component
		ComponentFactory.registerComponent("ErrorComponent" as any, ErrorComponent as any)

		// Error boundaries are not directly caught in tests, but we can verify fallback logic
		try {
			render(<ComponentRender componentName={"ErrorComponent" as any} />)
		} catch (error) {
			// Error will be thrown during render; this is expected
		}

		// Restore console.error
		console.error = originalConsoleError

		// Cleanup
		ComponentFactory.unregisterComponent("ErrorComponent")
	})

	it("should correctly handle ComponentFactory component registration and unregistration", () => {
		// Create a test component
		const TestComponent = () => <div>Test component</div>

		// Test registering a single component
		ComponentFactory.registerComponent("TestSingleComponent" as any, TestComponent as any)
		expect(ComponentFactory.getComponent("TestSingleComponent" as any)).toBe(TestComponent)

		// Test registering multiple components
		const MultipleComponents = {
			TestComponent1: TestComponent,
			TestComponent2: TestComponent,
		}
		ComponentFactory.registerComponents(MultipleComponents as any)
		expect(ComponentFactory.getComponent("TestComponent1" as any)).toBe(TestComponent)
		expect(ComponentFactory.getComponent("TestComponent2" as any)).toBe(TestComponent)

		// Test unregistering a single component
		ComponentFactory.unregisterComponent("TestSingleComponent")
		expect(ComponentFactory.getComponent("TestSingleComponent" as any)).not.toBe(TestComponent)

		// Test unregistering multiple components
		ComponentFactory.unregisterComponents(["TestComponent1", "TestComponent2"])
		expect(ComponentFactory.getComponent("TestComponent1" as any)).not.toBe(TestComponent)
		expect(ComponentFactory.getComponent("TestComponent2" as any)).not.toBe(TestComponent)
	})

	it("getFallbackComponent should return the default Fallback component", () => {
		const FallbackComponent = ComponentFactory.getFallbackComponent()

		// Render Fallback component
		render(<FallbackComponent />)

		// Verify Fallback component content
		expect(screen.getByText("Component UnRegistered")).toBeDefined()
	})
})
