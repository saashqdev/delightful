import { prefix } from "@/DelightfulFlow/constants"
import { NodeSchema } from "@/DelightfulFlow/register/node"
import clsx from "clsx"
import { memo, useMemo } from "react"
import styles from "./index.module.less"
import ItemAvatar from "./components/ItemAvatar"
import ItemTitle from "./components/ItemTitle"
import AddButton from "./components/AddButton"
import useAddItem from "./hooks/useAddItem"
import useDragNode from "./hooks/useDragNode"

type MaterialItemProps = NodeSchema & {
	showIcon?: boolean
	inGroup?: boolean
	avatar?: string
}

function MaterialItemComponent({
	showIcon = true,
	inGroup = false,
	avatar,
	...item
}: MaterialItemProps) {
	// 使用自定义钩子handle添加node和拖拽逻辑
	const { onAddItem } = useAddItem({ item })
	const { onDragStart } = useDragNode({ item })

	// 提取需要传递给子component的property
	const avatarProps = useMemo(
		() => ({
			showIcon,
			avatar,
			icon: item.icon,
			color: item.color,
			type: item.type,
		}),
		[showIcon, avatar, item.icon, item.color, item.type],
	)

	const titleProps = useMemo(
		() => ({
			label: item.label,
			desc: item.desc,
		}),
		[item.label, item.desc],
	)

	return (
		<div
			className={clsx(
				styles.materialItem,
				{
					[styles.inGroup]: inGroup,
				},
				`${prefix}material-item`,
			)}
			draggable
			onDragStart={onDragStart}
		>
			<div className={clsx(styles.header, `${prefix}header`)}>
				<div className={clsx(styles.left, `${prefix}left`)}>
					<ItemAvatar {...avatarProps} />
					<ItemTitle {...titleProps} />
				</div>
				<AddButton onAddItem={onAddItem} />
			</div>
		</div>
	)
}

// 使用React.memo包装component，添加自定义比较function只比较关键property
const MaterialItem = memo(MaterialItemComponent, (prevProps, nextProps) => {
	// 只有当关键property发生变化时才重新渲染
	return (
		prevProps.id === nextProps.id &&
		prevProps.label === nextProps.label &&
		prevProps.desc === nextProps.desc &&
		prevProps.showIcon === nextProps.showIcon &&
		prevProps.inGroup === nextProps.inGroup &&
		prevProps.avatar === nextProps.avatar &&
		prevProps.color === nextProps.color &&
		prevProps.icon === nextProps.icon &&
		prevProps.type === nextProps.type
	)
})

export default MaterialItem

