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

export interface DelightfulListProps<R extends DelightfulListItemItemType = DelightfulListItemItemType>
	extends Omit<FlexProps, "children"> {
	items?: (string | R)[]
	emptyProps?: EmptyProps
	active?: string | ((item: R, index: number) => boolean)
	onItemClick?: (data: R) => void
	itemClassName?: string
	itemClassNames?: DelightfulListItemProps<R>["classNames"]
	listItemProps?: Partial<DelightfulListItemProps<R>>
	listItemComponent?: ComponentType<DelightfulListItemProps<R>>
}

// 优化列表项组件，确保只有在必要时才重新渲染
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

// 单独封装列表项组件，实现精细的重渲染控制
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

	// 处理字符串项，转换为对象
	const item = useMemo(() => {
		if (typeof rawItem === "string") {
			return { id: rawItem } as R
		}
		return rawItem
	}, [rawItem])

	// 计算活动状态，避免在父组件中重复计算
	const activeStatus = useMemo(() => {
		return typeof active === "function" ? active(item, index) : active === item.id
	}, [active, item, index])

	// 记忆化点击处理器
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

// 使用类型安全的方式创建 memo 组件
const DelightfulListItemWrapper = memo(
	DelightfulListItemWrapperComponent,
	// 自定义比较函数，避免不必要的重渲染
	<R extends DelightfulListItemItemType>(
		prevProps: DelightfulListItemWrapperProps<R>,
		nextProps: DelightfulListItemWrapperProps<R>,
	) => {
		// 如果item引用相同，直接跳过更新
		if (prevProps.item === nextProps.item) {
			// 但仍需检查其他属性
			return (
				prevProps.active === nextProps.active &&
				prevProps.onItemClick === nextProps.onItemClick &&
				prevProps.itemClassName === nextProps.itemClassName &&
				isEqual(prevProps.itemClassNames, nextProps.itemClassNames) &&
				prevProps.ListItemComponent === nextProps.ListItemComponent &&
				isEqual(prevProps.listItemProps, nextProps.listItemProps)
			)
		}

		// 对于不同引用的item，检查内容是否相同
		if (
			typeof prevProps.item === "object" &&
			typeof nextProps.item === "object" &&
			prevProps.item?.id === nextProps.item?.id &&
			isEqual(prevProps.item, nextProps.item)
		) {
			// 内容相同，检查其他属性
			return (
				prevProps.active === nextProps.active &&
				prevProps.onItemClick === nextProps.onItemClick &&
				prevProps.itemClassName === nextProps.itemClassName &&
				isEqual(prevProps.itemClassNames, nextProps.itemClassNames) &&
				prevProps.ListItemComponent === nextProps.ListItemComponent &&
				isEqual(prevProps.listItemProps, nextProps.listItemProps)
			)
		}

		// 默认情况下，认为组件需要更新
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

		// 记忆化样式，避免每次渲染都创建新样式对象
		const styles = useMemo(() => ({ width: "100%", ...style }), [style])

		// 记忆化列表项数组
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

		// 处理空列表状态
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

// 使用 memo 包装组件，提供更好的性能
const DelightfulListOptimized = memo(DelightfulListBase) as typeof DelightfulListBase

export default DelightfulListOptimized
