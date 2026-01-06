import { createStyles } from "antd-style"

export const useStyles = createStyles(({ css, token, isDarkMode }) => ({
	container: css`
		width: 100%;
		height: 100%;
		position: relative;
		overflow: hidden;
		background: ${isDarkMode ? token.delightfulColorUsages.bg[0] : token.delightfulColorUsages.white};
		border-radius: 6px;
		max-width: 100% !important;
		max-height: 100% !important;
	`,
	image: css`
		width: 100%;
		height: 100%;
		max-width: unset;
		max-height: unset;
	`,
	text: css`
		color: ${token.delightfulColorScales.black};
		text-align: center;
		font-size: 12px;
		font-weight: 400;
		line-height: 16px;
		user-select: none;
	`,
	animationBg: css`
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
	`,
	buttonGroup: css`
		position: absolute;
		bottom: 20px;
	`,
	button: css`
		height: 32px;
		font-size: 12px;
		margin: 0 auto;
		border-radius: 8px;
		padding: 4px 8px;
		color: ${isDarkMode ? token.delightfulColorUsages.text[1] : token.delightfulColorUsages.text[1]};
		background: ${isDarkMode
			? token.delightfulColorUsages.bg[1]
			: token.delightfulColorUsages.white} !important;
		transition: all 0.5s ease;
	`,
}))
