import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { splitNumber } from "@/utils/number"

import { IconThumbUp, IconThumbUpFilled } from "@tabler/icons-react"
import { Flex } from "antd"
import { createStyles, cx } from "antd-style"
import { useTranslation } from "react-i18next"

interface PraiseButtonProps {
	hasPraise?: boolean
	count?: number
}

const useStyles = createStyles(({ css, isDarkMode, token }) => {
	return {
		hasPraise: {
			color: token.magicColorScales.amber[5],
		},
		count: css`
			color: ${isDarkMode ? token.magicColorScales.grey[5] : token.magicColorUsages.text[3]};

			font-size: 12px;
			font-weight: 400;
			line-height: 16px;
		`,
	}
})

function PraiseButton({ hasPraise = false, count }: PraiseButtonProps) {
	const { t } = useTranslation("interface")

	const { styles } = useStyles()

	return (
		<DelightfulButton
			className={cx({ [styles.hasPraise]: hasPraise })}
			justify="flex-start"
			icon={
				hasPraise ? (
					<DelightfulIcon component={IconThumbUpFilled} size={20} />
				) : (
					<DelightfulIcon component={IconThumbUp} size={20} />
				)
			}
			size="large"
			type="text"
			block
		>
			<Flex flex={1} align="center" justify="space-between">
				{hasPraise ? t("chat.floatButton.Praised") : t("chat.floatButton.Praise")}
				{count ? <span className={styles.count}>{splitNumber(count)}</span> : null}
			</Flex>
		</DelightfulButton>
	)
}

export default PraiseButton
