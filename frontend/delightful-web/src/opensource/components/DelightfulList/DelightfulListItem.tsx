import { Flex } from "antd"
import { cx } from "antd-style"
import { useMemo, memo, useRef, useCallback } from "react"
import type { MouseEventHandler, HTMLAttributes } from "react"
import DelightfulAvatar from "@/opensource/components/base/DelightfulAvatar"
import { useHover } from "ahooks"
import type { DelightfulListItemData as DelightfulListItemType } from "./types"
import { useDelightfulListItemStyles } from "./styles"

export interface DelightfulListItemProps<D extends DelightfulListItemType = DelightfulListItemType>
	extends Omit<HTMLAttributes<HTMLDivElement>, "data" | "onClick" | "children"> {
	data: D
	active?: boolean
	titleMaxWidth?: number
	onClick?: (data: D) => void
	classNames?: {
		container?: string
		avatar?: string
		title?: string
		content?: string
	}
}

// Extract complex avatar rendering into its own component
const ItemAvatar = memo(
	<D extends DelightfulListItemType>({
		avatar,
		isHover,
		className,
	}: {
		avatar: D["avatar"]
		isHover: boolean
		className?: string
	}) => {
		// No avatar provided
		if (!avatar) return null

		// Avatar provided as render function
		if (typeof avatar === "function") return avatar(isHover)

		// Avatar provided as string URL
		if (typeof avatar === "string")
			return <DelightfulAvatar size="large" src={avatar} className={className} />

		// Avatar provided as object with props
		return <DelightfulAvatar size="large" src={avatar.src} className={className} {...avatar} />
	},
	(prev, next) => {
		// Custom comparator to optimize renders
		if (prev.isHover !== next.isHover) return false
		if (prev.className !== next.className) return false

		// Rerender when avatar is a function since content is unknown
		if (typeof prev.avatar === "function" || typeof next.avatar === "function") return false

		// Otherwise compare primitive/reference equality
		return prev.avatar === next.avatar
	},
)

// Title component handles title rendering separately
const ItemTitle = memo(
	<D extends DelightfulListItemType>({
		title,
		isHover,
	}: {
		title: D["title"]
		isHover: boolean
	}) => {
		const renderedTitle = useMemo(() => {
			if (typeof title === "function") return title(isHover)
			return title
		}, [title, isHover])

		// Return rendered title directly
		return renderedTitle as React.ReactNode
	},
	(prev, next) => {
		if (prev.isHover !== next.isHover) return false

		// Rerender when title is a function since content is unknown
		if (typeof prev.title === "function" || typeof next.title === "function") return false

		return prev.title === next.title
	},
)

// Hover content component
const HoverSection = memo(
	({
		content,
		isHover,
		onClickHandler,
	}: {
		content: React.ReactNode
		isHover: boolean
		onClickHandler: MouseEventHandler<HTMLDivElement>
	}) => {
		const { styles } = useDelightfulListItemStyles()

		if (!content) return null

		return (
			<div
				style={{ display: isHover ? "block" : "none" }}
				className={styles.extra}
				onClick={onClickHandler}
			>
				{content}
			</div>
		)
	},
	(prev, next) => {
		return (
			prev.isHover === next.isHover &&
			prev.content === next.content &&
			prev.onClickHandler === next.onClickHandler
		)
	},
)

// Main component
function DelightfulListItemBase<D extends DelightfulListItemType = DelightfulListItemType>({
	data,
	active,
	className,
	classNames,
	onClick,
	...props
}: DelightfulListItemProps<D>) {
	const { styles } = useDelightfulListItemStyles()
	const ref = useRef<HTMLDivElement | null>(null)
	const isHover = useHover(ref)

	// Memoize handlers
	const handleMore = useCallback<MouseEventHandler<HTMLDivElement>>((e) => {
		e.stopPropagation()
	}, [])

	const handleClick = useCallback<MouseEventHandler<HTMLDivElement>>(() => {
		onClick?.(data)
	}, [data, onClick])

	// Memoize class names
	const containerClassName = useMemo(() => {
		return cx(
			styles.container,
			active ? styles.active : undefined,
			className,
			classNames?.container,
		)
	}, [styles.container, styles.active, active, className, classNames?.container])

	return (
		<Flex
			ref={ref}
			className={containerClassName}
			gap={8}
			align="center"
			onClick={handleClick}
			role="listitem"
			data-testid="delightful-list-item"
			{...props}
		>
			{data.avatar && (
				<div style={{ flexShrink: 0 }}>
					<ItemAvatar
						avatar={data.avatar}
						isHover={isHover}
						className={classNames?.avatar}
					/>
				</div>
			)}
			<Flex vertical flex={1} justify="space-between" className={styles.mainWrapper}>
				<ItemTitle title={data.title} isHover={isHover} />
			</Flex>
			<HoverSection
				content={data.hoverSection}
				isHover={isHover}
				onClickHandler={handleMore}
			/>
		</Flex>
	)
}

// Memo-wrap component with custom comparator for perf
const DelightfulListItemOptimized = memo(DelightfulListItemBase, (prevProps, nextProps) => {
	// Rerender if active, className, or classNames change
	if (prevProps.active !== nextProps.active) return false
	if (prevProps.className !== nextProps.className) return false
	if (
		prevProps.classNames !== nextProps.classNames &&
		(prevProps.classNames?.container !== nextProps.classNames?.container ||
			prevProps.classNames?.avatar !== nextProps.classNames?.avatar ||
			prevProps.classNames?.title !== nextProps.classNames?.title ||
			prevProps.classNames?.content !== nextProps.classNames?.content)
	) {
		return false
	}

	// Rerender if click handler changes
	if (prevProps.onClick !== nextProps.onClick) return false

	// Core comparison: detect data changes
	const prevData = prevProps.data
	const nextData = nextProps.data

	// ID must match
	if (prevData.id !== nextData.id) return false

	// Then compare other key properties
	if (prevData.title !== nextData.title) return false
	if (prevData.avatar !== nextData.avatar) return false
	if (prevData.hoverSection !== nextData.hoverSection) return false

	// Skip rerender when nothing changed
	return true
}) as typeof DelightfulListItemBase

export default DelightfulListItemOptimized
