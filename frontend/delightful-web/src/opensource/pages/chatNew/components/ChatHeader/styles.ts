import { createStyles } from "antd-style"
import { transparentize } from "polished"

const useStyles = createStyles(({ css, isDarkMode, token }) => {
	return {
		header: css`
			width: 100%;
			height: 60px;
			padding: 12px 16px;
			z-index: 1;
			background-color: ${transparentize(
				1 - 0.9,
				isDarkMode
					? token.delightfulColorScales.grey[0]
					: token.delightfulColorScales.grey[0],
			)};
			backdrop-filter: blur(5px);
			border-bottom: 1px solid ${token.colorBorder};
			user-select: none;
		`,
		headerTopic: css`
			overflow: hidden;
			color: ${isDarkMode
				? token.delightfulColorScales.grey[5]
				: token.delightfulColorUsages.text[3]};
			text-overflow: ellipsis;
			white-space: nowrap;

			font-size: 12px;
			font-weight: 400;
			line-height: 16px;
			max-width: 60%;
			user-select: none;
		`,
		headerTitle: css`
			color: ${isDarkMode
				? token.delightfulColorUsages.white
				: token.delightfulColorUsages.text[1]};
			font-size: 16px;
			font-weight: 600;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			max-width: 60%;
			user-select: none;
		`,
		extraSectionButtonActive: css`
			background: ${token.delightfulColorUsages.primaryLight.default};
			color: ${isDarkMode
				? token.delightfulColorUsages.white
				: token.delightfulColorScales.brand[5]};
			user-select: none;
		`,
	}
})

export default useStyles
