import type { DelightfulButtonProps } from "@/opensource/components/base/DelightfulButton"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { IMStyle } from "@/opensource/providers/AppearanceProvider/context"
import { IconMoodHappy } from "@tabler/icons-react"
import { useMemo } from "react"
import { useTranslation } from "react-i18next"
import { Popover } from "antd"
import DelightfulEmojiPanel from "@/opensource/components/base/DelightfulEmojiPanel"
import type { EmojiInfo } from "@/opensource/components/base/DelightfulEmojiPanel/types"

interface EmojiButtonProps extends Omit<DelightfulButtonProps, "onClick"> {
	iconSize?: number
	imStyle?: IMStyle
	onEmojiClick?: (emoji: EmojiInfo) => void
}

function EmojiButton({
	iconSize = 20,
	imStyle,
	onEmojiClick,
	className,
	...props
}: EmojiButtonProps) {
	const { t } = useTranslation("interface")

	const isStandard = useMemo(() => imStyle === IMStyle.Standard, [imStyle])

	return (
		<Popover
			content={<DelightfulEmojiPanel onClick={onEmojiClick} />}
			trigger="click"
			styles={{
				body: {
					padding: 0,
				},
			}}
		>
			<DelightfulButton
				type="text"
				icon={<DelightfulIcon color="currentColor" size={iconSize} component={IconMoodHappy} />}
				className={className}
				{...props}
			>
				{isStandard ? t("chat.input.emoji") : undefined}
			</DelightfulButton>
		</Popover>
	)
}

export default EmojiButton
