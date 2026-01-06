import { createStyles } from "antd-style"

export const useStyles = createStyles(({ css, token }) => {
	return {
		thirdPartyHeader: css`
			margin-bottom: 8px;
		`,
		thirdPartyBlock: css`
			margin-top: 12px;
		`,
		thirdPartyList: css`
			border-radius: 8px;
			border: 1px solid ${token.delightfulColorUsages.border};
			& > last-child {
				border: none;
			}
		`,
		thirdPartyApp: css`
			height: 50px;
			width: 100%;
			padding: 0 20px;
			border-bottom: 1px solid ${token.delightfulColorUsages.border};
		`,
		left: css`
			flex: 1;
		`,
		right: css``,
		text: css`
			font-weight: 400;
			font-size: 14px;
			line-height: 20px;
			letter-spacing: 0px;
		`,
		delete: css`
			color: ${token.delightfulColorUsages.danger.default};
			cursor: pointer;
			&:hover {
				color: ${token.delightfulColorUsages.danger.hover};
			}
		`,
		settings: css`
			color: ${token.delightfulColorUsages.primary.default};
			cursor: pointer;
			&:hover {
				color: ${token.delightfulColorUsages.primary.hover};
			}
		`,
		title: css`
			color: ${token.delightfulColorUsages.text[1]};
			font-weight: 600;
			font-size: 14px;
			line-height: 20px;
		`,
		desc: css`
			color: ${token.delightfulColorUsages.text[2]};
			font-size: 12px;
			line-height: 16px;
		`,
	}
})
