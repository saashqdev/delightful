import "antd-style"
import "antd/es/theme/interface"
import type { ColorScales, ColorUsages } from "../components/ThemeProvider/types"

export interface NewToken {
	delightfulColorScales: ColorScales
	delightfulColorUsages: ColorUsages
}

// Extend antd-style `token` type via declaration merging
// Add CustomToken shape so useTheme exposes these token objects
declare module "antd-style" {
	export interface CustomToken extends NewToken {}
}

// Extend antd token types
declare module "antd/es/theme/interface" {
	export interface FullToken extends NewToken {}
	export interface AliasToken extends NewToken {}
}
