import { createStyles } from "antd-style"

export const useStyles = createStyles(({ prefixCls, css, isDarkMode, token }) => ({
	modal: css`
		.${prefixCls}-modal-body {
			padding: 24px;
		}
		.${prefixCls}-modal-footer {
			border-top: 0;
			padding: 0 24px 24px 24px;
		}
	`,
	title: css`
		font-size: 18px;
		font-weight: 600;
		color: ${token.delightfulColorUsages.text[1]};
	}
	`,
	desc: css`
		font-size: 14px;
		color: ${token.delightfulColorUsages.text[1]};
	`,
	content: css`
		color: ${token.delightfulColorScales.orange[5]};
	`,
	fakeInput: css`
		width: 290px;
		height: 30px;
		padding-left: 12px;
		border-radius: 3px;
		color: ${token.delightfulColorUsages.text[3]};
		background: ${token.delightfulColorUsages.fill[0]};
	`,
	input: css`
		width: 290px;
		height: 30px;
		border-radius: 3px;
		border: 0;
		background: ${token.delightfulColorUsages.fill[0]};
		&:hover {
			background: ${token.delightfulColorUsages.fill[0]};
		}
	`,
	button: css`
		border-radius: 8px;
		min-width: unset !important;
		background: ${token.delightfulColorUsages.fill[0]};
	`,
	dangerButton: css`
		border-radius: 8px;
		color: ${token.delightfulColorUsages.white};
		background: ${
			isDarkMode
				? token.delightfulColorUsages.danger.default
				: token.delightfulColorUsages.danger.default
		};
		&:hover {
			--${prefixCls}-button-default-hover-bg: ${token.delightfulColorUsages.danger.hover};
			--${prefixCls}-button-default-active-bg: ${token.delightfulColorUsages.danger.active};
			span {
				color: ${token.delightfulColorUsages.white};
			}
		}
		&:disabled {
			color: ${token.delightfulColorUsages.white};
			background: ${token.delightfulColorScales.red[1]};
		}
	
	`,
}))
