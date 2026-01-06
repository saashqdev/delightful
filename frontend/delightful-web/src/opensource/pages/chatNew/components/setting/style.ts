import { createStyles } from "antd-style"

export const useStyles = createStyles(({ prefixCls, css, isDarkMode, token }) => ({
	header: css`
		--${prefixCls}-padding: 12px;
		--${prefixCls}-padding-lg: 12px;
		.${prefixCls}-drawer-header-title {
			flex-direction: row-reverse;
		}

    --${prefixCls}-color-split: ${token.colorBorder};

    .${prefixCls}-drawer-close {
      margin-right: 0;
    }
	`,
	icon: css`
		background-color: ${token.delightfulColorScales.green[5]};
		color: white;
		border-radius: 4px;
		padding: 4px;
	`,
	mask: css`
		--${prefixCls}-color-bg-mask: transparent;
	`,
	body: css`
		background-color: ${isDarkMode ? token.delightfulColorUsages.bg[2] : token.delightfulColorScales.white};
		--${prefixCls}-padding-lg: 12px;
	`,
}))
