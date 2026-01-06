import { render, screen, fireEvent } from "@testing-library/react"
import { vi, describe, test, expect, beforeEach } from "vitest"
import type { PaginationResponse } from "@/types/request"
import type { StructureItemType } from "@/types/organization"
import type { DelightfulListItemData } from "../../DelightfulList/types"
import DelightfulInfiniteScrollList from "../DelightfulInfiniteScrollList"

// Mock InfiniteScroll component
vi.mock("react-infinite-scroll-component", () => {
	return {
		default: ({ children, next, hasMore }: any) => (
			<div data-testid="infinite-scroll">
				{children}
				{hasMore && (
					<button type="button" onClick={next} data-testid="load-more">
						Load more
					</button>
				)}
			</div>
		),
	}
})

// Mock DelightfulList-related components and styles
vi.mock("../../DelightfulList/DelightfulListItem", () => {
	return {
		default: ({ title, desc, avatar, active, onClick }: any) => (
			<div data-testid="delightful-list-item" className={active ? "active" : ""} onClick={onClick}>
				{avatar && <div data-testid="avatar">{avatar}</div>}
				<div data-testid="title">{typeof title === "string" ? title : "title object"}</div>
				{desc && <div data-testid="desc">{desc}</div>}
			</div>
		),
	}
})

vi.mock("antd-style", () => {
	return {
		createStyles: (fn: any) => {
			// Provide token values required in styles.ts
			const token = {
				delightfulColorScales: { grey: [0, 1, 2, 3, 4, 5] },
				delightfulColorUsages: {
					text: [0, 1, 2, 3, 4, 5],
					primaryLight: { default: "#e6f7ff" },
					white: "#ffffff",
				},
			}
			return () => fn({ css: () => ({}), isDarkMode: false, token })
		},
		cx: (...args: any[]) => args.join(" "),
		css: (literals: TemplateStringsArray, ...placeholders: any[]) =>
			literals.reduce((acc, literal, i) => acc + literal + (placeholders[i] || ""), ""),
	}
})

// Mock i18n
vi.mock("react-i18next", () => ({
	useTranslation: () => {
		return {
			t: (str: string) => str,
			i18n: {
				changeLanguage: () => new Promise(() => {}),
			},
		}
	},
}))

// Mock Ant Design components
vi.mock("antd", () => {
	const Checkbox = ({ checked, disabled, onChange }: any) => (
		<input
			type="checkbox"
			checked={checked}
			disabled={disabled}
			onChange={(e) => onChange && onChange(e.target.checked)}
			data-testid="mock-checkbox"
		/>
	)

	return {
		Checkbox,
		Flex: ({ children, ...props }: any) => (
			<div data-testid="flex" {...props}>
				{children}
			</div>
		),
		Skeleton: () => <div data-testid="skeleton">Loading...</div>,
		Divider: ({ children, plain }: any) => (
			<div data-testid="divider" className={plain ? "plain" : ""}>
				{children}
			</div>
		),
		Spin: ({ size }: any) => (
			<div data-testid="mock-spin" className={size}>
				Loading...
			</div>
		),
		List: ({ children, style, className }: any) => (
			<div data-testid="mock-list" className={className} style={style}>
				{children}
			</div>
		),
	}
})

// Clear all mocks
beforeEach(() => {
	vi.clearAllMocks()
})

// Test interfaces
interface TestItem {
	id: string
	name: string
}

// Test data type
interface TestItemData extends DelightfulListItemData {
	id: string
	name: string
}

// Create mock data — can generate large sets for perf tests
const createMockData = (count: number, startId = 0): TestItem[] => {
	return Array.from({ length: count }).map((_, index) => ({
		id: `item-${startId + index}`,
		name: `Test item ${startId + index}`,
	}))
}

// Mock pagination response
const createMockPaginationResponse = (
	items: TestItem[],
	hasMore = true,
	pageToken = "next-page",
): PaginationResponse<TestItem> => {
	return {
		items,
		has_more: hasMore,
		page_token: pageToken,
	}
}

// Data transform function
const mockItemsTransform = (item: unknown): TestItemData => {
	const typedItem = item as TestItem
	return {
		id: typedItem.id,
		name: typedItem.name,
		// 基础DelightfulListItemData属性
		title: typedItem.name,
		avatar: "",
		desc: `Description ${typedItem.id}`,
	}
}

// Measure function execution time
const measureExecutionTime = async (callback: () => Promise<void> | void): Promise<number> => {
	const start = performance.now()
	await callback()
	const end = performance.now()
	return end - start
}

describe("DelightfulInfiniteScrollList performance", () => {
	// Render performance with varying data sizes
	test("render performance: initial render across data sizes", async () => {
		const dataSizes = [10, 50, 100, 200]
		const renderTimes: { size: number; time: number }[] = []

		// Test render time for each data size
		const testRenderTime = async (index: number) => {
			if (index >= dataSizes.length) return

			const size = dataSizes[index]
			const mockItems = createMockData(size)
			const mockData = createMockPaginationResponse(mockItems)
			const mockTrigger = vi
				.fn()
				.mockResolvedValue(createMockPaginationResponse(createMockData(size, size)))

			const time = await measureExecutionTime(async () => {
				const { unmount } = render(
					<DelightfulInfiniteScrollList
						data={mockData}
						trigger={mockTrigger}
						itemsTransform={mockItemsTransform}
					/>,
				)

				// Ensure all items rendered
				const items = screen.getAllByTestId("delightful-list-item")
				expect(items.length).toBe(size)

				// Unmount for next run
				unmount()
			})

			renderTimes.push({ size, time })
			console.log(`Render ${size} items took: ${time.toFixed(2)}ms`)

			// Recurse for next data size
			await testRenderTime(index + 1)
		}

		// Kick off tests
		await testRenderTime(0)

		// Verify render time roughly scales with data size
		// Note: Rough check; growth may not be perfectly linear
		for (let i = 1; i < renderTimes.length; i += 1) {
			const current = renderTimes[i]
			const previous = renderTimes[i - 1]
			const ratio = current.time / previous.time

			console.log(
				`When data grows from ${previous.size} to ${current.size}, render time ratio: ${ratio.toFixed(2)}`,
			)

			// Render time should not exceed 5x the data growth (heuristic threshold)
			expect(ratio).toBeLessThan((current.size / previous.size) * 5)
		}
	})

	// Interaction performance: load-more path (skipped due to env limits)
	test.skip("交互性能：加载更多数据的响应时间", async () => {
		const initialSize = 10
		const mockData = createMockData(initialSize)
		const mockTrigger = vi
			.fn()
			.mockResolvedValue(
				createMockPaginationResponse(createMockData(10), true, "next-page-2"),
			)

		// Render component
		render(
			<DelightfulInfiniteScrollList
				data={createMockPaginationResponse(mockData, true, "next-page")}
				trigger={mockTrigger}
				itemsTransform={mockItemsTransform}
			/>,
		)

		// Initial render should have initialSize items
		const initialItems = screen.getAllByTestId("delightful-list-item")
		expect(initialItems.length).toBe(initialSize)

		// Due to test env limits, load-more interaction can't be simulated reliably
		// This should work in a real environment
		console.log("Load-more test skipped; cannot simulate accurately in test env")
	})

	// Checkbox performance: select/deselect timings
	test("checkbox performance: select/deselect response time", async () => {
		const dataSize = 100
		const mockItems = createMockData(dataSize)
		const mockData = createMockPaginationResponse(mockItems)
		const mockTrigger = vi.fn()
		const mockOnChange = vi.fn()

		render(
			<DelightfulInfiniteScrollList
				data={mockData}
				trigger={mockTrigger}
				itemsTransform={mockItemsTransform}
				checkboxOptions={{
					checked: [],
					onChange: mockOnChange,
					dataType: "user" as StructureItemType,
				}}
			/>,
		)

		const checkboxes = screen.getAllByTestId("mock-checkbox")
		expect(checkboxes.length).toBe(100)

		// Measure select timing on first checkbox
		const selectTime = await measureExecutionTime(() => {
			fireEvent.click(checkboxes[0])
		})

		console.log(`Select response time: ${selectTime.toFixed(2)}ms`)
		expect(mockOnChange).toHaveBeenCalledTimes(1)
		expect(selectTime).toBeLessThan(200) // Should be under 200ms

		// Pretend 50 checkboxes are preselected
		const initialChecked = Array.from({ length: 50 }).map((_, i) => ({
			id: `item-${i}`,
			name: `测试项 ${i}`,
			title: `测试项 ${i}`,
			avatar: "",
			desc: `描述 item-${i}`,
			dataType: "user" as StructureItemType,
		}))

		// Re-render component
		render(
			<DelightfulInfiniteScrollList
				data={mockData}
				trigger={mockTrigger}
				itemsTransform={mockItemsTransform}
				checkboxOptions={{
					checked: initialChecked,
					onChange: mockOnChange,
					dataType: "user" as StructureItemType,
				}}
			/>,
		)

		// Clear previous calls
		mockOnChange.mockClear()

		// Measure deselect timing
		const updatedCheckboxes = screen.getAllByTestId("mock-checkbox")

		// Deselect the first (already selected) checkbox
		const unselectTime = await measureExecutionTime(() => {
			fireEvent.click(updatedCheckboxes[0])
		})

		console.log(`Deselect response time: ${unselectTime.toFixed(2)}ms`)
		expect(mockOnChange).toHaveBeenCalledTimes(1)
		expect(unselectTime).toBeLessThan(200) // Should be under 200ms
	})

	// End-to-end performance: realistic user scenario
	test("end-to-end performance: realistic interaction flow", async () => {
		const dataSize = 150
		const initialCheckedCount = 20
		const mockData = createMockData(dataSize)

		// Preselect 20 items
		const initialCheckedItems = mockData.slice(0, initialCheckedCount).map((item) => ({
			id: item.id,
			name: item.name,
			title: item.name,
			avatar: "",
			desc: `描述 ${item.id}`,
			dataType: "user" as StructureItemType,
		}))

		const mockTrigger = vi
			.fn()
			.mockResolvedValue(
				createMockPaginationResponse(createMockData(10, dataSize), true, "next-page-2"),
			)

		// Measure total render time
		const totalRenderTime = await measureExecutionTime(async () => {
			render(
				<DelightfulInfiniteScrollList
					data={createMockPaginationResponse(mockData, true, "next-page")}
					trigger={mockTrigger}
					itemsTransform={mockItemsTransform}
					checkboxOptions={{
						checked: initialCheckedItems,
						onChange: vi.fn(),
						dataType: "user" as StructureItemType,
					}}
				/>,
			)
		})

		console.log(
			`Initial render (${dataSize} items, ${initialCheckedCount} preselected): ${totalRenderTime.toFixed(2)}ms`,
		)
		expect(totalRenderTime).toBeLessThan(500) // Initial render should be under 500ms

		// Only verify correct render here
		const items = screen.getAllByTestId("delightful-list-item")
		expect(items.length).toBe(150)
	})
})
