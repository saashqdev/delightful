import { Tag } from "antd"

import { useMemo } from "react"
import { createStyles, cx } from "antd-style"

const useStyles = createStyles(({ css, isDarkMode, token }) => {
	return {
		tag: css`
			margin-right: 0;
			display: flex;
			align-items: center;
			gap: 2px;
			border: none;
			background-color: ${isDarkMode
				? token.delightfulColorUsages.fill[2]
				: token.delightfulColorUsages.fill[0]};
			color: ${isDarkMode ? token.delightfulColorUsages.text[3] : token.delightfulColorUsages.text[2]};
		`,
		green: css`
			background-color: ${isDarkMode
				? token.delightfulColorScales.green[0]
				: token.delightfulColorScales.green[0]};
			color: ${isDarkMode
				? token.delightfulColorScales.green[5]
				: token.delightfulColorScales.green[5]};
		`,
		orange: css`
			background-color: ${isDarkMode
				? token.delightfulColorUsages.fill[2]
				: token.delightfulColorUsages.fill[0]};
		`,
		blue: css`
			background-color: ${isDarkMode
				? token.delightfulColorScales.brand[8]
				: token.delightfulColorScales.brand[0]};
		`,
	}
})

type FlowTagProps = {
	text: string
	icon: React.ReactNode
	color?: string
}

export default function FlowTag({ text, icon, color }: FlowTagProps) {
	const { styles } = useStyles()

	const selectedColor = useMemo(() => {
		const colorMap: { [key: string]: string } = {
			blue: styles.blue,
			green: styles.green,
			orange: styles.orange,
		}
		return color && colorMap[color]
	}, [color, styles.blue, styles.green, styles.orange])

	return (
		<Tag icon={icon} className={cx(styles.tag, selectedColor)}>
			{text}
		</Tag>
	)
}





