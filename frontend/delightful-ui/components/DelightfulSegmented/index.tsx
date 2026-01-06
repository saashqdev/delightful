import { Segmented as AntdSegmented } from "antd"
import type { SegmentedProps } from "antd/es/segmented"
import { cx } from "antd-style"
import useStyles from "./styles"

export interface DelightfulSegmentedProps<K> extends SegmentedProps<K> {
	circle?: boolean
}

function DelightfulSegmented<K>({ className, circle = true, ...props }: DelightfulSegmentedProps<K>) {
	const { styles } = useStyles({ circle })

	return <AntdSegmented className={cx(styles.segmented, className)} {...props} />
}

export default DelightfulSegmented
