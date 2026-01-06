import TextAnimation from "@/opensource/components/animations/TextAnimation"
import DelightfulSpin from "@/opensource/components/base/DelightfulSpin"
import { memo } from "react"

const StreamingPlaceholder = memo(({ tip }: { tip: string }) => {
	return (
		<DelightfulSpin spinning tip={<TextAnimation dotwaveAnimation>{tip}</TextAnimation>}>
			<div style={{ width: "100%", height: 360, minWidth: 360 }} />
		</DelightfulSpin>
	)
})

export default StreamingPlaceholder
