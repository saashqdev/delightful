import { memo } from "react"
import type { MenuProps } from "antd"
import { Menu } from "antd"
import { useStyles } from "./style"

export type DelightfulMenuProps = MenuProps

const DelightfulMenu = memo(function DelightfulMenu({ rootClassName, className, ...props }: DelightfulMenuProps) {
	const { styles, cx } = useStyles()

	return (
		<Menu
			rootClassName={cx(styles.menuWrapper, rootClassName)}
			className={cx(styles.menu, className)}
			{...props}
		/>
	)
})

export default DelightfulMenu
