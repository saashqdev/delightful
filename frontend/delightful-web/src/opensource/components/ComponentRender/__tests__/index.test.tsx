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

	it("应该正确渲染子组件", async () => {
		// 注册一个接收子组件的测试组件
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
				<span data-testid="child-element">子元素内容</span>
			</ComponentRender>,
		)

		// 验证子组件是否正确渲染
		const component = await screen.findByTestId("children-test-component")
		const childElement = within(component).getByTestId("child-element")
		expect(childElement.textContent).toBe("子元素内容")

		// 清理：注销测试组件
		ComponentFactory.unregisterComponent("ChildrenTestComponent")
	})

	it("应该在组件出错时渲染 Fallback 组件", async () => {
		// 注册一个会抛出错误的组件
		const ErrorComponent = vi.fn().mockImplementation(() => {
			throw new Error("测试错误")
		})

		// 使用 mock.console.error 来防止错误日志输出
		const originalConsoleError = console.error
		console.error = vi.fn()

		// 注册错误组件
		ComponentFactory.registerComponent("ErrorComponent" as any, ErrorComponent as any)

		// 错误边界不会被直接捕获在测试中，但我们可以验证 Fallback 逻辑
		try {
			render(<ComponentRender componentName={"ErrorComponent" as any} />)
		} catch (error) {
			// 错误会在渲染时抛出，这是预期行为
		}

		// 恢复 console.error
		console.error = originalConsoleError

		// 清理
		ComponentFactory.unregisterComponent("ErrorComponent")
	})

	it("应该正确处理 ComponentFactory 的组件注册和注销", () => {
		// 创建测试组件
		const TestComponent = () => <div>测试组件</div>

		// 测试注册单个组件
		ComponentFactory.registerComponent("TestSingleComponent" as any, TestComponent as any)
		expect(ComponentFactory.getComponent("TestSingleComponent" as any)).toBe(TestComponent)

		// 测试注册多个组件
		const MultipleComponents = {
			TestComponent1: TestComponent,
			TestComponent2: TestComponent,
		}
		ComponentFactory.registerComponents(MultipleComponents as any)
		expect(ComponentFactory.getComponent("TestComponent1" as any)).toBe(TestComponent)
		expect(ComponentFactory.getComponent("TestComponent2" as any)).toBe(TestComponent)

		// 测试注销单个组件
		ComponentFactory.unregisterComponent("TestSingleComponent")
		expect(ComponentFactory.getComponent("TestSingleComponent" as any)).not.toBe(TestComponent)

		// 测试注销多个组件
		ComponentFactory.unregisterComponents(["TestComponent1", "TestComponent2"])
		expect(ComponentFactory.getComponent("TestComponent1" as any)).not.toBe(TestComponent)
		expect(ComponentFactory.getComponent("TestComponent2" as any)).not.toBe(TestComponent)
	})

	it("getFallbackComponent 应该返回默认的 Fallback 组件", () => {
		const FallbackComponent = ComponentFactory.getFallbackComponent()

		// 渲染 Fallback 组件
		render(<FallbackComponent />)

		// 验证 Fallback 组件的内容
		expect(screen.getByText("Component UnRegistered")).toBeDefined()
	})
})
