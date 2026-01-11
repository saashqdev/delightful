import { prefix } from "@/DelightfulFlow/constants"
import { useExternal } from "@/DelightfulFlow/context/ExternalContext/useExternal"
import { getPxWidth } from "@/DelightfulFlow/utils"
import { KEY_CODE_MAP } from "@/DelightfulJsonSchemaEditor/constants"
import TsInput from "@/common/BaseUI/Input"
import { Tooltip } from "antd"
import { IconPencil } from "@tabler/icons-react"
import { useMemoizedFn, useUpdateEffect } from "ahooks"
import clsx from "clsx"
import i18next from "i18next"
import React, { useMemo, useState } from "react"
import { useTranslation } from "react-i18next"
import styles from "./index.module.less"

type TextEditableProps = {
	title: string
	onChange: (value: string) => void
}

export default function TextEditable({ title, onChange }: TextEditableProps) {
	const { t } = useTranslation()
	const [isEdit, setIsEdit] = useState(false)

	// Actual title is based on what's already set, default title displayed if not set
	const [currentTitle, setCurrentTitle] = useState(title)

	/** Input box value, defaults to current actual title */
	const [inputTitle, setInputTitle] = useState(currentTitle || "")

	const { header } = useExternal()

	const handleKeyDown = useMemoizedFn((e) => {
		/** Handle enter key event */
		if ([KEY_CODE_MAP.ENTER].includes(e.keyCode)) {
			// console.log("confirm", e.target.value)
			setCurrentTitle(e.target.value)
			setIsEdit(false)
			if (onChange) onChange(e.target.value)
		}
	})

	/** Handle input blur event */
	const onInputBlur = useMemoizedFn((e) => {
		setCurrentTitle(e.target.value)
		setIsEdit(false)
		if (onChange) onChange(e.target.value)
	})

	const titleToolTips = useMemo(() => {
		return getPxWidth(currentTitle) > 300 ? currentTitle : ""
	}, [currentTitle])

	useUpdateEffect(() => {
		setInputTitle(title)
	}, [title])

	const onEdit = useMemoizedFn(() => {
		if (header?.editEvent) {
			header?.editEvent?.()
			return
		}
		setIsEdit(true)
	})

	return (
		<div className={clsx(styles.textEditable, `${prefix}text-editable`)}>
			{!isEdit && (
				<>
					<Tooltip title={titleToolTips}>
						<span className={clsx(styles.text, `${prefix}text`)}>{title}</span>
					</Tooltip>
					<IconPencil
						className={clsx(styles.editIcon, `${prefix}edit-icon`)}
						onClick={onEdit}
						size={18}
					/>
				</>
			)}
			{isEdit && (
				<TsInput
					placeholder={i18next.t("flow.pleaseInputFlowName", { ns: "delightfulFlow" })}
					onKeyDown={handleKeyDown}
					value={inputTitle}
					onChange={(e: any) => setInputTitle(e.target.value)}
					onBlur={onInputBlur}
					autoFocus
				/>
			)}
		</div>
	)
}

