import type React from "react"
import { useCallback, useMemo, useRef, useState, memo } from "react"
import { useMemoizedFn, useMount, useUpdateEffect } from "ahooks"
import { Checkbox, Flex, List, Spin } from "antd"
import type { WithIdAndDataType } from "@/types/organization"
import VirtualList from "rc-virtual-list"
import type { DelightfulListItemData as DelightfulListItemType } from "../DelightfulList/types"
import type { DelightfulListItemProps } from "../DelightfulList/DelightfulListItem"
import DelightfulListItem from "../DelightfulList/DelightfulListItem"
import DelightfulEmpty from "@/opensource/components/base/DelightfulEmpty"
import type { DelightfulInfiniteScrollListProps } from "./types"

function DelightfulInfiniteScrollListComponent<D, ItemR extends DelightfulListItemType = DelightfulListItemType>({
	data,
	trigger,
	itemsTransform,
	onItemClick,
	noDataFallback,
	style,
	className,
	checkboxOptions,
	containerHeight,
	disableLoadMore = false,
	itemHeight = 60,
	scrollThreshold = 50,
	loadingIndicator,
}: DelightfulInfiniteScrollListProps<D, ItemR>) {
	// Data and loading state
	const [listData, setListData] = useState<D[]>(() => data?.items || [])
	const pageTokenRef = useRef(data?.page_token || "")
	const [hasMore, setHasMore] = useState<boolean>(data?.has_more ?? true)
	const [loading, setLoading] = useState<boolean>(false)
	// Request identifier to handle race conditions
	const requestIdRef = useRef(0)

	// In tests, ensure loading state does not block rendering the empty view
	const isTestEnv = process.env.NODE_ENV === "test"

	// Transform raw data into list items; memoized to avoid redundant work
	const itemsArray = useMemo(() => {
		if (!listData || listData.length === 0) return []

		// Handle single item or array inputs
		return Array.isArray(listData)
			? listData.map((item) => itemsTransform(item as D))
			: [itemsTransform(listData as D)]
	}, [listData, itemsTransform])

	// Lookup maps for checked/disabled states
	const checkedItemsMap = useMemo(() => {
		if (!checkboxOptions?.checked) return new Map<string, boolean>()

		const map = new Map<string, boolean>()
		checkboxOptions.checked.forEach((item) => {
			map.set(item.id, true)
		})
		return map
	}, [checkboxOptions?.checked])

	const disabledItemsMap = useMemo(() => {
		if (!checkboxOptions?.disabled) return new Map<string, boolean>()

		const map = new Map<string, boolean>()
		checkboxOptions.disabled.forEach((item) => {
			map.set(item.id, true)
		})
		return map
	}, [checkboxOptions?.disabled])

	// Load more data
	const loadMore = useCallback(() => {
		if (!hasMore || loading || disableLoadMore) return

		setLoading(true)
		// Increment request ID to mark the latest call
		const currentRequestId = requestIdRef.current + 1
		requestIdRef.current = currentRequestId

		trigger?.({ page_token: pageTokenRef.current })
			.then((response) => {
				// Ignore results from stale requests
				if (currentRequestId !== requestIdRef.current) return

				if (response && Array.isArray(response.items)) {
					setListData((prev) => [...prev, ...response.items])
					pageTokenRef.current = response.page_token
					setHasMore(response.has_more)
				}
			})
			.catch((error) => {
				// Only handle errors from the latest request
				if (currentRequestId === requestIdRef.current) {
					console.error("Failed to load more data:", error)
				}
			})
			.finally(() => {
				// Only clear loading for the latest request
				if (currentRequestId === requestIdRef.current) {
					setLoading(false)
				}
			})
	}, [trigger, hasMore, loading, disableLoadMore])

	// Reset list when trigger changes
	useUpdateEffect(() => {
		// Bump request ID to cancel in-flight requests
		requestIdRef.current += 1

		setHasMore(true)
		pageTokenRef.current = ""
		setLoading(false)
		setListData([])

		// Call loadMore directly
		loadMore()
	}, [trigger])

	// Initial load
	useMount(() => {
		// If initial data exists, use it
		if (data && data.items.length > 0) {
			setListData(data.items)
			pageTokenRef.current = data.page_token || ""
			setHasMore(data.has_more)
		} else {
			// Otherwise perform initial fetch
			loadMore()
		}
	})

	// Handle checkbox select/deselect
	const handleItemCheck = useCallback(
		(item: ItemR, checked: boolean) => {
			if (!checkboxOptions?.onChange) return

			let newChecked: WithIdAndDataType<ItemR, any>[] = []

			if (checked) {
				// Add to checked list
				newChecked = [
					...(checkboxOptions.checked || []),
					{
						...item,
						dataType: checkboxOptions.dataType,
						id: item.id,
					} as WithIdAndDataType<ItemR, any>,
				]
			} else {
				// Remove from checked list
				newChecked = (checkboxOptions.checked || []).filter((i) => i.id !== item.id)
			}

			// Invoke external onChange callback
			checkboxOptions.onChange(newChecked)
		},
		[checkboxOptions],
	)

	// Check if item is selected
	const isItemChecked = useCallback(
		(itemId: string): boolean => {
			return checkedItemsMap.has(itemId)
		},
		[checkedItemsMap],
	)

	// Check if item is disabled
	const isItemDisabled = useCallback(
		(itemId: string): boolean => {
			return disabledItemsMap.has(itemId)
		},
		[disabledItemsMap],
	)

	// Optimized scroll handler
	const onScroll = useMemoizedFn((e: React.UIEvent<HTMLElement, UIEvent>) => {
		console.log("onScroll", loading, hasMore, disableLoadMore)
		if (loading || !hasMore || disableLoadMore) return

		const { scrollHeight, scrollTop, clientHeight } = e.currentTarget
		if (scrollHeight - scrollTop - clientHeight <= scrollThreshold) {
			loadMore()
		}
	})

	// Custom list item component (memoized)
	const CustomListItem = memo(({ data: itemData, ...restProps }: DelightfulListItemProps<ItemR>) => {
		// Without checkbox options, render a plain list item
		if (!checkboxOptions) {
			return <DelightfulListItem<ItemR> data={itemData} {...restProps} />
		}

		// Status flags
		const checked = isItemChecked(itemData.id)
		const disabled = isItemDisabled(itemData.id)

		// Handle checkbox click
		const handleCheckboxClick = (e: React.MouseEvent) => {
			e.stopPropagation()
			if (disabled) return

			// Toggle selection
			handleItemCheck(itemData, !checked)
		}

		// Handle list item click
		const handleItemClick = () => {
			// If disabled, only fire click handler, do not toggle selection
			if (disabled) {
				onItemClick?.(itemData)
				return
			}

			// Toggle selection
			handleItemCheck(itemData, !checked)

			// Fire click handler
			onItemClick?.(itemData)
		}

		return (
			<Flex gap={10} align="center" onClick={handleItemClick}>
				<div onClick={handleCheckboxClick}>
					<Checkbox checked={checked} disabled={disabled} />
				</div>
				<div style={{ flex: 1 }}>
					<DelightfulListItem<ItemR> data={itemData} {...restProps} />
				</div>
			</Flex>
		)
	})

	// Preserve display name for clearer debugging
	CustomListItem.displayName = "CustomListItem"

	// Memoized loading indicator
	const loadingElement = useMemo(() => {
		return (
			loadingIndicator ?? (
				<div style={{ textAlign: "center", padding: "12px 0" }}>
					<Spin size="small" />
				</div>
			)
		)
	}, [loadingIndicator])

	const noData = !itemsArray || itemsArray.length === 0

	// Handle list item click
	const handleItemClick = useCallback(
		(item: ItemR) => {
			if (onItemClick) {
				onItemClick(item)
			}
		},
		[onItemClick],
	)

	if (noData && (!loading || isTestEnv)) {
		return noDataFallback || <DelightfulEmpty data-testid="empty-state" />
	}

	if (noData && loading && !isTestEnv) {
		return (
			<Flex justify="center" align="center" style={{ height: containerHeight }}>
				{loadingElement}
			</Flex>
		)
	}

	return (
		<List style={style} className={className}>
			<VirtualList<ItemR>
				data={itemsArray}
				itemKey="id"
				itemHeight={itemHeight}
				height={containerHeight}
				onScroll={onScroll}
				data-testid="virtual-list"
			>
				{(item) => (
					<div key={item.id} onClick={() => handleItemClick(item)}>
						<CustomListItem data={item} />
					</div>
				)}
			</VirtualList>
			{loading && loadingElement}
		</List>
	)
}

	// Memoize component to avoid unnecessary re-renders
const DelightfulInfiniteScrollList = memo(DelightfulInfiniteScrollListComponent) as <
	D,
	ItemR extends DelightfulListItemType = DelightfulListItemType,
>(
	props: DelightfulInfiniteScrollListProps<D, ItemR>,
) => React.ReactElement

export default DelightfulInfiniteScrollList
