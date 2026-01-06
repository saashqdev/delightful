import type React from "react"
import type { PaginationResponse } from "@/types/request"
import type { StructureItemType, WithIdAndDataType } from "@/types/organization"
import type { Props as InfiniteScrollProps } from "react-infinite-scroll-component"
import type { DelightfulListItemData as DelightfulListItemType } from "../DelightfulList/types"

/**
 * Props for DelightfulInfiniteScrollList
 */
export interface DelightfulInfiniteScrollListProps<
	D,
	ItemR extends DelightfulListItemType = DelightfulListItemType,
	DataType extends StructureItemType = StructureItemType,
> extends Omit<Partial<InfiniteScrollProps>, "children" | "dataLength" | "next" | "hasMore"> {
	/** Initial data */
	data?: PaginationResponse<D>
	/** Trigger function to load more data */
	trigger: (params: { page_token: string }) => Promise<PaginationResponse<D>>
	/** Transform raw item into a list item */
	itemsTransform: (item: D) => ItemR
	/** Callback when a list item is clicked */
	onItemClick?: (item: ItemR) => void
	/** Checkbox configuration */
	checkboxOptions?: {
		/** Currently selected items */
		checked?: WithIdAndDataType<ItemR, DataType>[]
		/** Callback when selection changes */
		onChange?: (checked: WithIdAndDataType<ItemR, DataType>[]) => void
		/** Disabled items */
		disabled?: WithIdAndDataType<ItemR, DataType>[]
		/** Data type */
		dataType: DataType
	}
	/** Fallback when there is no data */
	noDataFallback?: React.ReactNode
	/** Container height in pixels, default 400 */
	containerHeight?: number
	/** Disable load-more functionality */
	disableLoadMore?: boolean
	/** Height of each item, default 60 */
	itemHeight?: number
	/** Scroll threshold, default 50 */
	scrollThreshold?: number
	/** Custom loading indicator */
	loadingIndicator?: React.ReactNode
}
