import { ExpressionMode } from "@/DelightfulExpressionWidget/constant"
import { InputExpressionProps } from "@/DelightfulExpressionWidget/types"
import DelightfulExpressionWrap, { WidgetExpressionValue } from "@/common/BaseUI/DelightfulExpressionWrap"
import { Form } from "antd"
import clsx from "clsx"
import React from "react"
import styles from "./index.module.less"

// @ts-ignore
interface LLMInput extends Partial<InputExpressionProps> {
	label: string
	name: string
	value?: WidgetExpressionValue
	onChange?: (value: WidgetExpressionValue) => void
	placeholder?: string
	className?: string
}

export default function DelightfulExpression({
	label,
	name,
	value,
	placeholder,
	className,
	onChange,
	...props
}: LLMInput) {
	return (
		<div className={clsx(styles.LLMInput, className)}>
			<div className={styles.header}>
				<div className={styles.left}>
					<span className={styles.title}>{label}</span>
				</div>
				<div className={styles.right}>
					<span className={styles.count}>0</span>
				</div>
			</div>
			<div className={styles.body}>
				<Form.Item name={name}>
					<DelightfulExpressionWrap
						onlyExpression
						mode={ExpressionMode.TextArea}
						placeholder={placeholder}
						minHeight="138px"
						{...props}
					/>
				</Form.Item>
			</div>
		</div>
	)
}
