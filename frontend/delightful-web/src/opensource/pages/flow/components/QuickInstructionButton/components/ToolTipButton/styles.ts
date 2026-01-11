import { createStyles } from "antd-style"

export const useStyles = createStyles(({ prefixCls, css, isDarkMode, token }) => {
	return {
		popover: css`
			padding: 10px;
		`,
		title: css`
			font-size: 14px;
			color: ${token.delightfulColorUsages.text[1]};
		`,
		form: css`
			display: flex;
			flex-direction: column;
			gap: 8px;
			.${prefixCls}-form-item {
				margin-bottom: 0px;
				.${prefixCls}-form-item-label {
					padding-bottom: 6px;
					label {
						font-size: 12px;
						font-weight: 400;
						line-height: 16px;
						color: ${token.delightfulColorUsages.text[2]};
					}
				}
			}
		`,
		input: css`
			resize: none;
			border: 1px solid ${isDarkMode ? token.delightfulColorUsages.border : token.delightfulColorUsages.border};
			background: ${isDarkMode ? token.delightfulColorUsages.fill[0] : token.delightfulColorUsages.fill[0]};
			--${prefixCls}-input-active-shadow: none !important;
			&::placeholder  {
				color: ${token.delightfulColorUsages.text[3]};
			}
			&:hover, &:focus{
				border: 1px solid ${isDarkMode ? token.delightfulColorUsages.border : token.delightfulColorUsages.border};
				background: ${isDarkMode ? token.delightfulColorUsages.fill[0] : token.delightfulColorUsages.fill[0]};
			}
			.${prefixCls}-input-suffix {
				position: absolute;
				right: 12px;
				bottom: 24px;
			}
		`,
		upload: css`
			width: 280px;
			height: 160px;
			display: flex;
			flex-direction: column;
			justify-content: center;
			align-items: center;
			border-radius: 8px;
			border: 1px dashed
				${isDarkMode ? token.delightfulColorUsages.border : token.delightfulColorUsages.border};
			background: ${isDarkMode
				? token.delightfulColorUsages.fill[0]
				: token.delightfulColorUsages.fill[0]};
			overflow: hidden;
		`,
		uploadTip: css`
			font-size: 12px;
			font-weight: 400;
			cursor: default;
			color: ${isDarkMode ? token.delightfulColorUsages.text[3] : token.delightfulColorUsages.text[3]};
			width: 280px;
			height: 160px;
			display: flex;
			align-items: center;
			justify-content: center;
		`,
		img: css`
			width: 100%;
		`,
		button: css`
			padding: 6px 12px;
			border: 0;
			border-radius: 8px;
			background-color: ${token.delightfulColorUsages.fill[0]};
			color: ${isDarkMode ? token.delightfulColorUsages.text[2] : token.delightfulColorUsages.text[1]};
		`,
		example: css`
			color: ${isDarkMode ? token.delightfulColorUsages.text[0] : token.delightfulColorUsages.text[0]};
		`,
		exampleImg: css`
			width: 100%;
			border-radius: 12px;
		`,
		exampleTitle: css`
			font-weight: 600;
			color: ${isDarkMode ? token.delightfulColorUsages.text[2] : token.delightfulColorUsages.text[2]};
		`,
		exampleDesc: css`
			color: ${isDarkMode ? token.delightfulColorUsages.text[2] : token.delightfulColorUsages.text[2]};
		`,
	}
})





