import { Input, Tooltip } from "antd"
import clsx from "clsx"
import i18next from "i18next"
import React, { memo, useCallback } from "react"
import { useTranslation } from "react-i18next"
import useTextEditable from "./hooks/useTextEditable"
import styles from "./index.module.less"

type TextEditableProps = {
	isEdit: boolean
	setIsEdit: React.Dispatch<React.SetStateAction<boolean>>
	title: string
	placeholder?: string
	onChange?: (val: string) => void
	className?: string
}

// Wrap with memo to avoid unnecessary rerenders
const TextEditable = memo(
	function TextEditable({
		isEdit,
		title,
		placeholder,
		onChange,
		setIsEdit,
		className,
	}: TextEditableProps) {
		const { t } = useTranslation()
		// Use a custom hook for text editing logic
		const { handleKeyDown, currentTitle, inputTitle, setInputTitle, onInputBlur } =
			useTextEditable({ title, onChange })

		// Optimize text input handler
		const handleInputChange = useCallback(
			(e: React.ChangeEvent<HTMLInputElement>) => {
				setInputTitle(e.target.value)
			},
			[setInputTitle],
		)

		// Optimize edit mode toggle
		const enableEditMode = useCallback(() => {
			setIsEdit(true)
		}, [setIsEdit])

		// Render different content based on edit state
		return (
			<div className={clsx(styles.titleEdit, className)}>
				{!isEdit ? (
					<div className={styles.titleView}>
						<Tooltip title={i18next.t("flow.click2ModifyName", { ns: "delightfulFlow" })}>
							<span className={styles.title} onClick={enableEditMode}>
								{currentTitle}
							</span>
						</Tooltip>
					</div>
				) : (
					<Input
						placeholder={placeholder}
						onKeyDown={handleKeyDown}
						value={inputTitle}
						onChange={handleInputChange}
						onBlur={onInputBlur}
						autoFocus
					/>
				)}
			</div>
		)
	},
	(prevProps, nextProps) => {
		// Custom comparator; rerender only on important prop changes
		return (
			prevProps.isEdit === nextProps.isEdit &&
			prevProps.title === nextProps.title &&
			prevProps.placeholder === nextProps.placeholder &&
			prevProps.className === nextProps.className
		)
	},
)

export default TextEditable

