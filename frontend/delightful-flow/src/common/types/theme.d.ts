// src/theme.d.ts
import type { ColorScales, ColorUsages } from "@/common/utils/palettes"

// Extend antd provider theme typings
declare module 'antdg-provider/context' {
	export interface Theme {
		token?: CustomToken;
		components?: ComponentsToken;
	}
}

export interface CustomToken {
	/** Top menu bar height */
	titleBarHeight?: number
	delightfulColorScales: ColorScales
	delightfulColorUsages: ColorUsages
	[key: string]: any
}
