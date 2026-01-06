import type { ReactNode } from "react"
import type { DelightfulAvatarProps } from "../base/DelightfulAvatar"

export interface DelightfulListItemData {
	id: string
	avatar?: string | DelightfulAvatarProps | ((isHover: boolean) => ReactNode)
	title?: string | ReactNode | ((isHover: boolean) => ReactNode)
	hoverSection?: ReactNode
	[key: string]: any
}
