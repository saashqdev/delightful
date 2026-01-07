import { createStyles } from "antd-style"

export const useStyles = createStyles(({ token }) => {
	return {
		header: {
			flex: "none",
			width: "100%",
			padding: "0 14px 0 14px",
			// Desktop
			// padding: "0 14px 0 90px",
			height: token.titleBarHeight,
			background: "transparent",
			justifyContent: "space-between",
			borderBottom: `1px solid ${token.colorBorder}`,
			userSelect: "none",
		},
		wrapper: {
			width: "auto",
			height: 30,
			display: "flex",
			gap: 10,
		},
		appWrapper: {
			paddingLeft: 68,
		},
		delightful: {
			width: "auto",
			height: 28,
			userSelect: "none",
			pointerEvents: "none",
		},
		button: {
			backgroundColor: token.delightfulColorUsages.fill[1],
		},
		search: {
			height: 30,
			width: 280,
		},
	}
})
