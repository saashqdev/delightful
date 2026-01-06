import type { SplitterProps } from "antd"
import { Splitter } from "antd"
import type { PropsWithChildren } from "react"
import { memo } from "react"
import { useStyles } from "./style"

export type DelightfulSplitterProps = PropsWithChildren<SplitterProps>

const DelightfulSplitter = memo(function DelightfulSplitter({
	className,
	children,
	...props
}: DelightfulSplitterProps) {
	const { styles, cx } = useStyles()

	return (
		<Splitter className={cx(styles.splitter, className)} {...props}>
			{children}
		</Splitter>
	)
})

// @ts-ignore
DelightfulSplitter.Panel = Splitter.Panel

type CompoundedComponent = typeof DelightfulSplitter & {
	Panel: typeof Splitter.Panel
}

export default DelightfulSplitter as CompoundedComponent
