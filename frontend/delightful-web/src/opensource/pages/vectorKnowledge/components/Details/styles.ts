import { createStyles } from "antd-style"

export const useVectorKnowledgeDetailStyles = createStyles(
	({ css, token, isDarkMode, prefixCls }) => {
		return {
			wrapper: css`
				height: calc(100vh - 44px);
			`,
			leftContainer: css`
				min-width: 250px;
				border-right: 1px solid
					${isDarkMode ? token.delightfulColorScales.grey[8] : token.delightfulColorUsages.border};
			`,
			rightContainer: css`
				flex: 1;
				height: 100%;
				padding: 20px;
				overflow-x: auto;
			`,
			header: css`
				padding: 13px 20px;
				border-bottom: 1px solid ${token.colorBorder};
				font-size: 18px;
				font-weight: 600;
				color: ${isDarkMode
					? token.delightfulColorScales.grey[9]
					: token.delightfulColorUsages.text[1]};
				background: ${isDarkMode ? "transparent" : token.delightfulColorUsages.white};
				height: 50px;
			`,
			arrow: css`
				border-radius: 4px;
				cursor: pointer;
				&:hover {
					background: ${isDarkMode
						? token.delightfulColorScales.grey[6]
						: token.delightfulColorScales.grey[0]};
				}
			`,
		}
	},
)
