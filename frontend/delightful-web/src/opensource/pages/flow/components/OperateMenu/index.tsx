import type { PopoverProps } from "antd"
import { Popover } from "antd"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { IconDots } from "@tabler/icons-react"
import { useState } from "react"
import useStyles from "./styles"

export interface OperateMenuProps extends PopoverProps {
	useIcon?: boolean
	Icon?: JSX.Element
	menuItems: React.ReactNode
}

function OperateMenu({
	useIcon = false,
	Icon,
	menuItems,
	children,
	className,
	...props
}: OperateMenuProps) {
	const { styles } = useStyles()
	const [open, setOpen] = useState(false)

	const handleContextMenu = (event: React.MouseEvent) => {
		event.preventDefault() // 阻止默认右键菜单
	}

	const handleClick = (event: React.MouseEvent) => {
		event.stopPropagation()
	}

	const handleClickContent = (event: React.MouseEvent) => {
		setOpen(false)
		event.stopPropagation()
	}

	return (
		<Popover
			overlayClassName={styles.popover}
			placement="topLeft"
			arrow={false}
			content={<div onClick={handleClickContent}>{menuItems}</div>}
			trigger="click"
			autoAdjustOverflow
			open={open}
			onOpenChange={setOpen}
			{...props}
		>
			<div onContextMenu={handleContextMenu} onClick={handleClick} className={className}>
				{useIcon &&
					(Icon || (
						<DelightfulButton
							type="text"
							icon={<DelightfulIcon color="currentColor" component={IconDots} size={18} />}
						/>
					))}
				{children}
			</div>
		</Popover>
	)
}

export default OperateMenu
