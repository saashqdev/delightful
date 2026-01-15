import { useMemo, useEffect, useState } from "react"
import { reaction } from "mobx"
import { normalizeLocale } from "@/utils/locale"
import { useTranslation } from "react-i18next"
import { delightful } from "@/enhance/delightfulElectron"
import { configService } from "@/services"
import { configStore } from "../stores"

/**
 * Get global language
 * @param includeAuto Whether to include auto
 */
export function useGlobalLanguage<T>(includeAuto: T = true as T) {
	const [language, setLanguage] = useState(configStore.i18n.language)

	useEffect(() => {
		return reaction(
			() => configStore.i18n.language,
			(newLanguage) => setLanguage(newLanguage),
		)
	}, [])

	if (includeAuto) return language
	return language === "auto" ? normalizeLocale(window.navigator.language) : language
}

/**
 * Get global language list
 * @param includeAuto Whether to include auto
 */
export function useSupportLanguageOptions(includeAuto = true) {
	const { t } = useTranslation("interface")
	const [state, setState] = useState({
		language: configStore.i18n.language,
		languages: configStore.i18n.languages,
	})

	useEffect(() => {
		return reaction(
			() => ({
				language: configStore.i18n.language,
				languages: configStore.i18n.languages,
			}),
			(newState) => setState(newState),
		)
	}, [])

	return useMemo(() => {
		return state.languages.reduce<
			Array<{ label: string; value: string; translations?: Record<string, string> }>
		>(
			(array, lang) => {
				array.push({
					label: lang.translations?.[state.language] || lang.name,
					value: lang.locale,
					translations: lang.translations,
				})
				return array
			},
			includeAuto ? [{ label: t("setting.languages.auto"), value: "auto" }] : [],
		)
	}, [includeAuto, state.language, state.languages, t])
}

/**
 * @description Set global internationalization language
 * @param lang Language code
 */
export function setGlobalLanguage(lang: string) {
	delightful?.language?.setLanguage?.(lang)
	configService.setLanguage(lang)
}

/**
 * @description Get current cluster configuration
 */
export function useClusterConfig() {
	const [clusterConfig, setClusterConfig] = useState(configStore.cluster.cluster)
	const [clustersConfig, setClustersConfig] = useState(configStore.cluster.clusterConfig)

	useEffect(() => {
		const disposer = reaction(
			() => configStore.cluster.cluster,
			(config) => setClusterConfig(config),
			{ fireImmediately: true },
		)

		return () => disposer()
	}, [])

	useEffect(() => {
		const disposer = reaction(
			() => configStore.cluster.clusterConfig,
			(config) => setClustersConfig(config),
		)

		return () => disposer()
	}, [])

	return { clusterConfig, clustersConfig }
}

export function useTheme() {
	const [themeConfig, setThemeConfig] = useState(configStore.theme.theme)

	useEffect(() => {
		return reaction(
			() => configStore.theme.theme,
			(theme) => setThemeConfig(theme),
		)
	}, [])

	const prefersColorScheme = useMemo(() => {
		if (themeConfig === "auto") {
			return matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"
		}
		return themeConfig
	}, [themeConfig])

	return { theme: themeConfig, setTheme: configService.setThemeConfig, prefersColorScheme }
}

export function useAreaCodes() {
	const [areaCodes, setAreaCodes] = useState(configStore.i18n.areaCodes)

	useEffect(() => {
		return reaction(
			() => configStore.i18n.areaCodes,
			(config) => setAreaCodes(config),
		)
	}, [])

	return { areaCodes }
}
