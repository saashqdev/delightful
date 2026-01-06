import "antd-style"
import "antd/es/theme/interface"
import type { ColorScales, ColorUsages } from "@dtyq/delightful-ui"

export interface NewToken {
	/** Top menu bar height */
	titleBarHeight?: number
	delightfulColorScales: ColorScales
	delightfulColorUsages: ColorUsages
}

// Extend antd-style `token` type via declaration merging
// By extending antd-style CustomToken object type definition, you can add corresponding token object in useTheme
declare module "antd-style" {
	// eslint-disable-next-line @typescript-eslint/no-empty-interface
	export interface DelightfulToken extends NewToken {}
}

// Extend antd's token type
declare module "antd/es/theme/interface" {
	export interface FullToken extends NewToken {}
	export interface AliasToken extends NewToken {}
}
