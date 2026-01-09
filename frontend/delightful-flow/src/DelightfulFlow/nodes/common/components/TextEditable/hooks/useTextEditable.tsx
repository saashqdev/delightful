import { getPxWidth } from "@/DelightfulFlow/utils"
import { KEY_CODE_MAP } from "@/DelightfulJsonSchemaEditor/constants"
import { useUpdateEffect } from "ahooks"
import { useCallback, useMemo, useState } from "react"

type UseTextEditableEditProps = {
	/** Default title */
	title: string
	/** Change handler */
	onChange?: (value: string) => void
}

export default function useTextEditableEdit({ title = "", onChange }: UseTextEditableEditProps) {
	const [isEdit, setIsEdit] = useState(false)
	// Prefer the explicit title; fall back to the default label when empty
	const [currentTitle, setCurrentTitle] = useState(title || "")
	/** Input value mirrors the current title by default */
	const [inputTitle, setInputTitle] = useState(currentTitle || "")

	useUpdateEffect(() => {
		setCurrentTitle(title || "")
		setInputTitle(title || "")
	}, [title])

	const handleKeyDown = useCallback(
		(e: any) => {
			/** Handle Enter key commit */
			if ([KEY_CODE_MAP.ENTER].includes(e.keyCode)) {
				// console.log("Confirm", e.target.value)
				setCurrentTitle(e.target.value)
				setIsEdit(false)
				if (onChange) onChange(e.target.value)
			}
		},
		[onChange],
	)

	/** Handle blur for the input */
	const onInputBlur = useCallback(
		(e: any) => {
			setCurrentTitle(e.target.value)
			setIsEdit(false)
			if (onChange) onChange(e.target.value)
		},
		[onChange],
	)

	const titleToolTips = useMemo(() => {
		if (!currentTitle) return ""
		return getPxWidth(currentTitle) > 100 ? currentTitle : ""
	}, [currentTitle])

	return {
		inputTitle,
		setInputTitle,
		isEdit,
		currentTitle,
		setIsEdit,
		handleKeyDown,
		onInputBlur,
		titleToolTips,
	}
}

