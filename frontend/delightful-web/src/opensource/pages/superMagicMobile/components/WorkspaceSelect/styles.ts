import { createStyles } from "antd-style"

export const useStyles = createStyles(({ token }) => {
	return {
		container: {
			height: 32,
			border: `1px solid ${token.delightfulColorUsages.border}`,
			padding: "0 12px",
			display: "flex",
			alignItems: "center",
			borderRadius: 8,
			backgroundColor: "#fff",
			gap: 6,
			"&:active": {
				backgroundColor: token.delightfulColorUsages.fill[1],
			},
		},
		icon: {
			color: token.delightfulColorUsages.text[1],
		},
		name: {
			flex: "auto",
			textOverflow: "ellipsis",
			overflow: "hidden",
			whiteSpace: "nowrap",
		},
		popupBody: {
			borderRadius: 12,
			borderBottomRightRadius: 0,
			borderBottomLeftRadius: 0,
		},
		popupContent: {
			display: "flex",
			flexDirection: "column",
			height: "100%",
			backgroundColor: "#fff",
		},
		popupContentHeader: {
			display: "flex",
			justifyContent: "space-between",
			alignItems: "center",
			padding: "10px 12px",
			borderBottom: `1px solid ${token.delightfulColorUsages.border}`,
		},
		popupContentBody: {
			flex: "auto",
			overflow: "hidden auto",
		},
		popupContentFooter: {
			flex: "none",
			borderTop: `1px solid ${token.delightfulColorUsages.border}`,
		},
		popupContentFooterContent: {
			height: 50,
			display: "flex",
			alignItems: "center",
			justifyContent: "center",
			gap: 4,
			lineHeight: "20px",
			"&:active": {
				backgroundColor: token.delightfulColorUsages.fill[1],
			},
		},
		popupContentHeaderTitle: {
			fontSize: 18,
			fontWeight: 600,
			lineHeight: "24px",
		},
		popupContentHeaderClose: {
			//
		},
		closeButton: {
			width: `24px !important`,
			height: `24px !important`,
		},
	}
})
