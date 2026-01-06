import { createStyles } from "antd-style"

export default createStyles(({ css, prefixCls, token }) => ({
	container: css``,
	memberSection: css`
		background-color: ${token.delightfulColorScales.grey[0]};
		padding: 10px;
		border-radius: 10px;
		width: 100%;
	`,
	member: css`
		cursor: pointer;
		margin: 3px;
	`,
	addMember: css`
		--${prefixCls}-control-height: 45px;
    color: ${token.delightfulColorUsages.text[1]};
    background-color: ${token.delightfulColorUsages.fill[0]} !important;

    &:hover {
      background-color: ${token.delightfulColorUsages.fill[1]} !important;
    }

    border: none;
	`,
	text: css`
		overflow: hidden;
		text-align: center;
		text-overflow: ellipsis;

		font-size: 12px;
		font-weight: 400;
		line-height: 16px;
	`,
	title: css`
		padding-left: 10px;
		color: ${token.delightfulColorUsages.text[2]};
		font-size: 14px;
		font-weight: 400;
		line-height: 20px;
	`,
	list: css`
		border-radius: 10px;
		overflow: hidden;
	`,
}))
