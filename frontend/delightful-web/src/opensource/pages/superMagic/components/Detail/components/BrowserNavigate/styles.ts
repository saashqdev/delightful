import { createStyles } from "antd-style"

export const useStyles = createStyles(({ token }) => ({
	header: {
		padding: "10px",
		borderBottom: `1px solid ${token.delightfulColorUsages.border}`,
		flex: "none",
		backgroundColor: token.delightfulColorUsages.fill[0],
	},
	url: {
		backgroundColor: token.delightfulColorUsages.bg[0],
		borderRadius: 1000,
		padding: "0 10px",
		fontSize: 14,
		color: token.delightfulColorUsages.text[2],
		height: 32,
		display: "flex",
		alignItems: "center",
	},
	text: {
		overflow: "hidden",
		textOverflow: "ellipsis",
		whiteSpace: "nowrap",
		marginLeft: 4,
	},
	icon: {
		flex: "0 0 20px",
	},
}))
