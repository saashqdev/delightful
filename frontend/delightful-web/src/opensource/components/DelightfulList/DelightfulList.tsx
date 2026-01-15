import { memo, forwardRef, useCallback, useMemo } from "react"
import type { EmptyProps, FlexProps } from "antd"
import { Flex } from "antd"
import type { ComponentType, ForwardedRef } from "react"
import { isEqual, omit } from "lodash-es"
import { cx } from "antd-style"
import type { DelightfulListItemData as DelightfulListItemItemType } from "./types"
import type { DelightfulListItemProps } from "./DelightfulListItem"
import DelightfulListItem from "./DelightfulListItem"
import DelightfulEmpty from "@/opensource/components/base/DelightfulEmpty"

export interface DelightfulListProps<
	R extends DelightfulListItemItemType = DelightfulListItemItemType,
> extends Omit<FlexProps, "children"> {
	items?: (string | R)[]
	emptyProps?: EmptyProps
	active?: string | ((item: R, index: number) => boolean)
	onItemClick?: (data: R) => void
	itemClassName?: string
	itemClassNames?: DelightfulListItemProps<R>["classNames"]
	listItemProps?: Partial<DelightfulListItemProps<R>>
	listItemComponent?: ComponentType<DelightfulListItemProps<R>>
}

// Optimize list items so they only rerender when necessary
type DelightfulListItemWrapperProps<R extends DelightfulListItemItemType> = {
	item: string | R
	index: number
	active: string | ((item: R, index: number) => boolean)
	onItemClick?: (data: R) => void
	itemClassName?: string
	itemClassNames?: DelightfulListItemProps<R>["classNames"]
	ListItemComponent: ComponentType<DelightfulListItemProps<R>>
	listItemProps?: Partial<DelightfulListItemProps<R>>
}

// Wrap list item separately for fine-grained rerender control
function DelightfulListItemWrapperComponent<R extends DelightfulListItemItemType>(
	props: DelightfulListItemWrapperProps<R>,
) {
	const {
		item: rawItem,
		index,
		active,
		onItemClick,
		itemClassName,
		itemClassNames,
		ListItemComponent,
		listItemProps,
	} = props

	// Convert string items to objects
	const item = useMemo(() => {
		if (typeof rawItem === "string") {
			return { id: rawItem } as R
		}
		return rawItem
	}, [rawItem])

	// Compute active state locally to avoid parent recomputation
	const activeStatus = useMemo(() => {
		return typeof active === "function" ? active(item, index) : active === item.id
	}, [active, item, index])

	// Memoize click handler
	const handleClick = useCallback(
		(data: R) => {
			if (onItemClick) {
				onItemClick(data)
			}
		},
		[onItemClick],
	)

	return (
		<ListItemComponent
			className={itemClassName}
			classNames={itemClassNames}
			key={item.id}
			active={activeStatus}
			data={item}
			onClick={handleClick}
			{...listItemProps}
		/>
	)
}

// Create memoized component with type safety
const DelightfulListItemWrapper = memo(
	DelightfulListItemWrapperComponent,
	// Custom comparator to avoid unnecessary rerenders
	<R extends DelightfulListItemItemType>(
		prevProps: DelightfulListItemWrapperProps<R>,
		nextProps: DelightfulListItemWrapperProps<R>,
	) => {
		// If item reference is identical, check other props only
		if (prevProps.item === nextProps.item) {
			// Still verify other props
			return (
				prevProps.active === nextProps.active &&
				prevProps.onItemClick === nextProps.onItemClick &&
				prevProps.itemClassName === nextProps.itemClassName &&
				isEqual(prevProps.itemClassNames, nextProps.itemClassNames) &&
				prevProps.ListItemComponent === nextProps.ListItemComponent &&
				isEqual(prevProps.listItemProps, nextProps.listItemProps)
			)
		}

		// For different references, compare content for equality
		if (
			typeof prevProps.item === "object" &&
			typeof nextProps.item === "object" &&
			prevProps.item?.id === nextProps.item?.id &&
			isEqual(prevProps.item, nextProps.item)
		) {
			// Content matches; verify remaining props
			return (
				prevProps.active === nextProps.active &&
				prevProps.onItemClick === nextProps.onItemClick &&
				prevProps.itemClassName === nextProps.itemClassName &&
				isEqual(prevProps.itemClassNames, nextProps.itemClassNames) &&
				prevProps.ListItemComponent === nextProps.ListItemComponent &&
				isEqual(prevProps.listItemProps, nextProps.listItemProps)
			)
		}

		// Otherwise, allow rerender
		return false
	},
) as typeof DelightfulListItemWrapperComponent

const DelightfulListBase = forwardRef(
	<R extends DelightfulListItemItemType>(
		props: DelightfulListProps<R>,
		ref: ForwardedRef<HTMLDivElement>,
	) => {
		const {
			items,
			active = "",
			onItemClick,
			itemClassName,
			itemClassNames,
			listItemComponent: ListItemComponent = DelightfulListItem as ComponentType<
				DelightfulListItemProps<R>
			>,
			style,
			emptyProps,
			listItemProps,
			...flexProps
		} = props

		// Memoize style object to avoid recreation
		const styles = useMemo(() => ({ width: "100%", ...style }), [style])

		// Memoize rendered items array
		const renderedItems = useMemo(() => {
			if (!items || items.length === 0) return null

			return items.map((item, index) => (
				<DelightfulListItemWrapper<R>
					key={typeof item === "string" ? item : item.id}
					item={item}
					index={index}
					active={active}
					onItemClick={onItemClick}
					itemClassName={itemClassName}
					itemClassNames={itemClassNames}
					ListItemComponent={ListItemComponent}
					listItemProps={listItemProps}
				/>
			))
		}, [
			items,
			active,
			onItemClick,
			itemClassName,
			itemClassNames,
			ListItemComponent,
			listItemProps,
		])

		// Handle empty state
		if (!items || items.length === 0) {
			if (!emptyProps) return null

			return (
				<DelightfulEmpty
					className={cx(flexProps.className, emptyProps?.className)}
					{...omit(emptyProps, "className")}
				/>
			)
		}

		return (
			<Flex ref={ref} vertical gap={4} style={styles} {...flexProps}>
				{renderedItems}
			</Flex>
		)
	},
)

// Wrap with memo for better performance
const DelightfulListOptimized = memo(DelightfulListBase) as typeof DelightfulListBase

export default DelightfulListOptimized
