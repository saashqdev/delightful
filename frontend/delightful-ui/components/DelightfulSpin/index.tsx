import type { SpinProps } from "antd"
import { Spin } from "antd"
import { memo } from "react"
import { useDelightfulSpinProps } from "./style"
import { cx } from "antd-style"

export type DelightfulSpinProps = SpinProps

const DelightfulSpin = memo(function DelightfulSpin({ children, size, className, ...props }: DelightfulSpinProps) {
	const delightfulSpinProps = useDelightfulSpinProps(size)

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
