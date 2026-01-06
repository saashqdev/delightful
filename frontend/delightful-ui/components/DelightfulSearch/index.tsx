import { IconSearch } from "@tabler/icons-react"
import type { InputProps, InputRef } from "antd"
import { Input } from "antd"
import { cx } from "antd-style"
import { forwardRef, useState, memo, useEffect } from "react"
import type { CompositionEvent, ChangeEvent } from "react"
import DelightfulIcon from "../DelightfulIcon"
import { useStyles } from "./style"

export type DelightfulSearchProps = InputProps

const DelightfulSearch = memo(
	forwardRef<InputRef, DelightfulSearchProps>(({ className, onChange, value, ...props }, ref) => {
		const { styles } = useStyles()

		const [rawValue, setRawValue] = useState(value)
		const [isComposing, setIsComposing] = useState(false)

		useEffect(() => {
			setRawValue(value)
		}, [value])

		return (
			<Input
				ref={ref}
				value={rawValue}
				onCompositionStart={() => setIsComposing(true)}
				onCompositionEnd={(e: CompositionEvent<HTMLInputElement>) => {
					setIsComposing(false)
					// Fire onChange once when IME composition ends
					onChange?.(e as unknown as ChangeEvent<HTMLInputElement>)
				}}
				onChange={(e) => {
					// Only trigger during non-IME editing
					setRawValue(e.target.value)
					if (!isComposing) {
						onChange?.(e)
					}
				}}
				className={cx(styles.search, className)}
				prefix={<DelightfulIcon component={IconSearch} size={20} color="currentColor" />}
				{...props}
			/>
		)
	}),
)

export default DelightfulSearch
