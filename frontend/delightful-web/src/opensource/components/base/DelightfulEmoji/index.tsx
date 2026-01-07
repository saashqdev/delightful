import type { ImgHTMLAttributes } from "react"
import { memo } from "react"

const basePath = "/emojis"

export interface DelightfulEmojiProps extends Omit<ImgHTMLAttributes<HTMLImageElement>, "src" | "alt"> {
	code: string
	ns?: string
	suffix?: string
	size?: number
}

const DelightfulEmoji = memo(({ code, ns = "emojis/", suffix = ".png", ...rest }: DelightfulEmojiProps) => {
	if (!code) return null

	return <img draggable={false} src={`${basePath}/${ns}${code}${suffix}`} alt={code} {...rest} />
})

export default DelightfulEmoji
