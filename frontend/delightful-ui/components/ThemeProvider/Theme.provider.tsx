import { createContext, useMemo } from "react"
import type { ThemeAppearance } from "antd-style"
import { ThemeProvider as AntdThemeProvider } from "antd-style"
import type { PropsWithChildren } from "react"
import { useMemoizedFn } from "ahooks"
import { genComponentTokenMap, genTokenMap } from "./tokenMap"
import { genPalettesConfigs } from "./utils"
import type { ColorScales, ColorUsages } from "./types"

export interface NewToken {
	/** Top menu bar height */
	titleBarHeight?: number
	delightfulColorScales: ColorScales
	delightfulColorUsages: ColorUsages
}

interface ThemeContextState {
	theme: ThemeAppearance
	prefersColorScheme: ThemeAppearance
	setTheme: (theme: ThemeAppearance) => void
}

type Theme = "light" | "dark" | "auto"

const ThemeContext = createContext<ThemeContextState>({} as ThemeContextState)

function ThemeProvider({ children, theme }: PropsWithChildren & { theme: Theme }) {
	const prefersColorScheme = useMemo(() => {
		if (theme === "auto") {
			return matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"
		}
		return theme
	}, [theme])

	const themeConfig = useMemoizedFn((appearance: ThemeAppearance) => {
		const config = genPalettesConfigs(appearance)
		return {
			cssVar: {
				prefix: "delightful",
			},
			token: {
				...genTokenMap(config.delightfulColorScales, config.delightfulColorUsages, appearance),
				titleBarHeight: 44,
				delightfulColorScales: config.delightfulColorScales,
				delightfulColorUsages: config.delightfulColorUsages,
			},
			components: genComponentTokenMap(
				config.delightfulColorScales,
				config.delightfulColorUsages,
				appearance,
			),
		}
	})

	return (
		<ThemeContext.Consumer>
			{(store) => (
				<ThemeContext.Provider value={store}>
					<AntdThemeProvider<NewToken>
						prefixCls="delightful"
						appearance={prefersColorScheme}
						themeMode={theme}
						theme={themeConfig}
					>
						{children}
					</AntdThemeProvider>
				</ThemeContext.Provider>
			)}
		</ThemeContext.Consumer>
	)
}

export default ThemeProvider
