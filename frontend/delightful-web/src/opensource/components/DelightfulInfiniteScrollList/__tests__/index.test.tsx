import { render, screen, fireEvent, waitFor, act } from "@testing-library/react"
import { vi, describe, test, expect, beforeEach } from "vitest"
import type { PaginationResponse } from "@/types/request"
import { StructureItemType } from "@/types/organization"
import DelightfulInfiniteScrollList from "../index"
import type { DelightfulListItemData } from "../../DelightfulList/types"

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

// Mock VirtualList component
vi.mock("rc-virtual-list", () => {
	return {
		default: ({ children, data, onScroll, height }: any) => (
			<div data-testid="virtual-list" style={{ height }}>
				{data.map((item: any) => children(item))}
				<button
					type="button"
					onClick={() => {
						onScroll({
							currentTarget: {
								scrollHeight: 1000,
								scrollTop: 950,
								clientHeight: 400,
							},
						})
					}}
					data-testid="scroll-to-bottom"
				>
					Scroll to bottom
				</button>
			</div>
		),
	}
})

// Mock DelightfulList-related components and styles
vi.mock("../../DelightfulList/DelightfulListItem", () => {
	return {
		default: ({ title, desc, avatar, active, onClick, data }: any) => (
			<div
				data-testid="delightful-list-item"
				className={active ? "active" : ""}
				onClick={onClick}
			>
				{avatar && <div data-testid="avatar">{avatar}</div>}
				<div data-testid="title">
					{typeof title === "string" ? title : data?.title || "title object"}
				</div>
				{desc && <div data-testid="desc">{desc}</div>}
				{data && <div data-testid="item-data">{data.name}</div>}
			</div>
		),
	}
})

// Mock DelightfulEmpty component
vi.mock("../../base/DelightfulEmpty", () => {
	return {
		default: () => <div data-testid="empty-state">No data</div>,
	}
})

// Mock antd components
vi.mock("antd", async () => {
	const antd = await vi.importActual("antd")
	return {
		...antd,
		List: ({ children, style, className }: any) => (
			<div data-testid="ant-list" className={className} style={style}>
				{children}
			</div>
		),
		Divider: ({ children, plain }: any) => (
			<div data-testid="ant-divider" className={plain ? "plain" : ""}>
				{children}
			</div>
		),
		Flex: ({ children, align, gap, justify, onClick }: any) => (
			<div
				data-testid="ant-flex"
				style={{
					display: "flex",
					alignItems: align,
					gap,
					justifyContent: justify,
				}}
				onClick={onClick}
			>
				{children}
			</div>
		),
		Checkbox: ({ checked, disabled, onChange }: any) => (
			<input
				type="checkbox"
				data-testid="ant-checkbox"
				checked={checked}
				disabled={disabled}
				onChange={(e) => onChange?.(e.target.checked)}
			/>
		),
		Spin: ({ size }: any) => (
			<div data-testid="ant-spin" className={size}>
				Loading...
			</div>
		),
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

vi.mock("antd-style", () => {
	return {
		createStyles: (fn: any) => {
			// Provide token values used in styles.ts
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

// Clear all mocks
beforeEach(() => {
	vi.clearAllMocks()
})

// Test data type
interface TestItem {
	id: string
	name: string
}

// Create mock data
const createMockData = (count: number, startId = 0): TestItem[] => {
	return Array.from({ length: count }, (_, i) => ({
		id: `test-${i + startId}`,
		name: `Test item ${i + startId}`,
	}))
}

// Create mock pagination response
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
const mockItemsTransform = (item: unknown): DelightfulListItemData => {
	const typedItem = item as TestItem
	return {
		id: typedItem.id,
		title: typedItem.name,
		name: typedItem.name,
	}
}

describe("DelightfulInfiniteScrollList", () => {
	// Reset mocks before each test
	beforeEach(() => {
		vi.clearAllMocks()
	})

	test("renders empty state", async () => {
		const mockTrigger = vi.fn().mockResolvedValue(createMockPaginationResponse([]))

		act(() => {
			render(
				<DelightfulInfiniteScrollList
					data={createMockPaginationResponse([])}
					trigger={mockTrigger}
					itemsTransform={mockItemsTransform}
				/>,
			)
		})

		// Wait for async tasks
		await waitFor(() => {
			expect(screen.getByTestId("empty-state")).toBeInTheDocument()
		})
	})

	test("renders custom empty state", async () => {
		const customEmptyState = <div data-testid="custom-empty">Custom empty state</div>
		const mockTrigger = vi.fn().mockResolvedValue(createMockPaginationResponse([]))

		act(() => {
			render(
				<DelightfulInfiniteScrollList
					data={createMockPaginationResponse([])}
					trigger={mockTrigger}
					itemsTransform={mockItemsTransform}
					noDataFallback={customEmptyState}
				/>,
			)
		})

		// Wait for async tasks
		await waitFor(() => {
			expect(screen.getByTestId("custom-empty")).toBeInTheDocument()
		})
	})

	test("renders data list", () => {
		const mockData = createMockData(3)
		render(
			<DelightfulInfiniteScrollList
				data={createMockPaginationResponse(mockData, false)}
				trigger={vi.fn()}
				itemsTransform={mockItemsTransform}
			/>,
		)

		const listItems = screen.getAllByTestId("delightful-list-item")
		expect(listItems).toHaveLength(3)
		expect(listItems[0]).toHaveTextContent("Test item 0")
	})

	test("clicking list item triggers callback", () => {
		const mockData = createMockData(3)
		const onItemClick = vi.fn()
		render(
			<DelightfulInfiniteScrollList
				data={createMockPaginationResponse(mockData, false)}
				trigger={vi.fn()}
				itemsTransform={mockItemsTransform}
				onItemClick={onItemClick}
			/>,
		)

		const listItems = screen.getAllByTestId("delightful-list-item")
		fireEvent.click(listItems[1])
		expect(onItemClick).toHaveBeenCalledWith(expect.objectContaining({ id: "test-1" }))
	})

	test("loads more items", async () => {
		const mockTrigger = vi
			.fn()
			.mockResolvedValue(createMockPaginationResponse(createMockData(3, 3), false))
		const mockData = createMockData(3)

		render(
			<DelightfulInfiniteScrollList
				data={createMockPaginationResponse(mockData, true)}
				trigger={mockTrigger}
				itemsTransform={mockItemsTransform}
			/>,
		)

		const scrollButton = screen.getByTestId("scroll-to-bottom")
		fireEvent.click(scrollButton)

		await waitFor(() => {
			expect(mockTrigger).toHaveBeenCalledWith({ page_token: "next-page" })
		})

		await waitFor(() => {
			const listItems = screen.getAllByTestId("delightful-list-item")
			expect(listItems).toHaveLength(6)
		})
	})

	test("disables load more when flagged", async () => {
		const mockTrigger = vi
			.fn()
			.mockResolvedValue(createMockPaginationResponse(createMockData(3, 3), true))
		const mockData = createMockData(3)

		render(
			<DelightfulInfiniteScrollList
				data={createMockPaginationResponse(mockData, true)}
				trigger={mockTrigger}
				itemsTransform={mockItemsTransform}
				disableLoadMore
			/>,
		)

		const scrollButton = screen.getByTestId("scroll-to-bottom")
		fireEvent.click(scrollButton)

		// Wait briefly then verify no load triggered
		await new Promise((r) => {
			setTimeout(r, 100)
		})
		expect(mockTrigger).not.toHaveBeenCalled()
	})

	test("custom loading indicator", async () => {
		const mockTrigger = vi.fn().mockImplementation(() => {
			// Use setTimeout and Promise.resolve to avoid Promise executor warning
			setTimeout(() => {}, 0)
			return Promise.resolve(createMockPaginationResponse(createMockData(3, 3), false))
		})

		const customLoadingIndicator = <div data-testid="custom-loading">Custom loading...</div>

		render(
			<DelightfulInfiniteScrollList
				data={createMockPaginationResponse(createMockData(3), true)}
				trigger={mockTrigger}
				itemsTransform={mockItemsTransform}
				loadingIndicator={customLoadingIndicator}
			/>,
		)

		const scrollButton = screen.getByTestId("scroll-to-bottom")
		fireEvent.click(scrollButton)

		await waitFor(() => {
			expect(screen.getByTestId("custom-loading")).toBeInTheDocument()
		})
	})

	test("custom item height", () => {
		const mockData = createMockData(3)
		render(
			<DelightfulInfiniteScrollList
				data={createMockPaginationResponse(mockData, false)}
				trigger={vi.fn()}
				itemsTransform={mockItemsTransform}
				itemHeight={80}
			/>,
		)

		const virtualList = screen.getByTestId("virtual-list")
		expect(virtualList).toBeInTheDocument()
	})

	test("custom container height", () => {
		const mockData = createMockData(3)
		render(
			<DelightfulInfiniteScrollList
				data={createMockPaginationResponse(mockData, false)}
				trigger={vi.fn()}
				itemsTransform={mockItemsTransform}
				containerHeight={600}
			/>,
		)

		const virtualList = screen.getByTestId("virtual-list")
		expect(virtualList).toHaveAttribute("style", expect.stringContaining("height: 600px"))
	})
})

// Checkbox functionality
describe("DelightfulInfiniteScrollList checkbox support", () => {
	test("renders checkboxes", () => {
		const mockData = createMockData(3)
		render(
			<DelightfulInfiniteScrollList
				data={createMockPaginationResponse(mockData, false)}
				trigger={vi.fn()}
				itemsTransform={mockItemsTransform}
				checkboxOptions={{
					checked: [],
					onChange: vi.fn(),
					dataType: StructureItemType.User,
				}}
			/>,
		)

		const checkboxes = screen.getAllByTestId("ant-checkbox")
		expect(checkboxes).toHaveLength(3)
	})

	test("checkbox select/deselect", () => {
		const mockData = createMockData(3)
		const onChange = vi.fn()
		render(
			<DelightfulInfiniteScrollList
				data={createMockPaginationResponse(mockData, false)}
				trigger={vi.fn()}
				itemsTransform={mockItemsTransform}
				checkboxOptions={{
					checked: [],
					onChange,
					dataType: StructureItemType.User,
				}}
			/>,
		)

		const flexItems = screen.getAllByTestId("ant-flex")
		fireEvent.click(flexItems[0])

		expect(onChange).toHaveBeenCalledWith([
			expect.objectContaining({
				id: "test-0",
				dataType: StructureItemType.User,
			}),
		])
	})

	test("disabled checkbox", () => {
		const mockData = createMockData(3)
		const onChange = vi.fn()
		render(
			<DelightfulInfiniteScrollList
				data={createMockPaginationResponse(mockData, false)}
				trigger={vi.fn()}
				itemsTransform={mockItemsTransform}
				checkboxOptions={{
					checked: [],
					onChange,
					disabled: [{ id: "test-1", dataType: StructureItemType.User }],
					dataType: StructureItemType.User,
				}}
			/>,
		)

		const checkboxes = screen.getAllByTestId("ant-checkbox")
		expect(checkboxes[1]).toHaveAttribute("disabled")
	})
})
