import { IconSearch } from "@tabler/icons-react"
import type { InputProps, InputRef } from "antd"
import { Input } from "antd"
import { cx } from "antd-style"
import { useTranslation } from "react-i18next"
import { forwardRef, useState, memo } from "react"
import type { CompositionEvent, ChangeEvent } from "react"
import DelightfulIcon from "../DelightfulIcon"
import { useSearchStyles } from "./style"

const DelightfulSearch = memo(
	forwardRef<InputRef, InputProps>(({ className, onChange, value, ...props }, ref) => {
		const { styles } = useSearchStyles()
		const { t } = useTranslation("interface")

		const [rawValue, setRawValue] = useState(value)
		const [isComposing, setIsComposing] = useState(false)

		return (
			<Input
				ref={ref}
				value={rawValue}
				onCompositionStart={() => setIsComposing(true)}
				onCompositionEnd={(e: CompositionEvent<HTMLInputElement>) => {
					setIsComposing(false)
				// Trigger onChange when IME ends
				onChange?.(e as unknown as ChangeEvent<HTMLInputElement>)
			}}
			onChange={(e) => {
				// Only trigger when not in IME editing state
					setRawValue(e.target.value)
					if (!isComposing) {
						onChange?.(e)
					}
				}}
				className={cx(styles.search, className)}
				placeholder={t("search")}
				prefix={<DelightfulIcon component={IconSearch} size={20} color="currentColor" />}
				{...props}
			/>
		)
	}),
)

export default DelightfulSearch
