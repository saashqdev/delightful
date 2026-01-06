import type { DotLottieReactProps } from "@lottiefiles/dotlottie-react"
import { DotLottieReact } from "@lottiefiles/dotlottie-react"
import { memo } from "react"
import loadingJson from "./loading.json?raw"

const url = URL.createObjectURL(new Blob([loadingJson], { type: "application/json" }))

interface DelightfulLoadingProps extends DotLottieReactProps {
	section?: boolean
	size?: number
}

const DelightfulLoading = memo(function DelightfulLoading({ section, ...props }: DelightfulLoadingProps) {
	return (
		<DotLottieReact
			src={section ? url : url}
			loop
			speed={section ? 2 : 1}
			autoplay
			{...props}
		/>
	)
})

export default DelightfulLoading
