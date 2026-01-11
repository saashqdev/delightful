import { Form } from "antd"
import { ExpressionMode } from "@delightful/delightful-flow/dist/DelightfulExpressionWidget/constant"
import type { InputExpressionProps } from "@delightful/delightful-flow/dist/DelightfulExpressionWidget/types"
import type { WidgetExpressionValue } from "@delightful/delightful-flow/dist/common/BaseUI/DelightfulExpressionWrap"
import DelightfulExpressionWrap from "@delightful/delightful-flow/dist/common/BaseUI/DelightfulExpressionWrap"
import { cx } from "antd-style"
import styles from "./index.module.less"

// @ts-ignore
interface LLMInput extends Partial<InputExpressionProps> {
	label: string
	name: string
	value?: WidgetExpressionValue
	onChange?: (value: WidgetExpressionValue) => void
	placeholder?: string
	className?: string
	showCount?: boolean
	extra?: string
	required?: boolean
	showCustomLabel?: boolean
}

export default function DelightfulExpression({
	label,
	name,
	value,
	placeholder,
	className,
	onChange,
	showCount,
	extra = "",
	required = false,
	showCustomLabel = true,
	...props
}: LLMInput) {
	return (
		<div className={cx(styles.LLMInput, className)}>
			{showCustomLabel && (
				<div className={styles.header}>
					<div className={styles.left}>
						<span className={styles.title}>{label}</span>
					</div>
					<div className={styles.right}>
						{showCount && <span className={styles.count}>0</span>}
					</div>
				</div>
			)}
			<div className={cx(styles.body)}>
				<Form.Item
					name={name}
					extra={extra}
					required={required}
					label={showCustomLabel ? "" : label}
				>
					<DelightfulExpressionWrap
						onlyExpression
						mode={ExpressionMode.TextArea}
						placeholder={placeholder}
						minHeight="138px"
						value={value}
						onChange={onChange}
						{...props}
					/>
				</Form.Item>
			</div>
		</div>
	)
}





