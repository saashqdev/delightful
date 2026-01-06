import { createStyles } from "antd-style"
import bg from "./bg.png"
import bgDark from "./bg-dark.png"

const useStyles = createStyles(
	(
		{ token, css, isDarkMode, prefixCls },
		{
			open,
			animationDuration,
			hidden,
		}: { open: boolean; animationDuration: number; hidden: boolean },
	) => ({
		animation: css`
			animation: ${open
				? `fadeIn ${animationDuration}ms ease-in-out`
				: `fadeOut ${animationDuration}ms ease-in-out`};
		`,
		container: css`
			width: 324px;
			max-height: 80vh;
			padding: 12px;
			overflow-y: auto;
			position: fixed;
			z-index: 2000;
			border-radius: 8px;
			box-shadow: ${token.boxShadow};
			background: url(${isDarkMode ? bgDark : bg}) no-repeat;
			background-size: 375px 284.473px;
			background-color: ${token.delightfulColorUsages.bg[1]};
			display: ${hidden ? "none" : "block"};
		`,
		header: css`
			margin-top: 38px;
			border-radius: 8px;
			padding: 10px 8px;
			background-color: ${token.delightfulColorUsages.bg[1]};
		`,
		segmented: css`
			width: 100%;
		`,
		headerTop: css`
			margin-top: -30px;
			height: 80px;
			overflow: visible;
		`,
		avatar: css``,
		username: css`
			margin-top: 16px;
			font-size: 18px;
			font-weight: 600;
			line-height: 24px;
			text-align: left;
		`,
		organization: css`
			font-size: 14px;
			font-weight: 400;
			line-height: 20px;
			text-align: left;
		`,
		descriptions: css`
			flex: 1;
			overflow-y: auto;
			max-height: 500px;
			border-radius: 8px;
			background-color: ${token.delightfulColorUsages.bg[1]};
			padding: 10px;
			.${prefixCls}-descriptions-item-label {
				min-width: 90px;
			}
			.${prefixCls}-descriptions-item {
				--delightful-descriptions-item-padding-bottom: 10px;
			}
		`,
		button: css`
			border: none;
			padding: 6px 12px;
			gap: 4px;
			background-color: ${isDarkMode
				? token.delightfulColorUsages.primaryLight.default
				: token.delightfulColorScales.brand[0]};
			color: ${token.delightfulColorScales.brand[5]};

			.${prefixCls}-btn-icon {
				line-height: 10px;
			}
		`,

		scheduleButton: css`
			border: none;
			border-radius: 4px;
			gap: 2px;
			background: ${token.delightfulColorScales.green[0]};
			color: ${token.delightfulColorScales.green[5]};

			&:hover {
				background: ${token.delightfulColorScales.green[1]} !important;
				color: ${token.delightfulColorScales.green[5]} !important;
			}
		`,
		tasksAssociatedWithMeButton: css`
			border: none;
			border-radius: 4px;
			gap: 2px;
			background: ${token.delightfulColorScales.lightBlue[0]};
			color: ${token.delightfulColorScales.blue[5]};

			&:hover {
				background: ${token.delightfulColorScales.lightBlue[1]} !important;
				color: ${token.delightfulColorScales.blue[5]} !important;
			}
		`,

		shareBusinessCardButton: css`
			border: none;
			background: ${token.delightfulColorUsages.fill[0]};
			color: ${token.delightfulColorUsages.text[1]};

			&:hover {
				background: ${token.delightfulColorUsages.fill[1]} !important;
				color: ${token.delightfulColorUsages.text[1]} !important;
			}
		`,
	}),
)

export default useStyles
