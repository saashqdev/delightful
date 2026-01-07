import { delightful } from "@/enhance/delightfulElectron"
import { useAppearanceStore } from "@/opensource/providers/AppearanceProvider/context"
import { useMount } from "ahooks"
import { useCallback } from "react"
import { configStore } from "@/opensource/models/config"
import useView from "./useView"

/**
 * @description Combined with electron for global caching, currently only integrating shortcut key caching, can be extended to global local storage read/write in the future
 */
export default function useGlobalShortcut() {
	const initGlobalShortcut = useCallback(() => {
		delightful?.config?.globalShortcut?.getRegisterAll?.().then((globalShortcutConfig) => {
			useAppearanceStore.setState({
				shortcutKey: globalShortcutConfig?.reduce((config, item) => {
					config[item.name] = item.config
					return config
				}, {}),
			})
		})
	}, [])

	const initLocale = useCallback(() => {
		delightful?.language?.getLanguage().then((lang) => {
			configStore.i18n.setLanguage(lang)
		})
	}, [])

	useView({
		onShow() {
			console.log("--useGlobalShortcut-- show")
			initLocale()
			initGlobalShortcut()
		},
	})

	useMount(() => {
		initLocale()
		initGlobalShortcut()
	})
}
