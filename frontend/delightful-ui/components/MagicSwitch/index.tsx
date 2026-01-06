import { Switch, type SwitchProps } from "antd"
import { useStyles } from "./style"

export type DelightfulSwitchProps = SwitchProps

function DelightfulSwitch({ className, ...props }: DelightfulSwitchProps) {
	const { styles, cx } = useStyles()

	return <Switch className={cx(styles.magicSwitch, className)} {...props} />
}

export default DelightfulSwitch
