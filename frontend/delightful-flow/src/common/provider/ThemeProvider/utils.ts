import {
	type ColorScales,
	type ColorUsages,
	colorScales,
	colorUsages,
	darkColorScales,
	darkColorUsages,
} from "@/common/utils/palettes"

export type ThemeAppearance = 'light' | 'dark' | 'auto'

export const genPalettesConfigs = (
	themeAppearance: ThemeAppearance,
): { delightfulColorScales: ColorScales; delightfulColorUsages: ColorUsages } => {
	switch (themeAppearance) {
		case "dark":
			return {
				delightfulColorScales: darkColorScales,
				delightfulColorUsages: darkColorUsages,
			}
		case "light":
			return {
				delightfulColorScales: colorScales,
				delightfulColorUsages: colorUsages,
			}
		case "auto":
			const isDarkMode = window.matchMedia("(prefers-color-scheme: dark)").matches
			return genPalettesConfigs(isDarkMode ? "dark" : "light")
		default:
			return {
				delightfulColorScales: colorScales,
				delightfulColorUsages: colorUsages,
			}
	}
}
