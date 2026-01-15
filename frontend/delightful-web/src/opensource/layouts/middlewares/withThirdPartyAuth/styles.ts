import { createStyles } from "antd-style"

export const useStyles = createStyles(({ css, isDarkMode, token, prefixCls }) => {
	const backgroundColor = isDarkMode
		? token.delightfulColorScales.grey[0]
		: token.delightfulColorScales.white[0]
	return {
		wrapper: css`
			width: 100vw;
			height: 100vh;
			background-color: ${backgroundColor};
		`,
		spin: css`
			.${prefixCls}-spin-blur {
				opacity: 1;
			}

			.${prefixCls}-spin {
				--${prefixCls}-spin-content-height: unset;
			}
		`,
	}
})
