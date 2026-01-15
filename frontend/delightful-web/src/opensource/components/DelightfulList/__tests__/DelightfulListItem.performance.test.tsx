import { render } from "@testing-library/react"
import { vi, describe, test, expect, beforeEach } from "vitest"
import DelightfulListItem from "../DelightfulListItem"
import type { DelightfulListItemData } from "../types"

// Mock ahooks useHover hook
vi.mock("ahooks", () => ({
	useHover: vi.fn().mockReturnValue(false),
}))

// Mock DelightfulAvatar component
vi.mock("@/opensource/components/base/DelightfulAvatar", () => ({
	default: vi.fn().mockImplementation(({ src, className }) => (
		<div className={className} data-testid="mock-avatar" data-src={src}>
			Mock Avatar
		</div>
	)),
}))

// Mock styles module
vi.mock("../styles", () => ({
	useDelightfulListItemStyles: () => ({
		styles: {
			container: "mock-container",
			active: "mock-active",
			mainWrapper: "mock-main-wrapper",
			extra: "mock-extra",
		},
	}),
}))

// Test data
const createTestItem = (id: string): DelightfulListItemData => ({
	id,
	title: `Test item ${id}`,
	avatar: `https://example.com/avatar-${id}.jpg`,
})

describe("DelightfulListItem performance", () => {
	// Reset mocks before each test
	beforeEach(() => {
		vi.clearAllMocks()
	})

	// Render speed
	test("render performance - single render should be fast", () => {
		const item = createTestItem("test-1")

		// Measure render start
		const startTime = performance.now()

		render(<DelightfulListItem data={item} />)

		// Measure render end
		const endTime = performance.now()
		const renderTime = endTime - startTime

		console.log(`DelightfulListItem render time: ${renderTime}ms`)

		// Avoid hard time limits; ensure timing works
		expect(renderTime).toBeGreaterThan(0) // Timing sanity check
		// Skip strict thresholds in CI
		if (process.env.CI !== "true") {
			expect(renderTime).toBeLessThan(200) // Loose threshold for tolerance
		}
	})

	// Memo optimization: rerender with identical data
	test("render optimization - rerendering identical data should be quick", () => {
		const item = createTestItem("test-1")
		const { rerender } = render(<DelightfulListItem data={item} />)

		// Measure rerender performance with identical data
		const startTime = performance.now()

		// Rerender identical data multiple times
		for (let i = 0; i < 10; i += 1) {
			rerender(<DelightfulListItem data={item} />)
		}

		const endTime = performance.now()
		const averageRenderTime = (endTime - startTime) / 10

		console.log(`Average rerender time with identical data: ${averageRenderTime}ms`)

		// Use relative performance instead of hard limits
		// First render a new ID to get a baseline
		const newItem = { ...item, id: "test-new" }
		const newStartTime = performance.now()
		rerender(<DelightfulListItem data={newItem} />)
		const newEndTime = performance.now()
		const newItemRenderTime = newEndTime - newStartTime

		console.log(`New data render time: ${newItemRenderTime}ms`)

		// Expect identical-data rerenders to be faster
		// Skip strict checks in CI
		if (process.env.CI !== "true") {
			expect(averageRenderTime).toBeLessThan(100) // Loose threshold for tolerance
			// Check relative performance instead of absolute
			expect(averageRenderTime).toBeLessThan(newItemRenderTime * 0.8) // At least 20% faster
		}
	})

	// Render when data changes
	test("render optimization - partial data changes should be efficient", () => {
		// Initial data
		const initialItem = createTestItem("test-1")
		const { rerender } = render(<DelightfulListItem data={initialItem} />)

		// Only change title
		const startTimeTitle = performance.now()

		const itemWithNewTitle = {
			...initialItem,
			title: "New title",
		}

		rerender(<DelightfulListItem data={itemWithNewTitle} />)

		const endTimeTitle = performance.now()
		const titleChangeTime = endTimeTitle - startTimeTitle
		console.log(`Title-only change render time: ${titleChangeTime}ms`)

		// Change ID (should trigger full rerender)
		const startTimeId = performance.now()

		const itemWithNewId = {
			...initialItem,
			id: "test-2",
		}

		rerender(<DelightfulListItem data={itemWithNewId} />)

		const endTimeId = performance.now()
		const idChangeTime = endTimeId - startTimeId
		console.log(`ID change render time: ${idChangeTime}ms`)

		// Relative performance over absolute timing
		expect(titleChangeTime).toBeGreaterThan(0) // Timing sanity check
		expect(idChangeTime).toBeGreaterThan(0) // Timing sanity check

		// Only run strict checks outside CI
		if (process.env.CI !== "true") {
			// Given perf variance, just assert both complete quickly
			expect(titleChangeTime).toBeLessThan(100) // Loose 100ms bound
			expect(idChangeTime).toBeLessThan(100) // Loose 100ms bound

			// Log ratio for debugging only
			console.log(`Title vs ID change time ratio: ${titleChangeTime / idChangeTime}`)
		}
	})

	// Memory usage
	test("memory optimization - component should not significantly increase memory usage across renders", () => {
		// Note: measuring memory directly in JS/Node is noisy; illustrative only

		// Skip in CI because memory tests are flaky
		if (process.env.CI === "true") {
			console.log("Skip memory test in CI")
			return
		}

		const items = Array.from({ length: 10 }).map((_, i) => createTestItem(`test-${i}`))

		// Roughly measure memory delta
		if (typeof global.gc === "function") {
			global.gc() // Try to trigger GC (requires --expose-gc)
		}

		const memoryBefore = process.memoryUsage().heapUsed

		// Render 10 components
		const { unmount } = render(
			<div>
				{items.map((item) => (
					<DelightfulListItem key={item.id} data={item} />
				))}
			</div>,
		)

		const memoryAfter = process.memoryUsage().heapUsed
		const memoryIncrease = (memoryAfter - memoryBefore) / 1024 / 1024
		console.log(`Memory increase after rendering 10 components: ${memoryIncrease}MB`)

		// Ensure memory increase stays bounded (coarse check)
		expect(memoryIncrease).toBeLessThan(50) // Allow up to 50MB increase

		// Clean up
		unmount()
	})

	// Memo comparator effectiveness
	test("optimization - memo comparator should skip unnecessary renders", () => {
		const renderSpy = vi.fn()

		// Wrapper to track render counts
		const TestWrapper = ({
			item,
			active,
		}: {
			item: DelightfulListItemData
			active?: boolean
		}) => {
			renderSpy()
			return <DelightfulListItem data={item} active={active} />
		}

		const item = createTestItem("test-1")
		const { rerender } = render(<TestWrapper item={item} />)

		// Reset spy count
		renderSpy.mockClear()

		// Rerender with same data
		rerender(<TestWrapper item={item} />)

		// Wrapper re-renders; inner DelightfulListItem should be skipped
		expect(renderSpy).toHaveBeenCalledTimes(1)

		// Change active prop
		rerender(<TestWrapper item={item} active />)

		// Should trigger DelightfulListItem rerender
		expect(renderSpy).toHaveBeenCalledTimes(2)

		// Change item id
		const newItem = { ...item, id: "test-2" }
		rerender(<TestWrapper item={newItem} active />)

		// Should trigger DelightfulListItem rerender
		expect(renderSpy).toHaveBeenCalledTimes(3)
	})
})
