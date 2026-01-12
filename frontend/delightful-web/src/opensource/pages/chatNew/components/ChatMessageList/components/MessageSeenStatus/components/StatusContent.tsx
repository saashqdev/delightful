import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { IconEye, IconEyeCheck } from "@tabler/icons-react"
import { Flex } from "antd"
import { createStyles } from "antd-style"
import { memo, HTMLAttributes } from "react"

const useStyles = createStyles(({ css, isDarkMode, token }) => ({
	icon: {
		color: isDarkMode ? token.delightfulColorScales.grey[6] : token.delightfulColorUsages.text[3],
	},
	text: css`
		color: ${isDarkMode ? token.delightfulColorScales.grey[6] : token.delightfulColorUsages.text[3]};
		text-align: justify;
		font-size: 12px;
		font-style: normal;
		font-weight: 400;
		line-height: 16px;
		user-select: none;
	`,
}))

// Extract icon to avoid repeated renders
const StatusIcon = memo(function StatusIcon({
	icon: Icon,
	className,
}: {
	icon: typeof IconEye | typeof IconEyeCheck
	className: string
}) {
	return <DelightfulIcon component={Icon} size={16} className={className} />
})

// Extract text to avoid repeated renders
const StatusText = memo(function StatusText({
	text,
	className,
}: {
	text: string
	className: string
}) {
	return <span className={className}>{text}</span>
})

interface StatusContentProps extends HTMLAttributes<HTMLDivElement> {
	icon: typeof IconEye | typeof IconEyeCheck
	text: string
	messageId?: string
}

// Extract content to avoid repeated renders
const StatusContent = memo(function StatusContent({
	icon: Icon,
	text,
	messageId,
	className,
}: StatusContentProps) {
	const { styles } = useStyles()
	return (
		<Flex
			align="center"
			justify="flex-end"
			gap={2}
			data-message-id={messageId}
			className={className}
		>
			<StatusIcon icon={Icon} className={styles.icon} />
			<StatusText text={text} className={styles.text} />
		</Flex>
	)
})

export default StatusContent
