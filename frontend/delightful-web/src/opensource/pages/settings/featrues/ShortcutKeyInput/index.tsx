import { createStyles } from "antd-style"
import { Input } from "antd"
import { useCallback, useMemo } from "react"
import type { KeyboardEvent } from "react"
import { useTranslation } from "react-i18next"
import { useAppearanceStore } from "@/opensource/providers/AppearanceProvider/context"
import { set } from "lodash-es"
import { useShallow } from "zustand/react/shallow"
import { delightful } from "@/enhance/delightfulElectron"
import { useMemoizedFn } from "ahooks"
import useKeyCode from "./useKeyCode"

const useStyles = createStyles(() => {
	return {
		field: {
			"&>input": {
				textAlign: "center",
			},
		},
	}
})

function ShortcutKeyInput() {
	const { styles } = useStyles()
	const { t } = useTranslation("interface")

	const { getSystemShortcut } = useKeyCode()

	const keyCode = useAppearanceStore(
		useShallow((state) => state?.shortcutKey?.globalSearch ?? [91, 75]),
	)

	const shortcutKey = useAppearanceStore(useShallow((state) => state.shortcutKey))

	const shortcut = useMemo(() => {
		if (!keyCode || keyCode?.length < 2) {
			return ""
		}
		return [...keyCode].map((i) => getSystemShortcut(`${i}`)).join(" + ")
	}, [getSystemShortcut, keyCode])

	const onBlur = useMemoizedFn(() => {
		if (shortcutKey) {
			delightful?.config?.globalShortcut?.register?.(shortcutKey)
			console.log("-shortcutKey-", shortcutKey)
		}
	})

	const onFocus = useMemoizedFn(() => {
		/** Remove all shortcut keys */
		delightful?.config?.globalShortcut?.unregisterAll()
	})

	// Key press handling
	const onKeyDown = useCallback((event: KeyboardEvent<HTMLInputElement>) => {
		event.preventDefault() // Prevent default behavior
		event?.stopPropagation()
		if (event?.keyCode === 8) {
			useAppearanceStore.setState((preState) => {
				set(preState, ["shortcutKey", "globalSearch"], null)
			})
			return
		}
		const keys = []
		const code: Array<number> = []
		if (event.metaKey) {
			code.push(91)
		}
		if (event.ctrlKey) {
			code.push(17)
		}
		if (event.altKey) {
			code.push(18)
		}
		if (event.shiftKey) {
			code.push(16)
		}
		// Only ineffective when there is only a single modifier key
		if (![16, 17, 18, 91].includes(event?.keyCode)) {
			code.push(event?.keyCode)
		}
		if (code.length < 2) {
			return
		}
		if (event.key && !["Meta", "Control", "Shift", "Alt"].includes(event.key)) {
			keys.push(event.key.toUpperCase())
		}

		useAppearanceStore.setState((preState) => {
			set(preState, ["shortcutKey", "globalSearch"], code)
		})
	}, [])

	return (
		<div className={styles.field}>
			<Input
				onBlur={onBlur}
				onFocus={onFocus}
				value={shortcut}
				onKeyDown={onKeyDown}
				placeholder={t("setting.shortcutKeyPlaceholder")}
			/>
		</div>
	)
}

export default ShortcutKeyInput
