import type { SpinProps } from "antd"
import { Spin } from "antd"
import { cx } from "antd-style"
import { memo } from "react"
import { useDelightfulSpinProps } from "./useDelightfulSpinProps"

interface DelightfulSpinProps extends SpinProps {
	section?: boolean
}

const DelightfulSpin = memo(function DelightfulSpin({
	children,
	size,
	section = false,
	className,
	...props
}: DelightfulSpinProps) {
	const delightfulSpinProps = useDelightfulSpinProps(section, size)
	return (
		<Spin
			{...delightfulSpinProps}
			wrapperClassName={cx(delightfulSpinProps.wrapperClassName, className)}
			{...props}
		>
			{children}
		</Spin>
	)
})

export default DelightfulSpin
