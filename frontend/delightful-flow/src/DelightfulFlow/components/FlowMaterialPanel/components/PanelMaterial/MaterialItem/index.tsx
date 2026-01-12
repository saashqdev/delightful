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
	//  usecustom钩子handleaddnode and draglogic
	const { onAddItem } = useAddItem({ item })
	const { onDragStart } = useDragNode({ item })

	//  提取need传递给子componentof property
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

//  useReact.memowrapcomponent，addcustomcomparefunctiononly compare keyproperty
const MaterialItem = memo(MaterialItemComponent, (prevProps, nextProps) => {
	//  onlywhen关键propertyre-render only when changednewrender
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

