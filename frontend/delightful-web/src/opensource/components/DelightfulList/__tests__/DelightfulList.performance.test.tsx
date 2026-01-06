import { render, screen, fireEvent, act } from "@testing-library/react"
import { vi, describe, test, expect, beforeEach, afterEach } from "vitest"
import DelightfulList from "../DelightfulList"
import type { DelightfulListItemData } from "../types"

// Mock DelightfulAvatar component
vi.mock("@/opensource/components/base/DelightfulAvatar", () => ({
	default: ({ children, className, size }: any) => (
		<div className={className || "mock-avatar"} data-size={size}>
			{children || "Avatar"}
		</div>
	),
}))

// Mock styles module to provide tokens
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
		avatar: `https://example.com/avatar/${index}.jpg`,
		hoverSection: <div>Hover content {index}</div>,
	}))
}

describe("DelightfulList performance", () => {
	// High-level mocks
	let originalConsoleWarn: typeof console.warn
	let warnSpy: ReturnType<typeof vi.fn>

	beforeEach(() => {
		originalConsoleWarn = console.warn
		warnSpy = vi.fn()
		console.warn = warnSpy
	})

	afterEach(() => {
		console.warn = originalConsoleWarn
	})

	// Active prop changes should not cause excessive rerenders
	test("active prop change should not over-render", async () => {
		const items = createTestItems(20)

		const { rerender } = render(<DelightfulList items={items} active="item-1" />)

		// Reset spy
		warnSpy.mockReset()

		// Data unchanged, only active prop changes
		rerender(<DelightfulList items={items} active="item-2" />)

		// Expect no React optimization warnings
		expect(warnSpy).not.toHaveBeenCalledWith(
			expect.stringMatching(/Component is re-rendering too many times/),
		)
	})

	// Render performance with large data
	test("render performance with large datasets", () => {
		const smallItems = createTestItems(10)
		const mediumItems = createTestItems(100)
		const largeItems = createTestItems(500)

		// Record small list render time
		const smallStartTime = performance.now()
		const { unmount: unmountSmall } = render(<DelightfulList items={smallItems} />)
		const smallEndTime = performance.now()
		unmountSmall()

		// Record medium list render time
		const mediumStartTime = performance.now()
		const { unmount: unmountMedium } = render(<DelightfulList items={mediumItems} />)
		const mediumEndTime = performance.now()
		unmountMedium()

		// Record large list render time
		const largeStartTime = performance.now()
		const { unmount: unmountLarge } = render(<DelightfulList items={largeItems} />)
		const largeEndTime = performance.now()
		unmountLarge()

		// Compute render durations
		const smallRenderTime = smallEndTime - smallStartTime
		const mediumRenderTime = mediumEndTime - mediumStartTime
		const largeRenderTime = largeEndTime - largeStartTime

		console.log(`Render 10 items: ${smallRenderTime}ms`)
		console.log(`Render 100 items: ${mediumRenderTime}ms`)
		console.log(`Render 500 items: ${largeRenderTime}ms`)

		// Check whether render times grow roughly linearly
		// Medium should be around 10x small; large around 50x small (heuristic)
		expect(mediumRenderTime).toBeLessThan(smallRenderTime * 20)
		expect(largeRenderTime).toBeLessThan(mediumRenderTime * 10)
	})

	// Performance under frequent updates
	test("performance under frequent updates", () => {
		const items = createTestItems(50)
		const { rerender } = render(<DelightfulList items={items} />)

		const iterations = 10
		const startTime = performance.now()

		// Simulate repeated rerenders
		for (let i = 0; i < iterations; i += 1) {
			// Each iteration updates a different active item
			act(() => {
				rerender(<DelightfulList items={items} active={`item-${i}`} />)
			})
		}

		const endTime = performance.now()
		const averageRenderTime = (endTime - startTime) / iterations

		console.log(`Frequent update test: avg render ${averageRenderTime}ms`)

		// Expect average render time under a reasonable threshold
		expect(averageRenderTime).toBeLessThan(50)
	})

	// Memoization should reduce rerenders
	test("memoization should reduce rerenders", () => {
		const items = createTestItems(20)
		const onClick = vi.fn()

		// First render
		const { rerender } = render(<DelightfulList items={items} onItemClick={onClick} />)

		// Rerender with identical props
		const startTime = performance.now()
		rerender(<DelightfulList items={items} onItemClick={onClick} />)
		const endTime = performance.now()

		// With memo, identical-prop rerenders should be very fast
		const rerenderTime = endTime - startTime
		console.log(`Same-prop rerender time: ${rerenderTime}ms`)

		// Expect very short rerender duration
		expect(rerenderTime).toBeLessThan(10)

		// Props with identical content but new references
		const sameContentItems = createTestItems(20)
		const sameContentOnClick = vi.fn()

		const startTimeNewProps = performance.now()
		rerender(<DelightfulList items={sameContentItems} onItemClick={sameContentOnClick} />)
		const endTimeNewProps = performance.now()

		const newPropsRerenderTime = endTimeNewProps - startTimeNewProps
		console.log(`Same-content new-prop rerender time: ${newPropsRerenderTime}ms`)

		// May expose memo gaps when references change despite identical content
	})

	// Mouse interaction performance with many items
	test("mouse interaction performance with many items", () => {
		const items = createTestItems(100)

		render(<DelightfulList items={items} />)

		// Get all list items
		const listItems = screen.getAllByText(/Test item \d+/)

		// Simulate hovering multiple items
		const startTime = performance.now()

		for (let i = 0; i < 10; i += 1) {
			// Mouse enter
			fireEvent.mouseEnter(listItems[i])
			// Mouse leave
			fireEvent.mouseLeave(listItems[i])
		}

		const endTime = performance.now()
		const hoverTime = endTime - startTime

		console.log(`10 hover interactions took: ${hoverTime}ms`)

		// Expect good hover performance
		expect(hoverTime / 10).toBeLessThan(20) // Average under 20ms per interaction
	})
})
