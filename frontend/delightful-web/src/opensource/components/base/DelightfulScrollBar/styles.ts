import { createStyles } from "antd-style"

export const useStyles = createStyles(({ token, css }) => {
	return {
		scrollBar: css`
			&::before {
				background-color: ${token.delightfulColorScales.grey[9]} !important;
			}
		`,
	}
})
