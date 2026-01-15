import { createStyles } from "antd-style"

export const useSearchStyles = createStyles(({ isDarkMode, token }) => {
	return {
		search: {
			paddingLeft: 6,
			paddingRight: 6,
			borderRadius: 8,
			border: `1px solid transparent`,
			color: isDarkMode
				? token.delightfulColorScales.grey[4]
				: token.delightfulColorUsages.text[3],
			// background: isDarkMode ? token.delightfulColorScales.grey[7] : token.delightfulColorUsages.white,
			backgroundColor: token.delightfulColorScales.grey[1],
			transition: "0.1s linear width",

			[`&:hover`]: {
				border: `1px solid ${token.colorBorder}`,
				backgroundColor: `${token.delightfulColorUsages.fill[0]}`,
				color: `${
					isDarkMode
						? token.delightfulColorScales.grey[4]
						: token.delightfulColorUsages.text[2]
				}`,
			},
			[`&:active`]: {
				backgroundColor: `${token.delightfulColorUsages.fill[2]}`,
			},
		},
	}
})
