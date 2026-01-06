import { getAntdLocale, normalizeLocale } from "@/common/utils/locale"
import { useDeepCompareEffect, useRequest } from "ahooks"
import { ConfigProvider, Flex, Spin } from "antd"
import { i18n } from "i18next"
import type { PropsWithChildren } from "react"
import React, { createContext, useState } from "react"
import { I18nextProvider } from "react-i18next"
import { isRtlLang } from "rtl-detect"
import { useShallow } from "zustand/react/shallow"
import type { GlobalLanguageStoreType } from "./store"
import { store } from "./store"
import { languageHelper } from "./utils"

const LocalesContext = createContext<GlobalLanguageStoreType>(store)

/**
 * @description Intermediate layer for the global language instance; syncs language from storage/defaults on init
 * Language bundle loading is controlled via useState to avoid re-rendering children and repeatedly firing useEffect
 */
function IntermediateLayer({
	children,
	instance,
}: PropsWithChildren<{
	instance: i18n
}>) {
	// Prevent child effects from re-running while the initial language bundle is loading
	const [loading, setLoading] = useState(true)

	const language = store(
		useShallow((languageStore) => {
			const lang = languageStore.language

			return lang === "auto"
				? languageHelper.transform(normalizeLocale(window.navigator.language))
				: lang
		}),
	)

	const {
		data: locale,
		cancel,
		run,
	} = useRequest((key: string) => getAntdLocale(key), {
		manual: true,
		onSuccess() {
			setLoading(false)
		},
	})

	// change language
	useDeepCompareEffect(() => {
		run?.(language)
		instance?.changeLanguage?.(language)
		return () => {
			cancel?.()
		}
	}, [language])

	// detect document direction
	const documentDir = isRtlLang(language!) ? "rtl" : "ltr"

	return (
		<I18nextProvider i18n={instance}>
			<ConfigProvider direction={documentDir} locale={locale}>
				{loading ? (
					<Flex align="center" justify="center">
						<Spin />
					</Flex>
				) : (
					children
				)}
			</ConfigProvider>
		</I18nextProvider>
	)
}

export function MagicFlowLocaleProvider(
	props: PropsWithChildren<{ store?: GlobalLanguageStoreType; i18nInstance: i18n }>,
) {
	return (
		<LocalesContext.Consumer>
			{(contextValue) => (
				<LocalesContext.Provider value={props?.store ?? contextValue}>
					<IntermediateLayer instance={props?.i18nInstance}>
						{props?.children}
					</IntermediateLayer>
				</LocalesContext.Provider>
			)}
		</LocalesContext.Consumer>
	)
}
