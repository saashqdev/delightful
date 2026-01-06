import type { ImgHTMLAttributes } from "react"
import { memo, useEffect, useState } from "react"

export interface DelightfulImageProps extends ImgHTMLAttributes<HTMLImageElement> {
	errorSrc?: string
}

const DelightfulImage = memo(function DelightfulImage(props: DelightfulImageProps) {
	const { errorSrc, ...imgProps } = props

	const [src, setSrc] = useState<string>(imgProps.src || "")

	useEffect(() => {
		if (imgProps.src) {
			setSrc(imgProps.src)
		}
	}, [imgProps.src])

	return (
		<img
			{...imgProps}
			src={src || errorSrc}
			onError={(event) => {
				setSrc(errorSrc || "")
				imgProps.onError?.(event)
			}}
		/>
	)
})

export default DelightfulImage
