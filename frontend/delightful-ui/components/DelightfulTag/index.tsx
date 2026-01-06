import { Tag, type TagProps } from "antd"
import DelightfulIcon from "../DelightfulIcon"
import { IconX } from "@tabler/icons-react"
import { useStyles } from "./style"

export type DelightfulTagProps = TagProps

function DelightfulTag({ className, ...props }: DelightfulTagProps) {
	const { styles, cx } = useStyles()

	return (
		<Tag
			className={cx(styles.tag, className)}
			closeIcon={<DelightfulIcon component={IconX} size={16} stroke={2} />}
			{...props}
		/>
	)
}

export default DelightfulTag
