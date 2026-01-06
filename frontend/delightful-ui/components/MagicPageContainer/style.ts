import { createStyles } from "antd-style"

export const useStyles = createStyles(({ css, isDarkMode, prefixCls, token }) => {
	return {
		card: {
			height: "100%",
			overflow: "hidden",
			borderRadius: 0,
			display: "flex",
			flexDirection: "column",
			backgroundColor: `${isDarkMode ? "#141414" : token.delightfulColorUsages.white}`,
			[`.${prefixCls}-card-body`]: {
				flex: 1,
				display: "flex",
			},
		},
		cardHeader: {
			borderRadius: "0 !important",
			position: "sticky",
			top: 0,
			color: `${
				isDarkMode ? token.delightfulColorScales.grey[8] : token.delightfulColorUsages.text[1]
			} !important`,
			zIndex: 10,
			backdropFilter: "blur(12px)",
			background: isDarkMode
				? `${token.delightfulColorScales.grey[0]} !important`
				: token.delightfulColorUsages.white,
			[`--${prefixCls}-padding-lg`]: "20px",
		},
		cardBody: css`
			height: calc(100vh - 55px);
			--${prefixCls}-padding-lg: 0;
      --${prefixCls}-card-body-padding: 0;
		`,

		closeButton: css`
			.${prefixCls}-btn-icon {
				height: 24px;
			}
		`,
	}
})
