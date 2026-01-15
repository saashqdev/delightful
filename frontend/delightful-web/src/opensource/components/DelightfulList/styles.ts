import { createStyles, cx } from "antd-style"

export const useDelightfulListItemStyles = createStyles(({ css, isDarkMode, token }) => {
	const extra = cx(css`
		color: ${isDarkMode
			? token.delightfulColorScales.grey[4]
			: token.delightfulColorUsages.text[1]};
		cursor: pointer;
		display: none;
		flex-shrink: 0;
		max-height: 100%;
		overflow: hidden;
	`)

	const mainWrapper = cx(css`
		max-width: 100%;
		overflow: hidden;
	`)

	return {
		container: css`
			padding: 10px;
			border-radius: 8px;
			cursor: pointer;
			position: relative;
			max-width: 100%;
			flex: 1;

			&:hover {
				.${extra} {
					display: block;
				}
				background-color: ${isDarkMode
					? token.delightfulColorScales.grey[0]
					: token.delightfulColorScales.grey[0]};
			}
			&:active {
				background-color: ${isDarkMode
					? token.delightfulColorScales.grey[1]
					: token.delightfulColorScales.grey[0]};
			}
		`,
		active: css`
			background-color: ${isDarkMode
				? token.delightfulColorUsages.primaryLight.default
				: token.delightfulColorUsages.primaryLight.default};
		`,
		mainWrapper,
		avatar: css`
			flex-shrink: 0;
		`,
		title: css`
			overflow: hidden;
			color: ${isDarkMode
				? token.delightfulColorUsages.white
				: token.delightfulColorUsages.text[1]};
			text-overflow: ellipsis;
			font-size: 14px;
			font-weight: 400;
			line-height: 20px;
			white-space: nowrap;
		`,
		content: css`
			flex-shrink: 1;
			overflow: hidden;
			color: ${isDarkMode
				? token.delightfulColorScales.grey[5]
				: token.delightfulColorUsages.text[3]};
			text-overflow: ellipsis;
			font-size: 12px;
			font-weight: 400;
			line-height: 16px;
			white-space: nowrap;
			max-width: 100%;
		`,
		time: css`
			overflow: hidden;
			color: ${isDarkMode
				? token.delightfulColorScales.grey[5]
				: token.delightfulColorUsages.text[3]};
			text-align: right;
			text-overflow: ellipsis;
			font-size: 12px;
			font-weight: 400;
			line-height: 16px;
			flex-shrink: 0;
		`,
		extra,
	}
})
