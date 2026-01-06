import { createStyles } from "antd-style"
import { DelightfulMermaidType } from "./constants"

export const useStyles = createStyles(
	({ css, token, isDarkMode }, { type }: { type: DelightfulMermaidType }) => ({
		container: css`
			position: relative;
			display: flex;
			justify-content: center;
			align-items: center;
			border: 1px solid ${token.delightfulColorUsages.border};
			border-radius: 8px;
			margin: 10px 0;
			min-height: 55px;
			background-color: ${token.colorBgContainer};
			overflow: hidden;
		`,
		error: css`
			display: ${type === DelightfulMermaidType.Mermaid ? "block" : "none"};
			padding: 10px;
			margin-right: 140px;
		`,
		segmented: css`
			bottom: 10px;
			right: 10px;
			position: absolute;
			z-index: 1;
		`,
		mermaid: css`
			display: ${type === DelightfulMermaidType.Mermaid ? "block" : "none"};
			width: 100%;
			font-size: 14px;
			line-height: 1;
		`,
		mermaidInnerWrapper: css`
			border: none;
			padding: 20px 10px;
			cursor: pointer;
			line-height: 1;
			height: fit-content;

			svg text {
				fill: ${isDarkMode ? `${token.colorText} !important` : "unset"};
			}
		`,
		code: css`
			display: ${type === DelightfulMermaidType.Mermaid ? "none" : "block"};
			width: 100%;
			border: none;
			margin: 0;
		`,
	}),
)
