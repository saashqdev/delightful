import { createStyles, keyframes } from "antd-style"

const rotateAnimation = keyframes`
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
`

const fadeInUp = keyframes`
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
`

export const useStyles = createStyles(({ token }) => {
	return {
		topicContainer: {
			width: "100%",
			overflow: "hidden",
			display: "flex",
			flexDirection: "row",
			justifyContent: "center",
			height: "100%",
			gap: token.marginXS,
		},
		messageContainer: {
			width: "480px",
			minWidth: "480px",
			height: "100%",
			overflowX: "hidden",
			overflowY: "hidden",
			position: "relative",
			paddingTop: "42px",
			border: `1px solid ${token.colorBorder}`,
			borderRadius: token.borderRadiusLG,
			backgroundColor: token.magicColorUsages.bg[1],
			transform: "none",
			scale: 1,
			transition: "all 0.8s ease-in-out",
			"@media (max-width: 768px)": {
				width: "100%",
				minWidth: "unset",
			},
		},
		fullWidthMessageContainer: {
			width: "100%",
			maxWidth: "800px",
			minWidth: "480px",
			"@media (max-width: 768px)": {
				width: "100%",
				minWidth: "unset",
				maxWidth: "unset",
			},
		},
		messageContainerNotStarted: {
			transform: "perspective(900px) rotateX(30deg) translateY(150px);!important",
			scale: 1.7,
			"@media (max-width: 768px)": {
				transform: "perspective(550px) rotateX(20deg) translateY(-20px);!important",
				scale: 1.1,
			},
		},
		messageListContainer: {
			padding: "10px 10px 10px 10px",
			width: "100%",
			height: "100%",
			overflowY: "auto",
			overflowX: "hidden",
		},
		messageListHeader: {
			position: "absolute",
			top: 0,
			left: 0,
			right: 0,
			zIndex: 1,
			height: "42px",
			fontWeight: 600,
			fontSize: "16px",
			color: token.magicColorUsages.text[1],
			padding: "10px 20px",
			borderBottom: `1px solid ${token.colorBorder}`,
		},
		waitingContainer: {
			display: "flex",
			flexDirection: "column",
			alignItems: "center",
			justifyContent: "center",
			position: "fixed",
			bottom: 0,
			left: 0,
			right: 0,
			background: "linear-gradient(180deg, rgba(249, 249, 249, 0) 0%, #F9F9F9 50%)",
			zIndex: 10,
			padding: "40px 20px",
			height: "100%",
			// backdropFilter: "blur(3px)",
			gap: "10px",
			transition: "all 0.3s ease-in-out",
			// boxShadow: "0 -4px 12px rgba(0, 0, 0, 0.03)",
			animation: `${fadeInUp} 0.5s ease-out`,
			"@media (max-width: 768px)": {
				padding: "30px 15px",
				paddingTop: "40%",
			},
		},
		watingTitle: {
			fontWeight: 600,
			fontSize: "18px",
			lineHeight: "24px",
			letterSpacing: "0px",
			textAlign: "center",
			textShadow: "0 1px 2px rgba(255, 255, 255, 0.8)",
			"@media (max-width: 768px)": {
				fontSize: "16px",
				lineHeight: "22px",
				marginTop: "10px",
			},
		},
		watingTitleWrapper: {
			padding: "8px 16px",
			borderRadius: "8px",
			backdropFilter: "blur(5px)",
			marginBottom: "5px",
		},
		waitingText: {
			fontWeight: 400,
			fontSize: "14px",
			lineHeight: "20px",
			letterSpacing: "0px",
			color: token.magicColorUsages.text[2],
			textShadow: "0 1px 1px rgba(255, 255, 255, 0.6)",
			"@media (max-width: 768px)": {
				fontSize: "13px",
				lineHeight: "18px",
			},
		},
		waitingTextWrapper: {
			padding: "6px 14px",
			borderRadius: "6px",
			backdropFilter: "blur(5px)",
			marginBottom: "10px",
		},
		waitingButton: {
			fontWeight: 500,
			marginTop: "15px",
			height: "40px",
			padding: "0 30px",
			fontSize: "16px",
			boxShadow: "0 2px 8px rgba(0, 0, 0, 0.1)",
			transition: "all 0.2s ease",
			"&:hover": {
				transform: "translateY(-2px)",
				boxShadow: "0 4px 12px rgba(0, 0, 0, 0.15)",
			},
			"@media (max-width: 768px)": {
				marginTop: "10px",
				height: "36px",
				padding: "0 20px",
				fontSize: "14px",
			},
		},
		leftContainer: {
			width: "300px",
			minWidth: "300px",
			height: "100%",
			borderRadius: token.borderRadiusLG,
			overflowY: "auto",
			display: "flex",
			flexDirection: "column",
			gap: "10px",
			transition: "all 0.3s ease-in-out",
		},
		detail: {
			minWidth: "400px",
			flex: 1,
			borderRadius: token.borderRadiusLG,
			backgroundColor: "#fff",
			overflowY: "hidden",
			border: `1px solid ${token.colorBorder}`,
			transition: "all 0.3s ease-in-out",
		},
		footer: {
			position: "fixed",
			bottom: 0,
			left: 0,
			right: 0,
			height: "52px",
			padding: "6px",
			backgroundColor: token.magicColorUsages.bg[1],
			border: `1px solid ${token.colorBorder}`,
			borderRadius: token.borderRadiusLG,
			width: `calc(100% - ${token.marginXS * 2}px)`,
			boxSizing: "border-box",
			margin: token.marginXS,
		},
		footerContent: {
			padding: "10px",
			height: "100%",
			display: "flex",
			flexDirection: "row",
			alignItems: "center",
			justifyContent: "space-between",
		},
		footerIcon: {
			width: "32px",
			height: "32px",
		},
		footerLeft: {
			display: "flex",
			alignItems: "center",
			gap: token.marginXS,
			fontWeight: 400,
		},
		magicIcon: {
			display: "flex",
			alignItems: "center",
			justifyContent: "center",
			backgroundColor: token.magicColorUsages.bg[1],
		},
		attachmentList: {
			flex: 1,
			display: "flex",
			flexDirection: "column",
			overflowY: "hidden",
		},
		taskData: {
			flex: 1,
			backgroundColor: token.magicColorUsages.bg[1],
			border: `1px solid ${token.colorBorder}`,
			borderRadius: token.borderRadiusLG,
			overflowX: "hidden",
		},
		item: {
			fontSize: "14px",
			height: 40,
			display: "flex",
			alignItems: "center",
			gap: 4,
			borderRadius: 8,
			padding: 10,
			color: token.magicColorUsages.text[1],
		},
		title: {
			fontSize: "12px",
			color: token.colorTextSecondary,
			fontWeight: 400,
			marginBottom: 10,
		},
		menuContainer: {
			marginTop: 10,
			borderBottom: `1px solid ${token.colorBorder}`,
		},
		icon: {
			width: "20px",
			height: "20px",
		},
		replayLogoContainer: {
			position: "relative",
			width: "60px",
			height: "60px",
			borderRadius: "50%",
			overflow: "hidden",
			boxShadow: "0 2px 10px rgba(0, 0, 0, 0.1)",
		},

		replayLogo: {
			width: "36px",
			height: "36px",
		},
		replayLogoDiv: {
			position: "absolute",
			width: "60px",
			height: "60px",
			borderRadius: "50%",
			zIndex: 10,
			display: "flex",
			justifyContent: "center",
			alignItems: "center",
		},
		overlay: {
			position: "absolute",
			top: "-30px",
			left: "-30px",
			width: "120px",
			height: "120px",
			background: "linear-gradient(90deg, #FFAFC8 0%, #E08AFF 50%, #9FC3FF 100%)",
			animation: `${rotateAnimation} 3s linear infinite`,
			opacity: 0.9,
		},
		rotate: {},
	}
})
