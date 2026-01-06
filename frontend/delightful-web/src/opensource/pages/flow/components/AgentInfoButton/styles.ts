import { createStyles } from "antd-style"

export const useStyles = createStyles(({ prefixCls, css, isDarkMode, token }) => {
  return {
    agentInfo: css`
			padding-bottom: 16px;
			border-bottom: 1px solid
				${isDarkMode ? token.delightfulColorScales.grey[4] : token.colorBorder};
		`,
    icon: css`
			width: 20px;
			height: 20px;
			border-radius: 4px;
		`,
    text2: css`
			color: ${isDarkMode ? token.delightfulColorScales.grey[5] : token.delightfulColorUsages.text[2]};
		`,
    text3: css`
			font-size: 12px;
			color: ${isDarkMode ? token.delightfulColorScales.grey[5] : token.delightfulColorUsages.text[3]};
		`,
    font12: css`
			font-size: 12px;
		`,
    tag: css`
			margin-right: 0;
			display: flex;
			align-items: center;
			gap: 2px;
			border: 0;
			background-color: ${isDarkMode
        ? token.delightfulColorUsages.fill[2]
        : token.delightfulColorUsages.fill[0]};
		`,
    button: css`
			width: 275px;
			border: 0;
			background-color: ${isDarkMode
        ? token.delightfulColorUsages.fill[2]
        : token.delightfulColorUsages.fill[0]};
			color: ${isDarkMode ? token.delightfulColorScales.grey[1] : token.delightfulColorUsages.text[1]};
		`,
    delete: css`
			background-color: ${isDarkMode
        ? token.delightfulColorScales.red[0]
        : token.delightfulColorScales.red[0]};
			color: ${isDarkMode ? token.delightfulColorScales.red[4] : token.delightfulColorScales.red[4]};
		`,
    transfer: css`
			color: ${isDarkMode
        ? token.delightfulColorScales.brand[5]
        : token.delightfulColorScales.brand[5]};
		`,
    iconButton: css`
			width: 44px !important;
			padding: 8px;
			border-radius: 8px;
			background-color: ${token.delightfulColorUsages.white};
			&:hover {
				background-color: ${token.delightfulColorUsages.white} !important;
			}
		`,
    switch: css`
			width: 40px;
			height: 24px;
			background-color: ${token.delightfulColorUsages.white};
			border: 1px solid ${token.colorBorder};
			.${prefixCls}-switch-handle {
				&::before {
					width: 18px;
					height: 18px;
					box-shadow: none;
					border: 1px solid ${token.delightfulColorUsages.border};
				}
			}
			&:hover {
				background-color: ${token.delightfulColorScales.grey[1]} !important;
			}
		`,
    switchChecked: css`
			background: ${token.delightfulColorScales.green[5]} !important;
			&:hover {
				background: ${token.delightfulColorScales.green[6]} !important;
			}
		`,
  }
})
