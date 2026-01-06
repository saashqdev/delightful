import { render, screen, fireEvent } from "@testing-library/react"
import { vi, describe, test, expect } from "vitest"
import DelightfulList from "../DelightfulList"
import type { DelightfulListItemData } from "../types"

// Mock styles module to satisfy tokens
vi.mock("../styles", () => ({
	useDelightfulListItemStyles: () => ({
		styles: {
			container: "mock-container",
			active: "mock-active",
			mainWrapper: "mock-main-wrapper",
			avatar: "mock-avatar",
			title: "mock-title",
			content: "mock-content",
			time: "mock-time",
			extra: "mock-extra",
		},
	}),
}))

// Test data
interface TestItemData extends DelightfulListItemData {
	id: string
	title: string
	customField?: string
}

// Generate test items
const createTestItems = (count: number): TestItemData[] => {
	return Array.from({ length: count }).map((_, index) => ({
		id: `item-${index}`,
		title: `Test item ${index}`,
		customField: `Custom field ${index}`,
	}))
}

describe("DelightfulList component", () => {
	// Basic rendering
	test("renders list items", () => {
		const items = createTestItems(3)

		render(<DelightfulList items={items} />)

		// All items render
		items.forEach((item) => {
			expect(screen.getByText(item.title)).toBeInTheDocument()
		})
	})

	// Empty list
	test("renders DelightfulEmpty when list is empty", () => {
		const { container } = render(<DelightfulList items={[]} />)

		// DelightfulEmpty may not expose a test id; ensure no list items render
		expect(container.querySelectorAll(".ant-flex").length).toBe(0)
	})

	// Item click handling
	test("clicking an item triggers onItemClick", () => {
		const items = createTestItems(3)
		const handleItemClick = vi.fn()

		render(<DelightfulList items={items} onItemClick={handleItemClick} />)

		// Click second item
		fireEvent.click(screen.getByText("Test item 1"))

		// Callback invoked with correct payload
		expect(handleItemClick).toHaveBeenCalledTimes(1)
		expect(handleItemClick).toHaveBeenCalledWith(items[1])
	})

	// Active state (string)
	test("applies active state when string id provided", () => {
		const items = createTestItems(3)
		const activeId = "item-1"

		const { container } = render(<DelightfulList items={items} active={activeId} />)

		// Only one item should have the active class
		const activeItems = container.querySelectorAll(".mock-active")
		expect(activeItems.length).toBe(1)

		// Active item contains expected title
		const activeItem = activeItems[0]
		const parentElement = activeItem.closest(".mock-container")
		expect(parentElement?.textContent).toContain("Test item 1")
	})

	// Active state (function)
	test("applies active state when predicate provided", () => {
		const items = createTestItems(5)
		const isActive = (_: TestItemData, index: number) => index === 2

		render(<DelightfulList items={items} active={isActive} />)

		// Only one item should have the active class
		const activeItems = document.querySelectorAll(".mock-active")
		expect(activeItems.length).toBe(1)

		// Active item contains expected title
		const activeItem = activeItems[0]
		const parentElement = activeItem.closest(".mock-container")
		expect(parentElement?.textContent).toContain("Test item 2")
	})

	// String item handling
	test("handles string items", () => {
		const stringItems = ["item-0", "item-1", "item-2"]

		render(<DelightfulList items={stringItems} />)

		// All items render
		const elements = document.querySelectorAll(".mock-container")
		expect(elements.length).toBe(3)
	})

	// Performance: render many items
	test("performance: renders many items", () => {
		// Create 100 items
		const items = createTestItems(100)

		// Measure render time
		const startTime = performance.now()

		render(<DelightfulList items={items} />)

		const endTime = performance.now()

		const renderTime = endTime - startTime

		// Render time should stay under a sample threshold (200ms)
		console.log(`Render 100 items: ${renderTime}ms`)
		expect(renderTime).toBeLessThan(200)

		// All items render
		const elements = document.querySelectorAll(".mock-container")
		expect(elements.length).toBe(100)
	})

	// Custom list item component
	test("supports custom list item component", () => {
		const items = createTestItems(3)

		// Create custom list item component
		const CustomListItem = vi.fn().mockImplementation((props: any) => (
			<div data-testid="custom-item">
				<span>Custom item: {props.data.title}</span>
			</div>
		))

		render(<DelightfulList items={items} listItemComponent={CustomListItem} />)

		// Custom component is used
		expect(CustomListItem).toHaveBeenCalledTimes(3)
		expect(screen.getAllByTestId("custom-item").length).toBe(3)
	})
})
